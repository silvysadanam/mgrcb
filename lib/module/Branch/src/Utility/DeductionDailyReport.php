<?php
/**
 * Branch Deduction Daily Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;

use Model\{DeductionMemberDaily, AuctionDaily};
use Web\Utility\DateHelper;


class DeductionDailyReport
{
    /**
     * 
     * @var \Branch\Utility\BranchStore
     */
    private $_branch;
    
    /**
     *
     * @var \Model\DeductionMemberDaily 
     */
    private $_model;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_deductionDate = '';
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    public function __construct($branch)
    {
        $this->_branch = $branch;
        $this->_model = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * set start date
     * @param string $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->_deductionDate = $date;
        
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
        $this->_loadReport();
        if (0 == $this->_report['total']) {
            return false;
        }
        
        return true;
    }
    
    private function _loadReport()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereEqual('deduction_date', $this->_deductionDate);
        $where = $this->_sql->getWhere();
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }
        
        $this->_report['report'] = [];
        $this->_report['summary'] = [
            'deductions' => array_fill_keys(array_keys($this->_report['deductions']), 0),
            'members' => [],
        ];
        
        $query2 = 'SELECT DISTINCT `member_id`, `deduction_type_id`, '
                . 'SUM(`deduction_amount`) AS `deduction_amount` '
                . 'FROM ' . $this->_model->getTableQuoted() . ' WHERE ' . $where 
                . ' GROUP BY `member_id`, `deduction_type_id` ';
        $list = $this->_model->queryRows($query2);
        foreach ($list as $item) {
            if (! isset($this->_report['report'][$item->member_id])) {
                $this->_report['report'][$item->member_id] = [];
                $this->_report['summary']['members'][$item->member_id] = 0;
            }
            
            $this->_report['report'][$item->member_id][$item->deduction_type_id] = $item->deduction_amount;
            $this->_report['summary']['members'][$item->member_id] += $item->deduction_amount;
            $this->_report['summary']['deductions'][$item->deduction_type_id] += $item->deduction_amount;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', array_keys($this->_report['summary']['members']))
                   ->whereEqual('auction_date', $this->_deductionDate);
        $where2 = $this->_sql->getWhere();
        
        $auctionDailyModel = new AuctionDaily();
        
        $this->_report['catch'] = $auctionDailyModel->selectPairs(['member_id', 'amount'], $where2, 'member_id');
    }
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_report['filters'] = [
            'date' => $this->_deductionDate,
            'datepicker' => DateHelper::datepicker($this->_deductionDate),
        ];
        
        $date = DateHelper::getDateTime($this->_deductionDate);
        $this->_report['params'] = 'date=' . $date->format('Ymd');
        $this->_report['deductions'] = $this->_branch->deductionTypes();
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
