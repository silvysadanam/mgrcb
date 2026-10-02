<?php
/**
 * Shared ManageReceipt Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{RPTransMember, RPTransMemberDetails, Member};
use Shared\Form\AjaxForm;
use Shared\Utility\AccountsHelper;

use Web\Utility\DateHelper;


class ManageReceipt
{
    const DELETE_RECEIPT = 1;
    const GET_ENTRY = 2;
    const SAVE_ENTRY = 3;
    const DELETE_ENTRY = 4;

    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\RPTransMember
     */
    private $_model;
    
    /**
     * 
     * @var \Model\RPTransMemberDetails
     */
    private $_rpTransModel;
    
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
        $this->_model = new RPTransMember();
        $this->_rpTransModel = new RPTransMemberDetails();
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
     * manage receipt
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
            case self::DELETE_RECEIPT:
                $this->_deleteReceipt();
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
            
            default:
                break;
        }
        
        return $this->_result;
    }
   
    private function _deleteReceipt()
    {
        /*
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return;
        }*/
        $now = DateHelper::timestamp();
        $update['updated_by'] = $this->_managerId;
        $update['updated_at'] = $now;
        $update['status'] = '0';
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_model->updateById($update, $this->_id);
            $this->_model->getAdapter()->commit();
            $this->_deleted('Receipt');
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
        $this->_setResult($this->_rpTransModel->selectFieldsById($fields, $this->_formValues['id']));
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
        $entry = $this->_rpTransModel->find($this->_formValues['id']);
        foreach ($this->_fields as $field) {
            $value = $this->_formValues[$field];
            if ($value != $entry->{$field}) {
                $update[$field] = $value;
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
            $this->_rpTransModel->updateById($update, $this->_formValues['id']);
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
            $this->_rpTransModel->insert($insert);
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
     * delete entry
     * 
     * @return null
     */
    private function _deleteEntry()
    {
        
        
        if (! $this->_checkId()) {
            return;
        }
        /*if ($this->_rpTransModel->selectTotal(['transID' => $this->_id]) < 2) {
            $this->_setError(['Single entry cannot be deleted']);
            return;
        }*/
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $where = "childID = {$this->_formValues['id']}";
            $this->_rpTransModel->deleteOne($where);
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
        if (! $this->_rpTransModel->hasId($this->_formValues['id'])) {
            return false;
        }
        return $this->_id == $this->_rpTransModel->selectColumnById('transID', $this->_formValues['id']);
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
