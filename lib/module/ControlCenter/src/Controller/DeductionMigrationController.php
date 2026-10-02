<?php
/**
 * ControlCenter DeductionMigration controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Model\{DeductionBill, DeductionBillEntry, DeductionBillNote};
use Model\{AuctionChit, Deduction, DeductionNote};

use Shared\Utility\DeductionSupport;

use Web\Core\Session;


class DeductionMigrationController extends ControlCenterController
{
    private $_limit = 300;
    
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\DeductionBill
     */
    private $_deductionBillModel;
    
    /**
     * 
     * @var \Model\DeductionBillEntry
     */
    private $_deductionBillEntryModel;
    
    /**
     * 
     * @var \Model\DeductionBillNote
     */
    private $_deductionBillNoteModel;
    
    /**
     * 
     * @var \Model\Deduction
     */
    private $_deductionModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var \stdClass
     */
    private $_deductionBill;
    
    /**
     * 
     * @var \stdClass
     */
    private $_auctionChit;
    
    /**
     * 
     * @var \PDOStatement
     */
    private $_insertDeduction;
    
    /**
     * 
     * @var \PDOStatement
     */
    private $_insertNote;
    
    /**
     * 
     * @var \PDOStatement
     */
    private $_updateAuctionChit;
    
    /**
     * 
     * @var array
     */
    private $_default = [];
    
    /**
     * 
     * @var array
     */
    private $_notes;
    
    /**
     * 
     * @var array
     */
    private $_entries;
    
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
        //Session::clear('offset'); exit;
        
        $this->_model = new AuctionChit();
        $this->_deductionBillModel = new DeductionBill();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
        $this->_deductionBillNoteModel = new DeductionBillNote();
        
        $this->_deductionModel = new Deduction();
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
    
    public function entryAction()
    {
        set_time_limit(120);
        $this->start('ControlCenter / Deduction Migration / entry');
        $offset = 0;
        if (Session::is('offset')) {
            $offset = Session::get('offset');
        }
        
        $limit = $offset . ',' . $this->_limit;
        $this->_printLine('Limit ' . $limit);
        $this->_default['deduction'] = [
            'auction_chit_id' => 0,
            'deduction_type_id' => 0,
            'deduction_amount' => 0,
        ];
        
        $this->_insertDeduction = $this->_deductionModel->getInsertPreparedStatement(array_keys($this->_default['deduction']));
        
        $this->_sql->clear();
        $this->_sql->whereOne('has_deduction_bill');
        $where = $this->_sql->getWhere();
        $list = $this->_model->select($where, 'auction_date,id', $limit);
        if (empty($list)) {
            $this->_printLine('No pending auction chits');
            $this->finish();
        }
        
        foreach ($list as $item) {
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $item->member_id)
                       ->whereEqual('deduction_date', $item->auction_date);
            
            $where1 = $this->_sql->getWhere();
            if ($this->_deductionBillModel->selectTotal($where1) > 0) {
                if (0 == $this->_deductionModel->selectTotal(['auction_chit_id' => $item->id])) {
                    $this->_auctionChit = $item;
                    $id = $this->_deductionBillModel->selectId($where1);
                    $this->_entries = $this->_deductionBillEntryModel->select(['deduction_bill_id' => $id]);
                    $this->_recreate($id);
                }
            }
        }
        
        Session::set('offset', $offset + $this->_limit);
        $this->_printLine('Completed');
        $this->finish();
    }
    
    
    private function _recreate($id)
    {
        $this->_printLine('DB ' . $id . ', AC ' . $this->_auctionChit->id);
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_importDeductions();
            
            $support = new DeductionSupport($this->_auctionChit->id);
            $support->created();
            $this->_model->getAdapter()->commit();
            $this->_printLine('OK');
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\ErrorException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    
    
    /**
     * run action
     * 
     * @return \Web\View\ViewModel
     */
    public function runAction()
    {
        $this->start('ControlCenter / Deduction Migration / run');
        $this->stop();
        $offset = 0;
        if (Session::is('offset')) {
            $offset = Session::get('offset');
        }
        
        $limit = $offset . ',' . $this->_limit;
        $this->_printLine('Limit ' . $limit);
        
        $this->_sql->clear();
        $this->_sql->whereOne('auction_chits');
        $where = $this->_sql->getWhere();
        if (0 == $this->_deductionBillModel->selectTotal($where)) {
            $this->_printLine('No deduction bills');
            $this->finish();
        }
        
        $list = $this->_deductionBillModel->select($where, 'deduction_date', $limit);
        
        if (empty($list)) {
            $this->_printLine('No pending deduction bills');
            $this->finish();
        }
        
        $this->_preload();
        
        foreach ($list as $item) {
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $item->member_id)
                       ->whereEqual('auction_date', $item->deduction_date);
            
            $where1 = $this->_sql->getWhere();
            if (1 == $this->_model->selectTotal($where1)) {
                $this->_auctionChit = $this->_model->selectRow($where1);
                if (0 == $this->_auctionChit->has_deduction_bill) {
                    $this->_deductionBill = $item;
                    $this->_sql->clear();
                    $this->_sql->whereId('deduction_bill_id', $item->id);
                    $where2 = $this->_sql->getWhere();
                    $this->_entries = $this->_deductionBillEntryModel->select($where2);
                    if ($this->_deductionBillNoteModel->selectTotal($where2) > 0) {
                        $this->_notes = $this->_deductionBillNoteModel->select($where2);
                    } else {
                        $this->_notes = [];
                    }
                    
                    $this->_migrate();
                }
            }
        }
        
        Session::set('offset', $offset + $this->_limit);
        $this->_printLine('Completed');
        $this->finish();
    }
    
    private function _preload()
    {
        $this->_default['deduction'] = [
            'auction_chit_id' => 0,
            'deduction_type_id' => 0,
            'deduction_amount' => 0,
        ];
        
        $this->_insertDeduction = $this->_deductionModel->getInsertPreparedStatement(array_keys($this->_default['deduction']));
        $this->_default['note'] = [
            'auction_chit_id' => '',
            'note' => '',
            'created_by' => '',
            'created_at' => '',
        ];
        
        $deductionNoteModel = new DeductionNote();
        $this->_insertNote = $deductionNoteModel->getInsertPreparedStatement(array_keys($this->_default['note']));
        
        $this->_default['note_fields'] = [ 'note', 'created_by', 'created_at' ];
        
        $this->_default['auction_chit'] = [
            'has_deduction_bill' => 1,
            'member_paid_amount' => 0,
            'deduction_amount' => 0,
            'deduction_balance' => 0,
            'deduction_bill_balance' => 0,
            'migrated' => 1,
        ];
        
        $this->_updateAuctionChit = $this->_model->getUpdatePreparedStatement(array_keys($this->_default['auction_chit']));
    }
    
    private function _migrate()
    {
        $this->_printLine('DB ' . $this->_deductionBill->id . ', AC ' . $this->_auctionChit->id);
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_deductionUpdate();
            $this->_importDeductions();
            if (! empty($this->_notes)) {
                $this->_saveNotes();
            }
            $support = new DeductionSupport($this->_auctionChit->id);
            $support->created();
            $this->_model->getAdapter()->commit();
            $this->_printLine('OK');
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\ErrorException $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
            exit;
        }
    }
    
    
    private function _deductionUpdate()
    {
        $update = $this->_default['auction_chit'];
        $update['deduction_amount'] = $this->_deductionBill->deduction_amount;
        $update['deduction_balance'] = $this->_deductionBill->deduction_balance;
        
        if ($this->_deductionBill->deduction_amount > $this->_auctionChit->balance) {
            $update['member_paid_amount'] = $this->_deductionBill->deduction_amount - $this->_auctionChit->balance;
        }
        
        if ($this->_deductionBill->deduction_amount < $this->_auctionChit->balance) {
            $update['deduction_bill_balance'] = $this->_auctionChit->balance - $this->_deductionBill->deduction_amount;
        }

        $values = array_values($update);
        $values[] = $this->_auctionChit->id;
        $this->_updateAuctionChit->execute($values);
    }
    
    private function _importDeductions()
    {
        $deduction = $this->_default['deduction'];
        $deduction['auction_chit_id'] = $this->_auctionChit->id;
        foreach ($this->_entries as $item) {
            $deduction['deduction_type_id'] = $item->deduction_type_id;
            $deduction['deduction_amount'] = $item->amount;
            $this->_insertDeduction->execute(array_values($deduction));
        }
        
    }
    
    private function _saveNotes()
    {
        $note = $this->_default['note'];
        $note['auction_chit_id'] = $this->_auctionChit->id;
        foreach ($this->_notes as $item) {
            foreach ($this->_default['note_fields'] as $field) {
                $note[$field] = $item->{$field};
            }
        }
        
        $this->_insertNote->execute(array_values($note));
    }
    
}
