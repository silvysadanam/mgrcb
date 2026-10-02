<?php
/**
 * Backend ManageHeads Utility
 *
 * @category   Backend
 * @package    Backend\Utility
 */

namespace Branch\Utility;

use Shared\Form\AjaxForm;
use Model\{Account, RPHeads, RPTransactionDetails};
use Web\Utility\DateHelper;

class ManageIncome
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
    }
    
    
    /**
     * manage head
     */
    public function head()
    {
        $this->_loadForm(['fmc_id', 'action', 'transDate', 'headID', 'transNo', 'amount', 'transBy', 'particulars', 'payment_type', 'bank_id']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveCashReceipt();
        } else {
            $this->_removeCashReceipt();
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
     * save heads
     * 
     * @return null
     */
    private function _saveCashReceipt()
    {
        $model = new RPTransactionDetails();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateCashReceipt($id);
            return;
        }
        
        $this->_addCashReceipt();
    }
    
    /**
     * add Head
     * 
     * @return null
     */
    private function _addCashReceipt()
    {
        
        $model = new RPTransactionDetails();
        $errors = [];
        $fmc_id = (int) $this->_values['fmc_id'];
        if (0 == $this->_values['headID']) {
            $errors[] = 'Head not selected';
        }
        
         if ('' === $this->_values['particulars']) {
            $errors[] = 'Narration is required';
        }
        
        if ('' === $this->_values['amount']) {
            $errors[] = 'Amount is required';
        } elseif ($this->_notNumber($this->_values['amount'])) {
            $errors[] = 'Amount is invalid';
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['headID', 'transNo', 'particulars', 'amount'];
        $insert = [];
        foreach ($fields as $field) {
            if ('' != $this->_values[$field]) {
                $insert[$field] = $this->_values[$field];
            }
        }
        $insert['transDate'] = DateHelper::fromDatepicker($this->_values['transDate']);
        $insert['fmc_id'] = $fmc_id;
        $insert['transType'] = 'R';
        if($this->_values['payment_type'] == 'Bank'){
            $insert['transBy'] =  $this->_values['bank_id'];
        }
        $model->insert($insert);
        $this->_saved('Cash Receipt', false);
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
