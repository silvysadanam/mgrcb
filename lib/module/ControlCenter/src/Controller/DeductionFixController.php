<?php
/**
 * ControlCenter DeductionFix controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;


class DeductionFixController extends ControlCenterController
{
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
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
        $this->start('ControlCenter / Deduction Fix / run');
        $this->stop();
       
        $id = 9698;
        $deduction = [
            1 => 150, // FMC office
            2 => 75, // FWS
            3 => 75, // Union
            4 => 150, // Auction
            10 => 10, // Insurance
            21 => 75, // Retirement
            24 => 100, // subsidy
            7 => 100, // FMC LOAN
            22 => 100, // Common savings
             9 => 400, // Bank loan
            8 => 100, // Relief loan
            6 => 65, // engine
            13 => 100 // thattumadi
        ];
        
        $this->_printLine($id);
        $model = new \Model\AuctionDaily();
        if (! $model->hasId($id)) {
            $this->_printLine('No daily entry');
            $this->finish();
        }
        
        $daily = $model->find($id);
        $sql = $model->getSql();
        $sql->whereId('member_id', $daily->member_id)
            ->whereLess('deduction_date', $daily->auction_date);
        $where1 = $sql->getWhere();
        
        $deductionBillModel = new \Model\DeductionBill();
        if (0 == $deductionBillModel->selectTotal($where1)) {
            $this->_printLine('No previous');
            $this->finish();
        }
        
        $lastDeductionBill = $deductionBillModel->selectRow($where1, 'deduction_date DESC');
        $deductionBillEntryModel = new \Model\DeductionBillEntry();
        
        $sql->clear();
        $sql->whereId('deduction_bill_id', $lastDeductionBill->id);
        $first = $deductionBillEntryModel->selectPairs(['deduction_type_id', 'closing_balance'], $sql->getWhere());
        $now = DateHelper::timestamp();
        $sql->clear();
        $sql->whereId('member_id', $daily->member_id)
            ->whereEqual('deduction_date', $daily->auction_date);
        $where2 = $sql->getWhere();
        $amount = array_sum($deduction);
        
        if ($deductionBillModel->selectTotal($where2) > 0) {
            $this->_printLine('Deduction bill exists previous');
            $this->finish();
        }
        
        try {
            $model->getAdapter()->beginTransaction();
            $create = [
                'member_id' => $daily->member_id, 'deduction_date' => $daily->auction_date,
                'auction_amount' => $daily->amount, 'available_amount' => $daily->balance,
                'auction_chits' => $daily->entries, 'deduction_amount' => $amount,
                'deduction_balance' => $daily->amount - $amount, 'created_at' => $now,
                'created_by' => $lastDeductionBill->created_by
            ];
            $billId = $deductionBillModel->insert($create);
            //$billId = 0;
            $default = [
                'deduction_bill_id' => $billId,
                'deduction_type_id' => '',
                'opening_balance' => 0,
                'amount' => 0,
                'closing_balance' => 0,
            ];
            
            $insertEntry = $deductionBillEntryModel->getInsertPreparedStatement(array_keys($default));
            foreach ($first as $type => $closing) {
                $entry = $default;
                $entry['deduction_type_id'] = $type;
                $entry['opening_balance'] = $closing;
                if (isset($deduction[$type])) {
                    $entry['amount'] = $deduction[$type];
                    $entry['closing_balance'] = $closing + $deduction[$type];
                } else {
                    $entry['closing_balance'] = $closing;
                }
                $insertEntry->execute(array_values($entry));
            }

            $sql->clear();
            $sql->whereId('member_id', $daily->member_id)
                ->whereGreater('deduction_date', $daily->auction_date);
            $where3 = $sql->getWhere();
            if ($deductionBillModel->selectTotal($where3) > 0) {
                $ids = $deductionBillModel->selectIds($where3);
                $sql->clear();
                $sql->whereIn('deduction_bill_id', $ids);
                $where4 = $sql->getWhere();
                $query = 'UPDATE ' . $deductionBillEntryModel->getTableQuoted() . ' SET '
                       . '`opening_balance` = `opening_balance` + ?, `closing_balance` = `closing_balance` + ? '
                        . 'WHERE ' . $where4 . ' AND `deduction_type_id` =?';
                $update = $deductionBillEntryModel->prepare($query);

                foreach ($deduction as $typeId => $modify) {
                    $update->execute([$modify, $modify, $typeId]);
                }
            }
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    /**
     * run action
     * 
     * @return \Web\View\ViewModel
     */
    public function correctAction()
    {
        $this->start('ControlCenter / Deduction Correct / run');
        $this->stop();
       
        $id = 10477;
        $this->_printLine($id);
        $model = new \Model\AuctionDaily();
        if (! $model->hasId($id)) {
            $this->_printLine('No daily entry');
            $this->finish();
        }
        
        $memberId = 32;
        $startDate = '2022-12-15';
        $type = 22; 
        $deduct = 100;
        
        $sql = $model->getSql();
        
        $sql->whereId('member_id', $memberId)
            ->whereGreater('deduction_date', $startDate, true);
        $where1 = $sql->getWhere();
        
        $deductionBillModel = new \Model\DeductionBill();
        if (0 == $deductionBillModel->selectTotal($where1)) {
            $this->_printLine('No previous');
            $this->finish();
        }
        
        $deductionBillEntryModel = new \Model\DeductionBillEntry();
        
        try {
            $model->getAdapter()->beginTransaction();
            $ids = $deductionBillModel->selectIds($where1);
            $sql->clear();
            $sql->whereIn('deduction_bill_id', $ids)
                ->whereId('deduction_type_id', $type);
            $query = 'UPDATE ' . $deductionBillEntryModel->getTableQuoted() . ' SET '
                   . '`opening_balance` = `opening_balance` - ' . $deduct . ', `closing_balance` = `closing_balance` - ' . $deduct
                   . ' WHERE ' . $sql->getWhere();
            $deductionBillModel->exec($query);
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    public function clearAction()
    {
        $this->start('ControlCenter / Deduction Correct / clear');
        //$this->stop();
       
        $id = 5654;
        $this->_printLine($id);
        $model = new \Model\DeductionBill();
        if (! $model->hasId($id)) {
            $this->_printLine('No deduction bill');
            $this->finish();
        }
        
        $entry = $model->find($id);
        $sql = $model->getSql();
        
        $sql->whereId('member_id', $entry->member_id)
            ->whereGreater('deduction_date', $entry->deduction_date, true);
        
        $where = $sql->getWhere();
        
        try {
            $model->getAdapter()->beginTransaction();
            $total = $model->selectTotal($where);
            $model->delete($where, $total);
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    public function cancelWithdrawalAction()
    {
        $id = 1063;
        $model = new \Model\Withdrawal();
        if (! $model->hasId($id)) {
            $this->_printLine('No record');
            $this->finish();
        }
        
        $sql = $model->getSql();
        
        // `member_id`, `deduction_type_id`, `withdrawal_amount`, `withdrawal_date`, `deduction_bill_date`,
        $withdrawal = $model->find($id);
        //print_r($withdrawal);
        $deductionBillModel = new \Model\DeductionBill();
        $sql->whereId('member_id', $withdrawal->member_id)
            ->whereEqual('deduction_date', $withdrawal->deduction_bill_date);
        $where = $sql->getWhere();
        if (0 == $deductionBillModel->selectTotal($where)) {
            $this->_printLine('No deduction bill');
            $this->finish();
        }
        
        $entryModel = new \Model\DeductionBillEntry();
        $deductionBill = $deductionBillModel->selectRow($where);
        $sql->clear();
        $sql->whereId('deduction_type_id', $withdrawal->deduction_type_id)
            ->whereId('deduction_bill_id', $deductionBill->id);
        
        $deductionBillEntry = $entryModel->selectRow($sql->getWhere());
        
        $sql->clear();
        $sql->whereId('member_id', $withdrawal->member_id)
            ->whereGreater('deduction_date', $withdrawal->deduction_bill_date);
        
        $where2 = $sql->getWhere();
        
        if (0 == $deductionBillModel->selectTotal($where2)) {
            $this->_printLine('No post deduction bills');
            $this->finish();
        }
        
        $billIds = $deductionBillModel->selectIds($where2);
        $sql->clear();
        $sql->whereId('deduction_type_id', $withdrawal->deduction_type_id)
            ->whereIn('deduction_bill_id', $billIds);
        
        $where3 = $sql->getWhere();
        
        $total = $entryModel->selectTotal($where3);
        
        try {
            $model->getAdapter()->beginTransaction();
            $update = 'UPDATE ' . $entryModel->getTableQuoted() 
                    . ' SET `opening_balance` = `opening_balance` + ' . $withdrawal->withdrawal_amount
                    . ', `closing_balance` = `closing_balance` + ' . $withdrawal->withdrawal_amount
                    . ' WHERE ' . $where3 . ' LIMIT ' . $total;
            //echo $update;
            $model->getAdapter()->exec($update);
            
            $modify = [
                'closing_balance' => $deductionBillEntry->closing_balance + $withdrawal->withdrawal_amount,
                'withdrawal' => $deductionBillEntry->withdrawal - $withdrawal->withdrawal_amount,
            ];
            
            $entryModel->updateById($modify, $deductionBillEntry->id);
            $model->deleteById($id);
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
        
        
    }
    
    /**
     * member paid action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberPaidAction()
    {
        $this->start('ControlCenter / Deduction Fix / member paid');
       
        $id = 10537;
        $limit = 3000;
        $deductionBillModel = new \Model\DeductionBill();
        
        $this->_printLine($id);
        $model = new \Model\AuctionDaily();
        $sql = $model->getSql();
        $sql->whereGreater('id', $id);
        $where1 = $sql->getWhere();
        if (0 == $deductionBillModel->selectTotal($where1)) {
            $this->_printLine('No deduction bill entry');
            $this->finish();
        }
        
        $list = $deductionBillModel->select($where1, 'id', $limit);
        $update = $deductionBillModel->getUpdatePreparedStatement(['member_paid_amount']);
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($list as $item) {
                $this->_printLine('ID ' . $item->id);
                $sql->clear();
                $sql->whereEqual('auction_date', $item->deduction_date)
                    ->whereId('member_id', $item->member_id);
                $where = $sql->getWhere();
                if ($model->selectTotal($where) > 0) {
                    $daily = $model->selectRow($where);
                    if ($item->deduction_amount > $daily->balance) {
                        $paid = $item->deduction_amount - $daily->balance;
                        $update->execute([$paid, $item->id]);
                        $this->_printLine('Updated ' . $paid);
                    }
                }
            }
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
        
        $this->_printLine('Completed');
        $this->finish();
        
    }
    
}
