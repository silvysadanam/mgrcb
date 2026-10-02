<?php
/**
 * Shared DeductionSupport Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{AuctionChit, AuctionDaily, Deduction, DeductionDaily, DeductionInitial};
use Model\{DeductionMemberDaily, DeductionMemberMonthly, DeductionMonthly, InitialDeductionBalance};
use Model\{Member, AdvanceWithdrawal, AdvanceWithdrawalRepayment};

use Web\Utility\DateHelper;


class DeductionSupport
{
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \stdClass
     */
    private $_auctionChit;
    
    /**
     * 
     * @var \Model\Deduction
     */
    private $_deductionModel;
    
    /**
     * 
     * @var \Model\DeductionMemberDaily
     */
    private $_deductionMemberDailyModel;
    
    /**
     * 
     * @var \Model\DeductionMemberMonthly
     */
    private $_deductionMemberMonthlyModel;
    
    /**
     * 
     * @var \Model\DeductionDaily
     */
    private $_deductionDailyModel;
    
    /**
     * 
     * @var \Model\DeductionMonthly
     */
    private $_deductionMonthlyModel;
    
    /**
     * 
     * @var \Model\DeductionInitial
     */
    private $_deductionInitialModel;
    
    /**
     * 
     * @var \Model\InitialDeductionBalance
     */
    private $_initialDeductionBalanceModel;
    
    /**
     * 
     * @var \Model\AdvanceWithdrawal
     */
    private $_advanceModel;
    
    /**
     * 
     * @var \Model\AdvanceWithdrawalRepayment
     */
    private $_advanceRepaymentModel;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_deductions;
    
    private $_openingBalance = [];
    
     private $_fmcBalance = [];
    
    private $_monthlyDate;
    
    private $_year;
    
    private $_month;
    
    private $_memberId;
    
    private $_auctionDate;
    
    private $_fmcId;
    
    private $_autoRepayment = true;
    
    private $_collectionBalance = [];
    
    private $_collected = [];
    
    private $_hasAdvance = false;
    
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_model = new AuctionChit();
        $this->_auctionChit = $this->_model->find($this->_id);
        $this->_memberId = $this->_auctionChit->member_id;
        $this->_auctionDate = $this->_auctionChit->auction_date;
        
        $this->_deductionModel = new Deduction();
        $this->_deductionMemberDailyModel = new DeductionMemberDaily();
        $this->_deductionMemberMonthlyModel = new DeductionMemberMonthly();
        $this->_deductionDailyModel = new DeductionDaily();
        $this->_deductionInitialModel = new DeductionInitial();
        $this->_deductionMonthlyModel = new DeductionMonthly();
        $this->_initialDeductionBalanceModel = new InitialDeductionBalance();
        $this->_advanceModel = new AdvanceWithdrawal();
        $this->_advanceRepaymentModel = new AdvanceWithdrawalRepayment();
        
        $this->_sql = $this->_model->getSql();
        
        $memberModel = new Member();
        $this->_fmcId = $memberModel->selectColumnById('fmc_id', $this->_memberId);
        $this->_loadDeductionDate();
        $this->_loadAdvanceStatus();
    }
    
    public function created()
    {
        $this->_auctionDaily();
        $this->_sql->clear();
        $this->_sql->whereId('auction_chit_id', $this->_id)
                   ->whereGreater('deduction_amount', 0);
        $this->_deductions = $this->_deductionModel->selectPairs(['deduction_type_id', 'deduction_amount'], $this->_sql->getWhere());
        
        $this->_memberDailyDeduction();
        $this->_memberMonthlyDeduction();
        $this->_fmcDeduction();
    }
    
    private function _auctionDaily()
    {
        $auctionDailyModel = new AuctionDaily();
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if ($auctionDailyModel->selectTotal($where) > 0) {
            $current = $auctionDailyModel->selectRow($where);
            $update = [
                'updated_at' => DateHelper::timestamp(),
            ];
            //$update['entries'] = $current->entries + 1;
            //$update['amount'] = $current->amount + $this->_auctionChit->amount;
            //$update['quantity'] = $current->quantity + $this->_auctionChit->quantity;
            //$update['advance'] = $current->advance + $this->_auctionChit->advance;
            //$update['balance'] = $current->balance + $this->_auctionChit->balance;
            $update['member_paid'] = $current->member_paid + $this->_auctionChit->member_paid_amount;
            $update['deduction_amount'] = $current->deduction_amount + $this->_auctionChit->deduction_amount;
            $update['deduction_balance'] = $current->deduction_balance + $this->_auctionChit->deduction_balance;
            
            return $auctionDailyModel->updateById($update, $current->id);
        }
        
        $insert = [
            'member_id' => $this->_memberId,
            'auction_date' => $this->_auctionDate,
            'entries' => 1,
            'amount' => $this->_auctionChit->amount,
            'quantity' => $this->_auctionChit->quantity,
            'advance' => $this->_auctionChit->advance,
            'balance' => $this->_auctionChit->balance,
            'updated_at' => DateHelper::timestamp(),
            'member_paid' => $this->_auctionChit->member_paid_amount,
            'deduction_amount' => $this->_auctionChit->deduction_amount,
            'deduction_balance' => $this->_auctionChit->deduction_balance,
        ];
        
        $auctionDailyModel->insert($insert);
    }
    
    private function _memberDailyDeduction()
    {
        $current = [];
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('deduction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMemberDailyModel->selectTotal($where) > 0) {
            $current = $this->_deductionMemberDailyModel->selectIndexed($where, null, null, 'deduction_type_id');
        }
        
        $forwardUpdate = $this->_getForwardUpdate($this->_deductionMemberDailyModel->getTableQuoted());
        
        foreach ($this->_deductions as $type => $amount) {
            $this->_collected[$type] = 0;
            $this->_collectionBalance[$type] = $amount;
            if ($this->_autoRepayment) {
                if ($this->_hasAdvance) {
                    $collected = $this->_collectAdvance($type, $amount);
                    if ($collected > 0) {
                        $this->_collected[$type] = $collected;
                        $this->_collectionBalance[$type] = $amount - $collected;
                    }
                }
            }
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount + $amount,
                    'closing_balance' => $current[$type]->closing_balance + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => $current[$type]->entries + 1,
                ];
                
                if ($this->_collected[$type] > 0) {
                    $update['repayment'] = $current[$type]->repayment + $this->_collected[$type];
                    $update['closing_balance'] = $current[$type]->closing_balance + $this->_collectionBalance[$type];
                    $update['adjustment'] = $current[$type]->adjustment - $this->_collected[$type];
                }
                $this->_deductionMemberDailyModel->updateById($update, $current[$type]->id);
            } else {
                $opening = $this->_getOpeningBalance($type);
                $insert = [
                    'member_id' => $this->_memberId,
                    'deduction_date' => $this->_auctionDate,
                    'deduction_type_id' => $type,
                    'opening_balance' => $opening,
                    'deduction_amount' => $amount,
                    'closing_balance' => $opening + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => 1,
                ];
                if ($this->_collected[$type] > 0) {
                    $insert['repayment'] = $this->_collected[$type];
                    $insert['closing_balance'] = $opening + $this->_collectionBalance[$type];
                    $insert['adjustment'] = -1 * $this->_collected[$type];
                }
                $this->_deductionMemberDailyModel->insert($insert);
            }
            
            if ($this->_collectionBalance[$type] > 0) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $this->_memberId)
                           ->whereId('deduction_type_id', $type)
                           ->whereGreater('deduction_date', $this->_auctionDate);
                $whereForward = $this->_sql->getWhere();
                if ($this->_deductionMemberDailyModel->selectTotal($whereForward) > 0) {
                    $query = str_replace('[A]', $this->_collectionBalance[$type], $forwardUpdate) . $whereForward;
                    $this->_deductionMemberDailyModel->exec($query);
                }
            }
        }
    }
    
    private function _memberMonthlyDeduction()
    {
        $current = [];
        $forwardUpdate = $this->_getForwardUpdate($this->_deductionMemberMonthlyModel->getTableQuoted());
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('deduction_date', $this->_monthlyDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMemberMonthlyModel->selectTotal($where) > 0) {
            $current = $this->_deductionMemberMonthlyModel->selectIndexed($where, null, null, 'deduction_type_id');
        }
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount + $amount,
                    'closing_balance' => $current[$type]->closing_balance + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => $current[$type]->entries + 1,
                ];
                if ($this->_collected[$type] > 0) {
                    $update['repayment'] = $current[$type]->repayment + $this->_collected[$type];
                    $update['closing_balance'] = $current[$type]->closing_balance + $this->_collectionBalance[$type];
                    $update['adjustment'] = $current[$type]->adjustment - $this->_collected[$type];
                }
                $this->_deductionMemberMonthlyModel->updateById($update, $current[$type]->id);
            } else {
                $insert = [
                    'member_id' => $this->_memberId,
                    'deduction_date' => $this->_monthlyDate,
                    'deduction_year' => $this->_year,
                    'deduction_month' => $this->_month,
                    'entries' => 1,
                    'updated_at' => DateHelper::timestamp(),
                    'deduction_type_id' => $type,
                    'deduction_amount' => $amount,
                    'opening_balance' => 0,
                ];
                
                if (isset($this->_openingBalance[$type])) {
                    $insert['opening_balance'] = $this->_openingBalance[$type];
                } else {
                    $this->_sql->clear();
                    $this->_sql->whereId('member_id', $this->_memberId)
                           ->whereLess('deduction_date', $this->_monthlyDate);
                    $where2 = $this->_sql->getWhere();
                    if ($this->_deductionMemberMonthlyModel->selectTotal($where2) > 0) {
                        $insert['opening_balance'] = $this->_deductionMemberMonthlyModel->selectColumn('closing_balance', $where2, 'deduction_date DESC');
                    }
                }
                
                $insert['closing_balance'] = $insert['opening_balance'] + $amount;
                if ($this->_collected[$type] > 0) {
                    $insert['repayment'] = $this->_collected[$type];
                    $insert['closing_balance'] = $insert['opening_balance'] + $this->_collectionBalance[$type];
                    $insert['adjustment'] = -1 * $this->_collected[$type];
                }
                $this->_deductionMemberMonthlyModel->insert($insert);
            }
            
            if ($this->_collectionBalance[$type] > 0) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $this->_memberId)
                           ->whereId('deduction_type_id', $type)
                           ->whereGreater('deduction_date', $this->_monthlyDate);
                $whereForward = $this->_sql->getWhere();
                if ($this->_deductionMemberMonthlyModel->selectTotal($whereForward) > 0) {
                    $query = str_replace('[A]', $amount, $forwardUpdate) . $whereForward;
                    $this->_deductionMemberMonthlyModel->exec($query);
                }
            }
        }
    }
    
    private function _getOpeningBalance($deductionTypeId)
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $deductionTypeId)
                   ->whereLess('deduction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMemberDailyModel->selectTotal($where) > 0) {
            return $this->_deductionMemberDailyModel->selectColumn('closing_balance', $where, 'deduction_date DESC');
        }
        
        $this->_openingBalance[$deductionTypeId] = 0;
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $deductionTypeId);
        
        $whereInitial = $this->_sql->getWhere();
        
        if ($this->_initialDeductionBalanceModel->selectTotal($whereInitial) > 0) {
            $this->_openingBalance[$deductionTypeId] = $this->_initialDeductionBalanceModel->selectColumn('opening_balance', $whereInitial);
        }
        
        return $this->_openingBalance[$deductionTypeId];
    }
    
    private function _fmcDeduction()
    {
        $this->_fmcDaily();
        $this->_fmcMonthly();
    }
            
    private function _fmcDaily()
    {
        $current = [];
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('deduction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionDailyModel->selectTotal($where) > 0) {
            $current = $this->_deductionDailyModel->selectIndexed($where, null, null, 'deduction_type_id');
        }
        
        $forwardUpdate = $this->_getForwardUpdate($this->_deductionDailyModel->getTableQuoted());
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount + $amount,
                    'closing_balance' => $current[$type]->closing_balance + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => $current[$type]->entries + 1,
                ];
                $this->_deductionDailyModel->updateById($update, $current[$type]->id);
            } else {
                $opening = $this->_getFmcOpeningBalance($type);
                $insert = [
                    'fmc_id' => $this->_fmcId,
                    'deduction_date' => $this->_auctionDate,
                    'deduction_type_id' => $type,
                    'opening_balance' => $opening,
                    'deduction_amount' => $amount,
                    'closing_balance' => $opening + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => 1,
                ];
                $this->_deductionDailyModel->insert($insert);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $this->_fmcId)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_auctionDate);
            $whereForward = $this->_sql->getWhere();
            if ($this->_deductionDailyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forwardUpdate) . $whereForward;
                $this->_deductionDailyModel->exec($query);
            }
        }
    }
    
    private function _fmcMonthly()
    {
        $current = [];
        $forwardUpdate = $this->_getForwardUpdate($this->_deductionMonthlyModel->getTableQuoted());
        
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('deduction_date', $this->_monthlyDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMonthlyModel->selectTotal($where) > 0) {
            $current = $this->_deductionMonthlyModel->selectIndexed($where, null, null, 'deduction_type_id');
        }
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount + $amount,
                    'closing_balance' => $current[$type]->closing_balance + $amount,
                    'updated_at' => DateHelper::timestamp(),
                    'entries' => $current[$type]->entries + 1,
                ];
                $this->_deductionMonthlyModel->updateById($update, $current[$type]->id);
            } else {
                $insert = [
                    'fmc_id' => $this->_fmcId,
                    'deduction_date' => $this->_monthlyDate,
                    'deduction_year' => $this->_year,
                    'deduction_month' => $this->_month,
                    'entries' => 1,
                    'updated_at' => DateHelper::timestamp(),
                    'deduction_type_id' => $type,
                    'deduction_amount' => $amount,
                    'opening_balance' => 0,
                ];
                
                if (isset($this->_fmcBalance[$type])) {
                    $insert['opening_balance'] = $this->_fmcBalance[$type];
                } else {
                    $this->_sql->clear();
                    $this->_sql->whereId('fmc_id', $this->_fmcId)
                           ->whereLess('deduction_date', $this->_monthlyDate);
                    $where2 = $this->_sql->getWhere();
                    if ($this->_deductionMonthlyModel->selectTotal($where2) > 0) {
                        $insert['opening_balance'] = $this->_deductionMonthlyModel->selectColumn('closing_balance', $where2, 'deduction_date DESC');
                    }
                }
                
                $insert['closing_balance'] = $insert['opening_balance'] + $amount;
                $this->_deductionMonthlyModel->insert($insert);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $this->_fmcId)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_monthlyDate);
            $whereForward = $this->_sql->getWhere();
            if ($this->_deductionMonthlyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forwardUpdate) . $whereForward;
                $this->_deductionMonthlyModel->exec($query);
            }
        }
    }
    
    private function _getFmcOpeningBalance($deductionTypeId)
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereId('deduction_type_id', $deductionTypeId)
                   ->whereLess('deduction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionDailyModel->selectTotal($where) > 0) {
            return $this->_deductionDailyModel->selectColumn('closing_balance', $where, 'deduction_date DESC');
        }
        
        $this->_fmcBalance[$deductionTypeId] = 0;
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereId('deduction_type_id', $deductionTypeId);
        
        $whereInitial = $this->_sql->getWhere();
        
        if ($this->_deductionInitialModel->selectTotal($whereInitial) > 0) {
            $this->_fmcBalance[$deductionTypeId] = $this->_deductionInitialModel->selectColumn('opening_balance', $whereInitial);
        }
        
        return $this->_fmcBalance[$deductionTypeId];
    }
    
    private function _loadDeductionDate()
    {
        $date = DateHelper::getDateTime($this->_auctionDate);
        $this->_year = $date->format('Y');
        $this->_month = $date->format('n');
        $days = $date->format('t');
        
        $date->setDate($this->_year, $this->_month, $days);
        $this->_monthlyDate = $date->format(DateHelper::MYSQL_DATE);
    }
    
    private function _loadAdvanceStatus()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereLess('advance_date', $this->_auctionDate)
                   ->whereZero('closed')
                   ->whereZero('withdrawal')
                   ->whereGreater('overdue_balance', 0);
        
        $this->_hasAdvance = $this->_advanceModel->selectTotal($this->_sql->getWhere()) > 0;
    }
    
    private function _getForwardUpdate($table)
    {
        $query = 'UPDATE ' . $table . ' SET `opening_balance` = `opening_balance` + \'[A]\', '
               . '`closing_balance` = `closing_balance` + \'[A]\' WHERE ';
        
        return $query;
    }
    
    /**
     * collect advance
     * 
     * @return null
     */
    private function _collectAdvance($type, $amount)
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $type)
                   ->whereLess('advance_date', $this->_auctionDate)
                   ->whereZero('closed')
                   ->whereZero('withdrawal')
                   ->whereGreater('overdue_balance', 0);
        $where = $this->_sql->getWhere();
        if (0 == $this->_advanceModel->selectTotal($where)) {
            return 0;
        }
        
        $advance = $this->_advanceModel->selectRow($where, 'advance_date');
        
        $this->_sql->clear();
        $this->_sql->whereId('advance_withdrawal_id', $advance->id)
                   ->whereGreater('deduction_bill_date', $this->_auctionDate, true);
        if ($this->_advanceRepaymentModel->selectTotal($this->_sql->getWhere()) > 0) {
            return 0;
        }
        
        if ($advance->overdue_balance <= $amount) {
            $collected = $advance->overdue_balance;
        } else {
            $collected = $amount;
        }
        
        $repayment = [
            'advance_withdrawal_id' => $advance->id,
            'amount' => $collected,
            'payment_date' => $this->_auctionDate,
            'overdue_repayment' => $collected,
            'deduction_bill_date' => $this->_auctionDate,
            'adjusted' => 1,
            'note' => 'Overdue collection auto adjusted from Deduction Bill entry',
            'deduction_bill_adjusted' => 1,
        ];
        
        $this->_advanceRepaymentModel->insert($repayment);
        
        $update = [
            'repaid' => $advance->repaid + $collected,
            'balance' => $advance->balance - $collected,
            'overdue_balance' => $advance->overdue_balance - $collected,
        ];
        
        if (0 == $update['overdue_balance']) {
            $update['closed'] = 1;
            $update['closing_date'] = $this->_auctionDate;
        }
        
        $this->_advanceModel->updateById($update, $advance->id);
        
        return $collected;
    }
    
}
