<?php
/**
 * Backend ManageHeads Utility
 *
 * @category   Backend
 * @package    Backend\Utility
 */

namespace Backend\Utility;

use Shared\Form\AjaxForm;
use Model\{Account, RPHeads, RPAccountSettings};


class ManageHeads
{
    /**
     * 
     * @var \Web\Mvc\AjaxResponse
     */
    private $_response;
    
    /**
     * 
     * @var array
     */
    private $_values;
    
    /**
     * 
     * @var array
     */
    private $_result = [];
    
    /**
     * 
     * @param \Web\Mvc\AjaxResponse $response
     */
    public function __construct($response)
    {
        $this->_response = $response;
        $this->_response->message('Invalid input');
        $this->_headModel = new RPHeads();
    }
    
    
    /**
     * manage head
     */
    public function head()
    {
        $this->_loadForm(['fmc_id', 'action', 'head_name', 'head_alias', 'transType', 'opb', 'code', 'ie_item', 'bs_item']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveHead();
        } else {
            $this->_removeHead();
        }
        
        return $this->_result;
    }
    
    /**
     * manage setting
     */
    public function setting()
    {
        $this->_loadForm(['fmc_id', 'action', 'code', 'voucher_no', 'voucher_no', 'receipt_no', 'financial_year']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveSetting();
        } else {
            $this->_removeSetting();
        }
        
        return $this->_result;
    }
    
    
    /**
     * check action
     * 
     * @return boolean
     */
    private function _checkAction()
    {
        return in_array($this->_values['action'], [1, 2]);
    }
    
    /**
     * save setting
     * 
     * @return null
     */
    private function _saveSetting()
    {
        $model = new RPAccountSettings();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateSetting($id);
            return;
        }
        
        $this->_addSetting();
    }
    
    
    /**
     * save heads
     * 
     * @return null
     */
    private function _saveHead()
    {
        $model = new RPHeads();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateHead($id);
            return;
        }
        
        $this->_addHead();
    }
    
     /**
     * add Setting
     * 
     * @return null
     */
    private function _addSetting()
    {
        
        $model = new RPAccountSettings();
        $errors = [];
        $fmc_id = (int) $this->_values['fmc_id'];
               
         if ('' == $this->_values['code']) {
            $errors[] = 'code is required';
        } else {
            if ($model->checkByColumnExceptFMC_ID('code', $this->_values['code'], $fmc_id)) {
                $errors[] = 'Code exists';
            }
        }
               
        if ('' == $this->_values['voucher_no']) {
            $errors[] = 'Voucher no is required';
        } 
        if ('' == $this->_values['receipt_no']) {
            $errors[] = 'Receipt no is required';
        } 
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['code', 'voucher_no', 'voucher_no', 'receipt_no', 'financial_year'];
        $insert = [];
        foreach ($fields as $field) {
            if ('' != $this->_values[$field]) {
                $insert[$field] = $this->_values[$field];
            }
        }
        
        $insert['fmc_id'] = $fmc_id;
        $model->insert($insert);
        $this->_saved('Setting', false);
    }
    /**
     * add Head
     * 
     * @return null
     */
    private function _addHead()
    {
        
        $model = new RPHeads();
        $errors = [];
        $fmc_id = (int) $this->_values['fmc_id'];
        if ('' == $this->_values['head_name']) {
            $errors[] = 'Head name is required';
        } else {
            $headQuery = "select * from  " . $this->_headModel->getTableQuoted()." where head_name = '{$this->_values['head_name']}' and TransType ='{$this->_values['transType']}' and fmc_id = {$fmc_id}";
            $heads = $this->_headModel->queryRows($headQuery);
            if (isset($heads[0]->head_name)) {
                $errors[] = 'Head name exists';
            }
        }
        
         if ('' == $this->_values['code']) {
            $errors[] = 'code is required';
        } else {
            if ($model->checkByColumnExceptFMC_ID('code', $this->_values['code'], $fmc_id)) {
                $errors[] = 'Code exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['head_name', 'head_alias', 'transType', 'opb', 'code','ie_item', 'bs_item'];
        $insert = [];
        foreach ($fields as $field) {
            if ('' != $this->_values[$field]) {
                $insert[$field] = $this->_values[$field];
            }
        }
        
        $insert['fmc_id'] = $fmc_id;
        $insert['status'] = 'N';
        $model->insert($insert);
        $this->_saved('Head', false);
    }
    
    /**
     * update Setting
     * 
     * @param int $id
     * @return null
     */
    private function _updateSetting($id)
    {
        $model = new RPAccountSettings();
        $errors = [];
        $fmc_id = $this->_values['fmc_id'];
               
         if ('' == $this->_values['code']) {
            $errors[] = 'code is required';
        } else {
            if ($model->checkByColumnExceptIdInFMC('code', $this->_values['code'], $id, $fmc_id)) {
                $errors[] = 'Code exists';
            }
        }
        
        if ('' == $this->_values['voucher_no']) {
            $errors[] = 'Voucher no is required';
        } 
        if ('' == $this->_values['receipt_no']) {
            $errors[] = 'Receipt no is required';
        } 
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $current = $model->find($id);
        $fields = ['code', 'voucher_no', 'voucher_no', 'receipt_no', 'financial_year'];
        $update = [];
        $nameValues = [];
        foreach ($fields as $field) {
            if ($this->_values[$field] != $current->{$field}) {
                $update[$field] = $this->_values[$field];
                $nameValues[] = $this->_values[$field];
            } else {
                $nameValues[] = $current->{$field};
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $model->updateById($update, $id);
        $this->_saved('Setting', true);
    }
    
    /**
     * update Head
     * 
     * @param int $id
     * @return null
     */
    private function _updateHead($id)
    {
        $model = new RPHeads();
        $errors = [];
        $fmc_id = $this->_values['fmc_id'];
        if ('' == $this->_values['head_name']) {
            $errors[] = 'Head Name is required';
        } else {
            $headQuery = "select * from  " . $this->_headModel->getTableQuoted()." where head_name = '{$this->_values['head_name']}' and code='{$this->_values['code']}' and id != {$id} and TransType ='{$this->_values['transType']}' and fmc_id = {$fmc_id}";
            $heads = $this->_headModel->queryRows($headQuery);
            if (isset($heads[0]->head_name)) {
                $errors[] = 'Head details exists';
            }
           // if ($model->checkByColumnExceptIdInFMC('head_name', $this->_values['head_name'], $id, $fmc_id)) {
           //     $errors[] = 'Head Name exists';
           // }
        }
        
         if ('' == $this->_values['code']) {
            $errors[] = 'code is required';
        } else {
            if ($model->checkByColumnExceptIdInFMC('code', $this->_values['code'], $id, $fmc_id)) {
                $errors[] = 'Code exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $current = $model->find($id);
        $fields = ['head_name', 'head_alias', 'transType', 'opb', 'code', 'ie_item', 'bs_item'];
        $update = [];
        $nameValues = [];
        foreach ($fields as $field) {
            if ($this->_values[$field] != $current->{$field}) {
                $update[$field] = $this->_values[$field];
                $nameValues[] = $this->_values[$field];
            } else {
                $nameValues[] = $current->{$field};
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $model->updateById($update, $id);
        $this->_saved('Head', true);
    }
    
    /**
     * remove head
     * 
     * @return null
     */
    private function _removeHead()
    {
        $model = new RPHeads();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }

            //$head = $model->selectColumnById('head_name', $id);
            $model = new RPHeads();
            $update = ['status' => 'Y'];
            $model->updateById($update, $id);
            //$model->deleteById($id);
            $this->_deletd('Head', true);
        }
    }
    
    /**
     * validate IFS Code
     * 
     * @param string $code
     * @return boolean
     */
    private function _ifsCode($code)
    {
        if (11 != strlen($code)) {
            return false;
        }
        
        if (0 == preg_match('/^[A-Z0-9][A-Z0-9]+$/', $code)) {
            return false;
        }
        
        return '0' == substr($code, 4, 1);
    }
    
    /**
     * get head name
     * 
     * @param array $options
     * @return string
     */
    private function _getHeadName($options)
    {
        $list = array_filter($options);
        
        return implode(' / ', $list);
    } 
    
    /**
     * load form
     * 
     * @param array $fields
     * @return null
     */
    private function _loadForm($fields = [])
    {
        $form = new AjaxForm();
        if (! empty($fields)) {
            $form->addFields($fields);
        }
        
        if (! $form->isPost()) {
            return;
        }
        
        $this->_values = $form->run();
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
    private function _deletd($title, $name = '')
    {
        if ('' != $name) {
            $title .= sprintf(' "%s"', $this->_escape($name));
        }
        
        $this->_valid($title . ' deleted', true);
    }
    
    private function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
}
