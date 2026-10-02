<?php
/**
 * Application LoanTotal Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Loan, Member, LoanSummary};

use Application\ViewHelper\Currency;
use Application\Utility\LoanHelper;

use Shared\Model\PairModel;

use Web\Utility\DateHelper;


class LoanTotalReport
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
     * @var \Model\LoanSummary 
     */
    private $_loanSummaryModel;

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
    private $_where = [];
    
    /**
     * 
     * @var \Application\ViewHelper\Currency
     */
    private $_currency;
    
    /**
     * 
     * @var int
     */
    private $_sourceId = 0;
    
    /**
     * 
     * @var int
     */
    private $_typeId = 0;
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_memberModel = new Member();
        $this->_sql = $this->_model->getSql();
        $this->_currency = new Currency();
        $this->_loanSummaryModel = new LoanSummary();
    }
    
    /**
     * set start date
     * @param string $date
     * @return $this
     */
    public function setStartDate($date)
    {
        $this->_startDate = $date;
        return $this;
    }
    
    /**
     * set emd date
     * 
     * @param string $date
     * @return $this
     */
    public function setEndDate($date)
    {
        $this->_endDate = $date;
        return $this;
    }
    
    /**
     * set fmc id
     * 
     * @param int $id
     * @return $this
     */
    public function setFmcId($id)
    {
        $this->_fmcId = $id;
        return $this;
    }
    
    /**
     * set source id
     * 
     * @param int $id
     * @return $this
     */
    public function setSourceId($id)
    {
        $this->_sourceId = $id;
        return $this;
    }
    
    /**
     * set type id
     * 
     * @param int $id
     * @return $this
     */
    public function setTypeId($id)
    {
        $this->_typeId = $id;
        return $this;
    }
    
    /**
     * get report
     * 
     * @return array
     */
    public function report()
    {
        return $this->_report;
    }
    
    /**
     * run report
     * 
     * @return boolean
     */
    public function run()
    {
        $this->_preload();
        $this->_loadTotal();
        if (0 == $this->_report['total']['loans']) {
            return false;
        }

        $this->_loadReport();
        
        return true;
    }
    
    /**
     * format amount
     * 
     * @param float $amount
     * @param boolean $mask
     * @return string
     */
    public function amount($amount, $mask = false)
    {
        if ($amount > 0.00) {
            return $this->_currency->currency($amount, 0);
        }
        
        if ($amount < 0.00) {
            return '-' . $this->_currency->currency(abs($amount), 0);
        }
        
        if ($mask) {
            return '--';
        }
        
        return '0';
    }
    
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_report['filters'] = [
            'fmc' => $this->_fmcId,
            'start' => $this->_startDate,
            'end' => $this->_endDate,
            'source' => $this->_sourceId,
            'type' => $this->_typeId,
        ];
        
        $this->_report['params'] = '';
        
        $pattern = '%s `%s` ';
        $this->_tables['loan'] = sprintf($pattern, $this->_model->getTableQuoted(), 'l');
        $this->_tables['member'] = sprintf($pattern, $this->_memberModel->getTableQuoted(), 'm');
        $this->_tables['summary'] = sprintf($pattern, $this->_loanSummaryModel->getTableQuoted(), 's');
        
        $this->_joins['member'] = 'INNER JOIN ' . $this->_tables['member'] . ' ON `m`.`id` = `l`.`member_id` ';
                //sprintf($join, $this->_tables['member'], 'm', 'l', 'member');
        $this->_joins['summary'] = 'INNER JOIN ' . $this->_tables['summary'] . ' ON `s`.`loan_id` = `l`.`id` ';
        $this->_default['default'] = [
            'loans' => 0, 'amount' => 0, 'principal_paid' => 0, 'interest_paid' => 0,
            'total_paid' => 0, 'overdue' => 0, 'scheduled_principal_outstanding' => 0,
            'actual_principal_outstanding' => 0,
            'actual_principal_interest_outstanding' => 0, 'par' => 0, 'members' => 0,
        ];
        
        $this->_default['direct'] = [
            'loans', 'amount', 'overdue', 'scheduled_principal_outstanding', 
            'actual_principal_outstanding', 'actual_principal_interest_outstanding', 'par',
        ];
        
        $this->_default['sum'] = [
            'principal_paid' => [
                'principal_arrears', 'current_principal', 'prepayment',
            ],
            'interest_paid' => [
                'penal_interest', 'interest_arrears', 'current_interest',
            ],
        ];
        
        $this->_default['total_paid'] = ['principal_paid', 'interest_paid'];
        
        $query = 'SELECT COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount`, '
               . 'SUM(`s`.`collection_penal_interest`) AS `penal_interest`, '
               . 'SUM(`s`.`collection_interest_arrears`) AS `interest_arrears`, '
               . 'SUM(`s`.`collection_current_interest`) AS `current_interest`, '
               . 'SUM(`s`.`collection_principal_arrears`) AS `principal_arrears`, '
               . 'SUM(`s`.`collection_current_principal`) AS `current_principal`, '
               . 'SUM(`s`.`collection_prepayment`) AS `prepayment`, '
               . 'SUM(`s`.`balance_total_overdue`) AS `overdue`, '
               . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled_principal_outstanding`, '
               . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `actual_principal_outstanding`, '
               . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `actual_principal_interest_outstanding`, '
               . 'SUM(`s`.`par`) AS `par` '
               . 'FROM ' . $this->_tables['loan'] . $this->_joins['summary'];
        
        /*
        if ($this->_fmcId > 0) {
            $query .= $this->_joins['member'];
        }
        */
        
        $this->_default['query'] = $query;
        
        $query2 = 'SELECT COUNT(DISTINCT `l`.`member_id`) AS `members` '
               . 'FROM ' . $this->_tables['loan'] . $this->_joins['summary'];
        
        if ($this->_fmcId > 0) {
            $query .= $this->_joins['member'];
        }
        
        $this->_default['member'] = $query2;
    }
    
    /**
     * load total
     */
    private function _loadTotal()
    {
        $this->_sql->whereNotIn('l.loan_status_id', [LoanHelper::STATUS_REFINANCED, LoanHelper::STATUS_RESCHEDULED]);
        $queryParams = ['fmc' => $this->_fmcId];
        if ('' != $this->_startDate || '' != $this->_endDate) {
            if ('' != $this->_startDate && '' != $this->_endDate) {
                if ($this->_startDate == $this->_endDate) {
                    $this->_sql->whereEqual('l.loan_date', $this->_startDate);
                    $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
                } else {
                    $this->_sql->whereBetween('l.loan_date', $this->_startDate, $this->_endDate);
                    $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
                    $queryParams['end'] = DateHelper::format('Ymd', $this->_endDate);
                }
            } elseif ('' != $this->_startDate) {
                $this->_sql->whereGreater('l.loan_date', $this->_startDate, true);
                $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
            } else {
                $this->_sql->whereLess('l.loan_date', $this->_endDate, true);
                $queryParams['end'] = DateHelper::format('Ymd', $this->_endDate);
            }
        }
        
        if ($this->_fmcId > 0) {
            $sql = $this->_memberModel->getSql();
            $sql->whereId('fmc_id', $this->_fmcId);
            $memberIds = $this->_memberModel->selectIds($sql->getWhere());
            $this->_sql->whereIn('l.member_id', $memberIds);
            //$this->_sql->whereId('m.fmc_id', $this->_fmcId);
        }
        
        if ($this->_sourceId > 0) {
            $this->_sql->whereId('l.loan_source_id', $this->_sourceId);
            $queryParams['source'] = $this->_sourceId;
        }
        
        if ($this->_typeId > 0) {
            $this->_sql->whereId('l.loan_type_id', $this->_typeId);
            $queryParams['type'] = $this->_sourceId;
        }
        
        $this->_where['all'] = $this->_sql->getWhere();
        $this->_sql->whereZero('l.closed');
        $this->_where['active'] = $this->_sql->getWhere();
        $this->_sql->clear();
        if (! empty($this->_where['all'])) {
            $this->_sql->where($this->_where['all']);
        }
        $this->_sql->whereOne('l.closed');
        $this->_where['closed'] = $this->_sql->getWhere();
        
        $query1 = 'SELECT COUNT(`l`.`id`) AS `total`, SUM(`l`.`loan_amount`) AS `amount`, '
                . 'COUNT(DISTINCT `l`.`member_id`) AS `members` '
                . 'FROM ' . $this->_tables['loan'];
        if ($this->_fmcId > 0) {
            //$query1 .= $this->_joins['member'];
        }
        
        if (! empty($this->_where['all'])) {
            $query1 .= 'WHERE ' . $this->_where['all'];
        }
        
        $total = $this->_model->queryRow($query1);
        $this->_report['total'] = [
            'loans' => $total->total,
            'amount' => $total->amount,
            'members' => $total->members,
            'closed' => 0,
        ];
        
        $this->_sql->clear();
        $query2 = 'SELECT COUNT(DISTINCT `l`.`member_id`) AS `members` '
                . 'FROM ' . $this->_tables['loan'];
        
        if ($this->_fmcId > 0) {
           // $query2 .= $this->_joins['member'];
        }
        
        $query2 .= 'WHERE ' . $this->_where['closed'];
        $this->_report['total']['closed'] = $this->_model->queryTotal($query2);
        
        $this->_report['summary'] = $this->_default['default'];
        
        $this->_report['params'] = '/?' . http_build_query($queryParams);
    }
    
    /**
     * load report
     */
    private function _loadReport()
    {
        $pairModel = new PairModel();
        $titles =  $pairModel->loanStatus();
        $this->_report['status_list'] = [ 0 => 'Active' ];
        $this->_report['status'] = [
            0 => $this->_default['default'],
        ];
        
        /*
        $status = [
            LoanHelper::STATUS_WRITTEN_OFF, LoanHelper::STATUS_REFINANCED,
            LoanHelper::STATUS_RESCHEDULED, LoanHelper::STATUS_DELINQUENT,
            LoanHelper::STATUS_PRECLOSED, LoanHelper::STATUS_INACTIVE,
        ];
         * 
         */
        
        $status = [
            LoanHelper::STATUS_WRITTEN_OFF, LoanHelper::STATUS_DELINQUENT,
            LoanHelper::STATUS_PRECLOSED, LoanHelper::STATUS_INACTIVE,
        ];
        
        foreach ($status as $item) {
            $this->_report['status'][$item] = $this->_default['default'];
            $this->_report['status_list'][$item] = $titles[$item];
        }
        
        $this->_getStatusTotal(0, $this->_where['active']);
        
        foreach ($status as $key) {
            $this->_sql->clear();
            $this->_sql->where($this->_where['closed'])
                       ->whereId('l.loan_status_id', $key);
            $this->_getStatusTotal($key, $this->_sql->getWhere());
        }
        
        $this->_report['status_list'][7] = 'Closed at end of tenure';
        $this->_report['status'][7] = $this->_default['default'];
        $this->_sql->clear();
        $this->_sql->where($this->_where['closed'])
                   ->whereZero('l.loan_status_id');
        $this->_getStatusTotal($key, $this->_sql->getWhere());
    }
    
    /**
     * get status total
     * 
     * @param int $status
     * @param string $where
     * @return null
     */
    private function _getStatusTotal($status, $where)
    {  
        $query = $this->_default['query'] . ' WHERE ' . $where;
        $summary = $this->_model->queryRow($query);
        if (0 == $summary->loans) {
            return;
        }
        
        foreach ($this->_default['direct'] as $direct) {
            $this->_report['status'][$status][$direct] = $summary->{$direct};
            $this->_report['summary'][$direct] += $summary->{$direct};
        }
        
        foreach ($this->_default['sum'] as $field => $items) {
            foreach ($items as $item) {
                $this->_report['status'][$status][$field] += $summary->{$item};
            }
            $this->_report['summary'][$field] += $this->_report['status'][$status][$field];
        }
        
        foreach ($this->_default['total_paid'] as $key) {
            $this->_report['status'][$status]['total_paid'] += $this->_report['status'][$status][$key];
            $this->_report['summary']['total_paid'] += $this->_report['status'][$status][$key];
        }
        
        $query2 = $this->_default['member'] . ' WHERE ' . $where;
        $members = $this->_model->queryTotal($query2);
        $this->_report['status'][$status]['members'] = $members;
        $this->_report['summary']['members'] += $members;
    }
    
    /*
     * print report
     */
    private function _print()
    {
        echo '<pre>';
        print_r($this->_report);
        exit;
    }
    
}
