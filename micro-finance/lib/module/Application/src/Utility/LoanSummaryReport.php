<?php
/**
 * Application LoanSummary Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Loan, Member};
use Web\View\Support\LookupStore;
use Application\ViewHelper\Currency;


class LoanSummaryReport
{
    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_memberModel;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_startDate = '';
    
    /**
     * 
     * @var string
     */
    private $_endDate = '';
    
    /**
     * 
     * @var int
     */
    private $_fmcId = 0;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_default = [];
    
    /**
     * 
     * @var array
     */
    private $_fmc = [];
    
    /**
     * 
     * @var array
     */
    private $_sources = [];
    
    /**
     * 
     * @var array
     */
    private $_types = [];
    
    /**
     * 
     * @var array
     */
    private $_tables = [];
    
    /**
     * 
     * @var array
     */
    private $_joins = [];
    
    /**
     * 
     * @var array
     */
    private $_groups = [];
    
    /**
     * 
     * @var array
     */
    private $_where = [];
    
    /**
     * 
     * @var \Application\ViewHelper\Currency
     */
    private $_currency;
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_memberModel = new Member();
        $this->_sql = $this->_model->getSql();
        $this->_currency = new Currency();
    }
    
    public function setStartDate($date)
    {
        $this->_startDate = $date;
        return $this;
    }
    
    public function setEndDate($date)
    {
        $this->_endDate = $date;
        return $this;
    }
    
    public function setFmcId($id)
    {
        $this->_fmcId = $id;
        return $this;
    }
    
    public function report()
    {
        return $this->_report;
    }
    
    public function isSummary()
    {
        return 0 == $this->_fmcId;
    }
    
    public function run()
    {
        $this->_preload();
        $this->_loadTotal();
        if (0 == $this->_report['total']['loans']) {
            return false;
        }
        
        $this->_loadIds();
        $this->_default['default'] = ['loans' => 0, 'amount' => 0, 'active' => 0, 'active_amount' => 0];
        $this->_report['source'] = array_fill_keys($this->_sources, $this->_default['default']);
        $this->_report['type'] = array_fill_keys($this->_types, $this->_default['default']);
        if ($this->_fmcId > 0) {
            $this->_fmcReport();
        } else {
            $this->_summaryReport();
        }
        
        return true;
    }
    
    public function amount($amount, $mask = true)
    {
        if ($amount > 0.00) {
            return $this->_currency->currency($amount, 0);
        }
        
        if ($mask) {
            return '--';
        }
        
        return '0';
    }
    
    private function _preload()
    {
        $pattern = '%s `%s` ';
        $this->_tables['loan'] = sprintf($pattern, $this->_model->getTableQuoted(), 'l');
        $this->_tables['member'] = sprintf($pattern, $this->_memberModel->getTableQuoted(), 'm');
        
        $join = 'INNER JOIN %s ON `%s`.`id` = `%s`.`%s_id` ';
        $this->_joins['member'] = sprintf($join, $this->_tables['member'], 'm', 'l', 'member');
        
        $this->_groups = [
            'fmc' => ' GROUP BY `m`.`fmc_id`',
            'source' => ' GROUP BY `l`.`loan_source_id`',
            'type' => ' GROUP BY `l`.`loan_type_id`',
        ];
    }
    
    private function _loadTotal()
    {
        if ('' != $this->_startDate || '' != $this->_endDate) {
            if ('' != $this->_startDate && '' != $this->_endDate) {
                if ($this->_startDate == $this->_endDate) {
                    $this->_sql->whereEqual('l.loan_date', $this->_startDate);
                } else {
                    $this->_sql->whereBetween('l.loan_date', $this->_startDate, $this->_endDate);
                }
            } elseif ('' != $this->_startDate) {
                $this->_sql->whereGreater('l.loan_date', $this->_startDate, true);
            } else {
                $this->_sql->whereLess('l.loan_date', $this->_endDate, true);
            }
        }
        
        if ($this->_fmcId > 0) {
            $this->_sql->whereId('m.fmc_id', $this->_fmcId);
        }
        
        $this->_where['all'] = $this->_sql->getWhere();
        $this->_sql->whereZero('l.closed');
        $this->_where['active'] = $this->_sql->getWhere();
        
        $query1 = 'SELECT COUNT(`l`.`id`) AS `total`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'];
        $query2 = 'SELECT COUNT(`l`.`id`) AS `total`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'];
        if ($this->_fmcId > 0) {
            $query1 .= $this->_joins['member'];
            $query2 .= $this->_joins['member'];
        }
        
        if (! empty($this->_where['all'])) {
            $query1 .= 'WHERE ' . $this->_where['all'];
        }
        $query2 .= 'WHERE ' . $this->_where['active'];
        
        $total = $this->_model->queryRow($query1);
        $active = $this->_model->queryRow($query2);
        $this->_report['total'] = [
            'loans' => $total->total,
            'amount' => $total->amount,
            'active' => $active->total,
            'active_amount' => $active->amount,
        ];
        
    }
    
    private function _summaryReport()
    {
        $this->_report['fmc'] = array_fill_keys($this->_fmc, $this->_default['default']);
        
        $query1 = 'SELECT DISTINCT `m`.`fmc_id`, COUNT(`l`.`id`) AS `loans`, '
                . 'SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'] . $this->_joins['member'];
        if (! empty($this->_where['all'])) {
            $query1 .= 'WHERE ' . $this->_where['all'];
        }
        $query1 .= $this->_groups['fmc'];
        $list1 = $this->_model->queryRows($query1);
        foreach ($list1 as $item) {
            $this->_report['fmc'][$item->fmc_id]['loans'] = $item->loans;
            $this->_report['fmc'][$item->fmc_id]['amount'] = $item->amount;
        }
        
        $query10 = 'SELECT DISTINCT `m`.`fmc_id`, COUNT(`l`.`id`) AS `loans`, '
                 . 'SUM(`l`.`loan_amount`) AS `amount` '
                 . 'FROM ' . $this->_tables['loan'] . $this->_joins['member']
                 . 'WHERE ' . $this->_where['active'] . $this->_groups['fmc'];
        $list10 = $this->_model->queryRows($query10);
        foreach ($list10 as $item) {
            $this->_report['fmc'][$item->fmc_id]['active'] = $item->loans;
            $this->_report['fmc'][$item->fmc_id]['active_amount'] = $item->amount;
        }
        
        $query2 = 'SELECT DISTINCT `l`.`loan_source_id`, COUNT(`l`.`id`) AS `loans`, '
                . 'SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'];
        if (! empty($this->_where['all'])) {
            $query2 .= 'WHERE ' . $this->_where['all'];
        }
        $query2 .= $this->_groups['source'];
        $list2 = $this->_model->queryRows($query2);
        foreach ($list2 as $item) {
            $this->_report['source'][$item->loan_source_id]['loans'] = $item->loans;
            $this->_report['source'][$item->loan_source_id]['amount'] = $item->amount;
        }
        
        $query3 = 'SELECT DISTINCT `l`.`loan_source_id`, COUNT(`l`.`id`) AS `loans`, '
                . 'SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan']
                . 'WHERE ' . $this->_where['active'] . $this->_groups['source'];
        
        $list3 = $this->_model->queryRows($query3);
        foreach ($list3 as $item) {
            $this->_report['source'][$item->loan_source_id]['active'] = $item->loans;
            $this->_report['source'][$item->loan_source_id]['active_amount'] = $item->amount;
        }
                        
        $query4 = 'SELECT DISTINCT `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans`, '
                . 'SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'];
        if (! empty($this->_where['all'])) {
            $query4 .= ' WHERE ' . $this->_where['all'];
        }
        $query4 .= $this->_groups['type'];
        $list4 = $this->_model->queryRows($query4);
        foreach ($list4 as $item) {
            $this->_report['type'][$item->loan_type_id]['loans'] = $item->loans;
            $this->_report['type'][$item->loan_type_id]['amount'] = $item->amount;
        }
        
        $query5 = 'SELECT DISTINCT `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans`, '
                . 'SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan']
                . 'WHERE ' . $this->_where['active'] . $this->_groups['type'];
        
        $list5 = $this->_model->queryRows($query5);
        foreach ($list5 as $item) {
            $this->_report['type'][$item->loan_type_id]['active'] = $item->loans;
            $this->_report['type'][$item->loan_type_id]['active_amount'] = $item->amount;
        }
    }
    
    private function _fmcReport()
    {       
        $query1 = 'SELECT `l`.`loan_source_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'] . $this->_joins['member']
                . 'WHERE ' . $this->_where['all'] . $this->_groups['source'];
        $list1 = $this->_model->queryRows($query1);
        foreach ($list1 as $item) {
            $this->_report['source'][$item->loan_source_id]['loans'] = $item->loans;
            $this->_report['source'][$item->loan_source_id]['amount'] = $item->amount;
        }
        
        $query2 = 'SELECT `l`.`loan_source_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'] . $this->_joins['member']
                . 'WHERE ' . $this->_where['active'] . $this->_groups['source'];
        $list2 = $this->_model->queryRows($query2);
        foreach ($list2 as $item) {
            $this->_report['source'][$item->loan_source_id]['active'] = $item->loans;
            $this->_report['source'][$item->loan_source_id]['active_amount'] = $item->amount;
        }
        
        $query3 = 'SELECT `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'] . $this->_joins['member']
                . 'WHERE ' . $this->_where['all'] . $this->_groups['type'];
        $list3 = $this->_model->queryRows($query3);
        foreach ($list3 as $item) {
            $this->_report['type'][$item->loan_type_id]['loans'] = $item->loans;
            $this->_report['type'][$item->loan_type_id]['amount'] = $item->amount;
        }
        
        $query4 = 'SELECT `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan'] . $this->_joins['member']
                . 'WHERE ' . $this->_where['active'] . $this->_groups['type'];
        $list4 = $this->_model->queryRows($query4);
        foreach ($list4 as $item) {
            $this->_report['type'][$item->loan_type_id]['active'] = $item->loans;
            $this->_report['type'][$item->loan_type_id]['active_amount'] = $item->amount;
        }
    }
    
    private function _loadIds()
    {
        $fmcIds = array_keys(LookupStore::get('fmc'));
        sort($fmcIds, SORT_NUMERIC);
        $this->_fmc = $fmcIds;
        
        $typeIds = array_keys(LookupStore::get('type'));
        sort($typeIds, SORT_NUMERIC);
        $this->_types = $typeIds;
        
        $sourceIds = array_keys(LookupStore::get('source'));
        sort($sourceIds, SORT_NUMERIC);
        $this->_sources = $sourceIds;
    }
    
    private function _print()
    {
        echo '<pre>';
        print_r($this->_report);
        exit;
    }
    
}
