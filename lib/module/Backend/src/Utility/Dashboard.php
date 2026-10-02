<?php
/**
 * Backend Deduction Report Utility
 * 
 * @category Backend
 * @package  Backend\Utility
 */

namespace Backend\Utility;

use Model\{DeductionMonthly, Member, DeductionFmc, DeductionMemberDaily};
use Model\{AdvanceWithdrawal, AdvanceWithdrawalRepayment};
use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;
use Web\View\Support\LookupStore;


class Dashboard
{
    /**
     *
     * @var \Model\DeductionMonthly
     */
    private $_model;
    
    /**
     *
     * @var \Model\DeductionFmc
     */
    private $_deductionFmcModel;
    
    /**
     *
     * @var \Model\DeductionmemberDaily
     */
    private $_deductionMemberDailyModel;
    
    /**
     *
     * @var \Model\Member
     */
    private $_memberModel;
    
    /**
     *
     * @var \Model\AdvanceWithdrawal
     */
    private $_advanceWithdrawalModel;
    
    /**
     *
     * @var \Model\AdvanceWithdrawalRepayment
     */
    private $_advanceWithdrawalRepaymentModel;


    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_deductionTypes;
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    /**
     * 
     * @var \PDOStatement
     */
    private $_collection;
    
    /**
     * 
     * @var \PDOStatement
     */
    private $_members;
    
    /**
     * 
     * @var array
     */
    private $_memberIds;
    
    /**
     * 
     * @var int
     */
    private $_id;
    
    
    public function __construct()
    {
        $this->_model = new DeductionMonthly();
        $this->_deductionFmcModel = new DeductionFmc();
        $this->_deductionMemberDailyModel = new DeductionMemberDaily();
        $this->_advanceWithdrawalModel = new AdvanceWithdrawal();
        $this->_advanceWithdrawalRepaymentModel = new AdvanceWithdrawalRepayment();
        $this->_memberModel = new Member();
        
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * run report
     * 
     * @return boolean
     */
    public function run()
    {
        $this->_prepare();
        
        $fmc = LookupStore::get('fmc');
        $fmcIds = array_keys($fmc);
        foreach ($fmcIds as $id) {
            $this->_id = $id;
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $this->_id);
            $this->_where = $this->_sql->getWhere();
            if ($this->_deductionFmcModel->selectTotal($this->_where) > 0) {
                $this->_deductionTypes = $this->_deductionFmcModel->selectColumns('deduction_type_id', $this->_where);
                if ($this->_model->checkByColumnId('fmc_id', $this->_id)) {
                    $blank = array_fill_keys($this->_deductionTypes, 0);
                    $this->_report[$this->_id] = [
                        'month' => $this->_model->selectMax('deduction_date', $this->_where),
                        'date' => '',
                        'deductions' => $this->_deductionTypes,
                        'collection' => $blank,
                        'advance' => $blank,
                        'balance' => $blank,
                    ];
                    $this->_loadFmc();
                }
            }
        }
        
        return true;
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
     * load prepared statements
     */
    private function _prepare()
    {
        $table = $this->_model->getTableQuoted();
        $q1 = 'SELECT `deduction_type_id`, `closing_balance` FROM ' . $table
            . ' WHERE `fmc_id` =? AND `deduction_date` =?';
        $this->_collection = $this->_model->prepare($q1);
        
        $q2 = 'SELECT `id` FROM ' . $this->_memberModel->getTableQuoted()
            . ' WHERE `fmc_id` =? AND `member_type_id` =?';
        $this->_members = $this->_model->prepare($q2);
    }
    
    /**
     * load fmc report
     */
    private function _loadFmc()
    {
        $this->_loadMembers();
        $this->_report[$this->_id]['date'] = $this->_getDate();
        $this->_loadCollection();
        $this->_loadAdvances();
    }
    
    /**
     * load fmc members
     */
    private function _loadMembers()
    {
        $this->_members->execute([$this->_id, MembershipType::LIFEMEMBER]);
        $this->_memberIds = $this->_members->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    /**
     * get report date
     * 
     * @return string
     */
    private function _getDate()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_memberIds);
        
        return $this->_deductionMemberDailyModel->selectMax('deduction_date', $this->_sql->getWhere());
    }
    
    /**
     * load collection
     */
    private function _loadCollection()
    {
        $this->_collection->execute([$this->_id, $this->_report[$this->_id]['month']]);
        $collection = $this->_collection->fetchAll(\PDO::FETCH_KEY_PAIR);
        foreach ($collection as $type => $value) {
            $this->_report[$this->_id]['collection'][$type] = $value;
            $this->_report[$this->_id]['balance'][$type] = $value;
        }
    }
    
    /**
     * load advances
     */
    private function _loadAdvances()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_memberIds)
                   ->whereIn('deduction_type_id', $this->_deductionTypes);
        $where = $this->_sql->getWhere();
        if ($this->_advanceWithdrawalModel->selectTotal($where) > 0) {
            $query = 'SELECT `deduction_type_id`, SUM(`advance_amount`) FROM '
                   . $this->_advanceWithdrawalModel->getTableQuoted() . ' WHERE ' . $where
                   . ' GROUP BY `deduction_type_id`';
            $list = $this->_model->queryPairs($query);
            foreach ($list as $type => $advance) {
                $this->_report[$this->_id]['advance'][$type] = $advance;
                $this->_report[$this->_id]['balance'][$type] -= $advance;
            }
            
            $this->_loadAdjustments(array_keys($list));
        }
    }
    
    /**
     * load adjustments / repayments
     * 
     * @param array $deductions
     */
    private function _loadAdjustments($deductions)
    {
        $query = 'SELECT `a`.`deduction_type_id`, SUM(`r`.`amount`) FROM '
               . $this->_advanceWithdrawalRepaymentModel->getTableQuoted() . ' `r` '
               . 'INNER JOIN ' . $this->_advanceWithdrawalModel->getTableQuoted() . ' `a` '
               . 'ON `a`.`id` = `r`.`advance_withdrawal_id` WHERE ';
        $group = ' GROUP BY `a`.`deduction_type_id`';
        $this->_sql->clear();
        $this->_sql->whereIn('a.member_id', $this->_memberIds)
                   ->whereIn('a.deduction_type_id', $deductions)
                   ->whereZero('r.manual_entry');
        $where1 = $this->_sql->getWhere();

        $autoCollection = $this->_model->queryPairs($query . $where1 . $group);
        if (! empty($autoCollection)) {
            foreach ($autoCollection as $type => $value) {
                $this->_report[$this->_id]['balance'][$type] -= $value;
            }
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('a.member_id', $this->_memberIds)
                   ->whereIn('a.deduction_type_id', $deductions)
                   ->whereOne('r.manual_entry');
        $where2 = $this->_sql->getWhere();
        $manualCollection = $this->_model->queryPairs($query . $where2 . $group);
        if (! empty($manualCollection)) {
            foreach ($manualCollection as $typeId => $amount) {
                $this->_report[$this->_id]['balance'][$typeId] += $amount;
            }
        }
    }
    
}
