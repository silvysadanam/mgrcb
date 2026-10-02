<?php
/**
 * ControlCenter Auction Remove controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;

use Model\{AuctionChit, Member, AuctionDaily, Deduction};
use Model\{DeductionMemberDaily, DeductionMemberMonthly};
use Model\{DeductionDaily, DeductionMonthly};
//use Model\{AuctioneerDaily, AuctioneerMonthly, AuctioneerAccountDaily, AuctioneerAccountMonthly};

use Shared\Utility\AuctionSummary;


class AuctionRemoveController extends ControlCenterController
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \stdclass
     */
    private $_auctionChit;
    
    private $_member;
    
    private $_fmc;
    
    private $_date;
    
    private $_deductions;
    
    private $_forwardUpdate;
    
    private $_single = true;
    
    private $_monthlyDate;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
        $this->_model = new AuctionChit();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->stop();
    }
    
    /**
     * run action
     * 
     * @return \Web\View\ViewModel
     */
    public function runAction()
    {
        $this->start('ControlCenter / Auction Remove / run');
        exit;
        $id = 38232;
        $this->_printLine('Auction Chit ID: ' . $id);
        if (! $this->_model->hasId($id)) {
            $this->_printLine('No auction chit');
            $this->finish();
        }
        
        $this->_auctionChit = $this->_model->find($id);
        $this->_preload();
        if ($this->_auctionChit->has_deduction_bill > 0) {
            $this->_printLine('Has deduction bill');
            try {
                $this->_model->getAdapter()->beginTransaction();
                $this->_resetAuctionChit();
                $this->_auctionDaily();
                $this->_memberDaily();
                $this->_memberMonthly();
                $this->_fmcDaily();
                $this->_fmcMonthly();
                $this->_model->getAdapter()->commit();
            } catch (\PDOException $ex) {
                $this->_model->getAdapter()->rollBack();
                echo $ex->getMessage();
                echo $ex->getTraceAsString();
            } catch (\Web\Exception $ex) {
                $this->_model->getAdapter()->rollBack();
                echo $ex->getMessage();
                echo $ex->getTraceAsString();
            } catch (\ErrorException $ex) {
                $this->_model->getAdapter()->rollBack();
                echo $ex->getMessage();
                echo $ex->getTraceAsString();
            }
        } else {
            $this->_printLine('No deduction bill');
        }
        
         $this->_printLine('Deleting auction chit');
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->deleteById($id);
            $summary = new AuctionSummary(1);
            $summary->deleted($this->_auctionChit->member_id, $this->_auctionChit->auction_date, $this->_auctionChit->donor_id);
            $this->_model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\ErrorException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
         
        $this->_printLine('Completed');
        $this->finish();
    }
    
    private function _preload()
    {
        $this->_date = $this->_auctionChit->auction_date;
        $this->_member = $this->_auctionChit->member_id;
        
        $memberModel = new Member();
        $this->_fmc = $memberModel->selectColumnById('fmc_id', $this->_member);
        
        $this->_sql->whereId('member_id', $this->_member)
                   ->whereEqual('auction_date', $this->_date)
                   ->whereOne('has_deduction_bill');
        if ($this->_model->selectTotal($this->_sql->getWhere()) > 1) {
            $this->_single = false;
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('auction_chit_id', $this->_auctionChit->id)
                   ->whereGreater('deduction_amount', 0);
        $where = $this->_sql->getWhere();
        $deductionModel = new Deduction();
        $this->_deductions = $deductionModel->selectPairs(['deduction_type_id', 'deduction_amount'], $where);
        
        
        $this->_forwardUpdate = 'UPDATE `[T]` SET `opening_balance` = `opening_balance` - \'[A]\', '
                              . '`closing_balance` = `closing_balance` - \'[A]\' WHERE ';
        
        $date = DateHelper::getDateTime($this->_date);
        $year = $date->format('Y');
        $month = $date->format('n');
        $days = $date->format('t');
        
        $date->setDate($year, $month, $days);
        $this->_monthlyDate = $date->format(DateHelper::MYSQL_DATE);
    }
    
    private function _resetAuctionChit()
    {
        $update = [
            'has_deduction_bill' => 0,
            'member_paid_amount' => 0,
            'deduction_amount' => 0,
            'deduction_balance' => 0,
            'deduction_note' => '',
        ];
        $this->_model->updateById($update, $this->_auctionChit->id);
            
        // clear deductions
        $this->_sql->clear();
        $this->_sql->whereId('auction_chit_id', $this->_auctionChit->id);
        $deductionModel = new Deduction();
        $deductionModel->delete($this->_sql->getWhere());
    }
    
    private function _auctionDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_member)
                   ->whereEqual('auction_date', $this->_date);
        $where = $this->_sql->getWhere();
        
        $auctionDailyModel = new AuctionDaily();
        $current = $auctionDailyModel->selectRow($where);
        $update2 = [];
        $update2['member_paid'] = $current->member_paid - $this->_auctionChit->member_paid_amount;
        $update2['deduction_amount'] = $current->deduction_amount - $this->_auctionChit->deduction_amount;
        $update2['deduction_balance'] = $current->deduction_balance - $this->_auctionChit->deduction_balance;
        $auctionDailyModel->updateById($update2, $current->id);
    }
    
    private function _memberDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_member)
                   ->whereEqual('deduction_date', $this->_date);
        $where = $this->_sql->getWhere();
        
        $dailyModel = new DeductionMemberDaily();
        if ($this->_single) {
            $dailyModel->delete($where);
            
            return;
        }
        
        $current = $dailyModel->selectIndexed($where, null, null, 'deduction_type_id');
        $forward = str_replace('[T]', $dailyModel->getTable(), $this->_forwardUpdate);
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount - $amount,
                    'closing_balance' => $current[$type]->closing_balance - $amount,
                    'entries' => $current[$type]->entries - 1,
                ];
                $dailyModel->updateById($update, $current[$type]->id);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $this->_member)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_date);
            $whereForward = $this->_sql->getWhere();
            if ($dailyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forward) . $whereForward;
                $dailyModel->exec($query);
            }
        }
    }
    
    private function _memberMonthly()
    {
        $monthlyModel = new DeductionMemberMonthly();
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_member)
                   ->whereEqual('deduction_date', $this->_monthlyDate);
        $where = $this->_sql->getWhere();
        $current = $monthlyModel->selectIndexed($where, null, null, 'deduction_type_id');
        $forward = str_replace('[T]', $monthlyModel->getTable(), $this->_forwardUpdate);
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount - $amount,
                    'closing_balance' => $current[$type]->closing_balance - $amount,
                    'entries' => $current[$type]->entries - 1,
                ];
                $monthlyModel->updateById($update, $current[$type]->id);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $this->_member)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_monthlyDate);
            $whereForward = $this->_sql->getWhere();
            if ($monthlyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forward) . $whereForward;
                $monthlyModel->exec($query);
            }
        }
    }
    
    private function _fmcDaily()
    {
        $dailyModel = new DeductionDaily();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmc)
                   ->whereEqual('deduction_date', $this->_date);
        $where = $this->_sql->getWhere();
        $current = $dailyModel->selectIndexed($where, null, null, 'deduction_type_id');
        $forward = str_replace('[T]', $dailyModel->getTable(), $this->_forwardUpdate);
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount - $amount,
                    'closing_balance' => $current[$type]->closing_balance - $amount,
                    'entries' => $current[$type]->entries - 1,
                ];
                $dailyModel->updateById($update, $current[$type]->id);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $this->_fmc)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_date);
            $whereForward = $this->_sql->getWhere();
            if ($dailyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forward) . $whereForward;
                $dailyModel->exec($query);
            }
        }
    }
    
    private function _fmcMonthly()
    {
        $monthlyModel = new DeductionMonthly();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmc)
                   ->whereEqual('deduction_date', $this->_monthlyDate);
        $where = $this->_sql->getWhere();
        $current = $monthlyModel->selectIndexed($where, null, null, 'deduction_type_id');
        $forward = str_replace('[T]', $monthlyModel->getTable(), $this->_forwardUpdate);
        
        foreach ($this->_deductions as $type => $amount) {
            if (isset($current[$type])) {
                $update = [
                    'deduction_amount' => $current[$type]->deduction_amount - $amount,
                    'closing_balance' => $current[$type]->closing_balance - $amount,
                    'entries' => $current[$type]->entries - 1,
                ];
                $monthlyModel->updateById($update, $current[$type]->id);
            }
            
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $this->_fmc)
                       ->whereId('deduction_type_id', $type)
                       ->whereGreater('deduction_date', $this->_monthlyDate);
            $whereForward = $this->_sql->getWhere();
            if ($monthlyModel->selectTotal($whereForward) > 0) {
                $query = str_replace('[A]', $amount, $forward) . $whereForward;
                $monthlyModel->exec($query);
            }
        }
    }
    
    
    
}
