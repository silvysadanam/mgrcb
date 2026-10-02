<?php
/**
 * Backend Deduction Daily Report Utility
 * 
 * @category Backend
 * @package  Backend\Utility
 */

namespace Backend\Utility;

use Model\{DeductionMemberDaily, Member, Fmc, DeductionFmc, AuctionDaily};

use Shared\Utility\DeductionHelper;
use Shared\Constant\MemberType;
user \Shared\Constant\MembershipType;
use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;


class DeductionDailyReport
{
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
    private $_memberIds = [];
    
    public function __construct()
    {
        $this->_model = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * set date
     * @param string $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->_deductionDate = $date;
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
        $this->_sql->whereIn('member_id', $this->_memberIds)
                   ->whereEqual('deduction_date', $this->_deductionDate);
        $where = $this->_sql->getWhere();
        
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }
        
        $this->_report['summary'] = [
            'deductions' => array_fill_keys(array_keys($this->_report['deductions']), 0),
            'members' => [],
        ];
        
        $query2 = 'SELECT DISTINCT `member_id`, `deduction_type_id`, '
                . 'SUM(`deduction_amount`) AS `deduction_amount` '
                . 'FROM ' . $this->_model->getTableQuoted()
                . ' WHERE ' . $where . ' GROUP BY `member_id`, `deduction_type_id` ';
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
            'fmc_id' => $this->_fmcId,
            'date' => $this->_deductionDate,
            'datepicker' => DateHelper::datepicker($this->_deductionDate),
        ];
        
        $fmcModel = new Fmc();
        $this->_report['filters']['fmc'] = $fmcModel->selectColumnById('name', $this->_fmcId);
        
        $start = DateHelper::getDateTime($this->_deductionDate);
        
        $this->_report['params'] = 'fmc=' . $this->_fmcId . '&amp;date=' . $start->format('Ymd');
        
        $deductionFmcModel = new DeductionFmc();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId);
        
        $brachDeductionIds = $deductionFmcModel->selectColumns('deduction_type_id', $this->_sql->getWhere());
        $types = DeductionHelper::deductionTypes();
        $deductions = [];
        foreach ($types as $id => $type) {
            if (in_array($id, $brachDeductionIds)) {
                $deductions[$id] = $type;
            }
        }
        $this->_report['deductions'] = $deductions;
        
        $memberModel = new Member();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereId('membership_type_id', MembershipType::LIFEMEMBER);
        $this->_memberIds = $memberModel->selectIds($this->_sql->getWhere());
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_memberIds);
        $where = $this->_sql->getWhere();
        
        LookupStore::register('member_code', $memberModel->selectPairs(['id', 'code'], $where, 'code'));
        LookupStore::register('member_name', $memberModel->selectPairs(['id', 'name'], $where, 'id'));
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
