<?php
/**
 * Shared ManageVoucher Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{RPTransMember, RPTransactionDetails, Member, RPTransMemberDetails};
use Shared\Form\AjaxForm;
use Shared\Utility\AccountsHelper;
use Application\Utility\LoanStore;
use Web\Utility\DateHelper;


class ManageVoucher
{
    const DELETE_VOUCHER = 1;
    const GET_ENTRY = 2;
    const SAVE_ENTRY = 3;
    const DELETE_ENTRY = 4;
    const EDIT_VOUCHER = 5;

    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\RPTransMemberDetails
     */
    private $_model;
    private $_transModel;
    private $_membTrans;
    /**
     * 
     * @var array
     */
    private $_fields;
    
    /**
     * 
     * @var \Web\Mvc\AjaxResponse
     */
    private $_response;
    
    /**
     * 
     * @var array
     */
    private $_result = [];
    
    /**
     * 
     * @var \Shared\Form\AjaxForm
     */
    private $_form;
    
    /**
     * 
     * @var array
     */
    private $_formValues = [];
    
    /**
     * 
     * @var boolean
     */
    private $_manager = true;
    private $_accountHeads = [];
    /**
     * 
     * @var int
     */
    private $_managerId = 0;
    
    /**
     * constructor
     * 
     * @param int $id member id
     */
    public function __construct($id, $fmc_id, $manager = true)
    {
        $this->_id = $id;
        $this->_manager = $manager;
        $this->_model = new RPTransactionDetails();
        $this->_transModel = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $accountHelper = new AccountsHelper($fmc_id);
        $heads = $accountHelper->getHeads();
        $accountHeads = [];
        foreach ($heads as $head) {
            $this->_accountHeads[$head->id] = $head->head_name;
        }
    }
    
    public function setManagerId($id)
    {
        $this->_managerId = $id;
    }
    
    /**
     * manage voucher
     * 
     * @param \Web\Mvc\AjaxResponse $response
     * @param int $action
     * @return array
     */
    public function run($response, $action)
    {
        $this->_response = $response;
        $this->_response->message('Invalid request');
        
        switch ($action) {
            case self::DELETE_VOUCHER:
                $this->_deleteVoucher();
                break;
            
            case self::GET_ENTRY:
                $this->_getEntry();
                break;
            
            case self::SAVE_ENTRY:
                $this->_saveEntry();
                break;
            
            case self::DELETE_ENTRY:
                $this->_deleteEntry();
                break;
            
            case self::EDIT_VOUCHER:
                $this->_EditEntry();
                break;
            
            default:
                break;
        }
        
        return $this->_result;
    }
   
    private function _deleteVoucher()
    {
        /*
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return;
        }*/
        //Check transfer transaction
        $voucher = $this->_model->find($this->_id);
        if($voucher->trans_to > 0){
            $receiptTrans = $this->_transModel->checkByColumnByFmc('voucher_id', $voucher->id, $voucher->trans_to);
        }
        $now = DateHelper::timestamp();
        $update['updated_by'] = $this->_managerId;
        $update['updated_at'] = $now;
        $update['status'] = '0';
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->updateById($update, $this->_id);
            $this->_model->getAdapter()->commit();
            if(isset($receiptTrans->id)){
                $this->_transModel->getAdapter()->beginTransaction();
                $this->_transModel->updateById($update, $receiptTrans->id);
                $this->_transModel->getAdapter()->commit();
                $this->_deleted('Vocher & Receipt');
            } else {
                $this->_deleted('Vocher');
            }
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->addData($ex->getTraceAsString(), 'trace')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * get entry details
     */
    private function _getEntry()
    {
        if (! $this->_checkId()) {
            return;
        }
        
        $fields = [ 'headID', 'amount' ];
        $this->_setResult($this->_model->selectFieldsById($fields, $this->_formValues['id']));
        $this->_valid();
    }
    
    /**
     * save entry
     * 
     * @return null
     */
    private function _saveEntry()
    {
        $this->_fields = [ 'headID', 'amount'];
        
        $this->_loadAjaxForm($this->_fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        if ($this->_formValues['id'] > 0) {
            $this->_updateEntry();
            return;
        }
        
        $this->_addEntry();
    }
    
    /**
     * update entry
     * 
     * @return null
     */
    private function _updateEntry()
    {
        if (! $this->_checkEntryId()) {
            return;
        }
            
        if (! $this->_validateEntry()) {
            return;
        }
        $rpupdate = [];
        $update = [];
        $entry = $this->_model->find($this->_formValues['id']);
        foreach ($this->_fields as $field) {
            if ($this->_formValues[$field] != $entry->{$field}) {
                $value = $this->_formValues[$field];
                if ($value != $entry->{$field}) {
                    $update[$field] = $value;
                }
            }
        }

        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $rpupdate['updated_at'] = DateHelper::timestamp();
        if ($this->_manager) {
            $rpupdate['updated_by'] = $this->_managerId;
        } 

        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->updateById($update, $this->_formValues['id']);
            $this->_model->updateById($rpupdate, $this->_formValues['id']);
            $this->_model->getAdapter()->commit();
            $this->_saved('Entry', true);
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * edit entry
     * 
     * @return null
     */
    private function _editEntry()
    {
       
        $mfLoan = 0;
        $this->_fields = [ 'transDate', 'non_member', 'particulars', 'transBy', 'ByTo', 'mfLoan'];
        
        $this->_loadAjaxForm($this->_fields);
        if (empty($this->_formValues)) {
            return;
        }
        if (! $this->_checkEntryId()) {
            return;
        }
            
        if (! $this->_validateVoucherEntry()) {
            return;
        }
        if(isset($this->_formValues['mfLoan'])){
            $mfLoan = $this->_formValues['mfLoan'];
        }
        if($this->_formValues['mfLoan']){
            $this->_fields = array_values(array_diff(
                $this->_fields,
                ['transBy', 'ByTo', 'mfLoan']
            ));
        } else {
            $this->_fields = array_values(array_diff(
                $this->_fields,
                ['mfLoan']
            ));
        }
        if(isset($this->_formValues['ByTo']) ===' Cash')
        {
            $this->_formValues['transBy'] = 0;
        } 
        
        $rpupdate = [];
        $update = [];
        $entry = $this->_model->find($this->_formValues['id']);
        foreach ($this->_fields as $field) {
          if($field === 'ByTo') continue;
          if ($this->_formValues[$field] != $entry->{$field}) {
            if($field == 'transDate') {
                $value = DateHelper::fromDatepicker($this->_formValues[$field]);
            }else {
                $value = $this->_formValues[$field];
            }
            if ($value != $entry->{$field}) {
                $update[$field] = $value;
            }
          }
        }

        if (empty($update)) {
            $this->_noChange();
            return;
        }
        $voucher = $this->_model->find($this->_formValues['id']);   
        $rpupdate['updated_at'] = DateHelper::timestamp();
        if ($this->_manager) {
            $rpupdate['updated_by'] = $this->_managerId;
        } 

        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->updateById($update, $this->_formValues['id']);
            $this->_model->updateById($rpupdate, $this->_formValues['id']);
            if($voucher->loan_id > 0){
            $sql = "SELECT * FROM ". $this->_transModel->getTableQuoted() . " `t` "
                    . "inner join " . $this->_membTrans->getTableQuoted()." `m` ON `m`.`transID` = `t`.`id` " 
                    . "  where 1=1  and `t`.`loan_id` = {$voucher->loan_id} and `t`.`member_id` = {$voucher->member_id} and `m`.`amount` = {$voucher->amount} order by t.id desc limit 0,1";
            $data = $this->_transModel->queryRows($sql);  
            if (!empty($data) && isset($data[0]->member_id)) {
                   if (isset($update['particulars'])) {
                            $update['remarks'] = $update['particulars'];
                            unset($update['particulars']);
                    }
                    $this->_transModel->updateById($update, $data[0]->id);    
                }
            }
            if($mfLoan){
                 $sql = "SELECT * FROM ". $this->_model->getTableQuoted() . " `t` "
                            . "  where 1=1  and `t`.`loan_id` = {$voucher->loan_id} and `t`.`member_id` = {$voucher->member_id} and `t`.`amount` = {$voucher->amount}";
                 $data = $this->_model->queryRows($sql); 
                     if (!empty($data)) {
                        foreach ($data as $mfItem) {
                            if ((int) $mfItem->id !== $this->_formValues['id']) {
                                $this->_model->updateById($update, $mfItem->id);
                                $this->_model->updateById($rpupdate, $mfItem->id);
                            }
                        }
                    }
            }
            $this->_model->getAdapter()->commit();
            $this->_saved('Entry', true);
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    private function _addEntry()
    {
        if (! $this->_validateEntry()) {
            return;
        }
              
         
        $now = DateHelper::timestamp();
        
        $update['updated_by'] = $this->_managerId;
        $update['updated_at'] = $now;
        
        $insert = [
            'transID' => $this->_id,
            'headID' => intval($this->_formValues['headID']),
            'amount' => $this->_formValues['amount'],
            'remarks' =>  $this->_accountHeads[$this->_formValues['headID']],
        ];
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->insert($insert);
            $this->_model->updateById($update, $this->_id);
            $this->_model->getAdapter()->commit();
            $this->_saved('Entry', false);
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * validate entry
     * 
     * @return boolean
     */
    private function _validateEntry()
    {
        $errors = [];

        if (0 == $this->_formValues['headID']) {
            $errors[] = 'Head not selected';
        }
        
        if ('' === $this->_formValues['amount']) {
            $errors[] = 'Amount is required';
        } elseif ($this->_notNumber($this->_formValues['amount'])) {
            $errors[] = 'Amount is invalid';
        }
        
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * validate entry
     * 
     * @return boolean
     */
    private function _validateVoucherEntry()
    {
        $errors = [];
        $entry = $this->_model->find($this->_formValues['id']);
        if ('' === $this->_formValues['transDate']) {
            $errors[] = 'Date not selected';
        }
        
        if ('' === $this->_formValues['particulars']) {
            $errors[] = 'Narration is required';
        } 
         
        if($entry->non_member != "" && '' === $this->_formValues['non_member']){
            $errors[] = 'Non Member is required';
        } 
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * delete entry
     * 
     * @return null
     */
    private function _deleteEntry()
    {
        
        
        if (! $this->_checkId()) {
            return;
        }
        /*if ($this->_model->selectTotal(['transID' => $this->_id]) < 2) {
            $this->_setError(['Single entry cannot be deleted']);
            return;
        }*/
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $where = "childID = {$this->_formValues['id']}";
            $this->_model->deleteOne($where);
            $this->_model->getAdapter()->commit();
            $this->_deleted('Entry');
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * check form id
     * 
     * @return boolean
     */
    private function _checkFormId()
    {
        if (empty($this->_formValues)) {
            return false;
        }
        
        return $this->_formValues['id'] > 0;
    }
   
    /**
     * check entry  id
     * 
     * @return boolean
     */
    private function _checkId()
    {
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return false;
        }

        if (! $this->_checkEntryId()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * check entry id
     * 
     * @return boolean
     */
    private function _checkEntryId()
    {
        if (! $this->_model->hasId($this->_formValues['id'])) {
            return false;
        }
        return $this->_id == $this->_model->selectColumnById('id', $this->_formValues['id']);
    }
    
    /**
     * load ajax form
     * 
     * @param array $fields
     */
    private function _loadAjaxForm($fields = [])
    {
        $this->_form = new AjaxForm();
        if (! empty($fields)) {
            $this->_form->addFields($fields);
        }
        
        if ($this->_form->isPost()) {
            $this->_formValues = $this->_form->run();
        }
    }
    
    /**
     * set response valid
     * 
     * @param string $message
     * @param boolean $flash
     */
    private function _valid($message = 'success', $flash = false)
    {
        $this->_response->valid()
                        ->message($message);
        if ($flash) {
            $this->_result['flash'] = $message;
        }
    }
    
    /**
     * set result data
     * 
     * @param \stdClass $result
     */
    private function _setResult($result)
    {
        $this->_response->addData($result, 'result');
    }
    
    /**
     * set response error
     * 
     * @param array $errors
     */
    private function _setError($errors)
    {
        $this->_response->message(implode('<br>', $errors));
    }
    
    /**
     * set response no changes
     */
    private function _noChange()
    {
        $this->_response->message('No changes');
    }
    
    /**
     * set saved message
     * 
     * @param string $title
     * @param boolean $update
     */
    private function _saved($title, $update = false)
    {
        $message = $title . ' ';
        if ($update) {
            $message .= 'updated';
        } else {
            $message .= 'added';
        }
        
        $this->_valid($message, true);
    }
    
    /**
     * set deleted message
     * 
     * @param string $title
     */
    private function _deleted($title)
    {
        $this->_valid($title . ' deleted', true);
    }
    
    
    private function _notNumber($number, $zeroAllowed = false)
    {
        if ($zeroAllowed) {
            if ('0' == $number) {
                return false;
            }
        }
        
        //return 0 == preg_match('/^[1-9]\d*$/', $number);
        
        return 0 == preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $number);
    }
    
}
