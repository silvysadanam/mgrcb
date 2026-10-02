<?php
/**
 * Branch ManageAdvanceRepayment Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;

use Model\{AdvanceWithdrawal, AdvanceWithdrawalRepayment, DeductionMemberDaily};

use Shared\Form\AjaxForm;


use Web\Utility\DateHelper;


class ManageAdvanceRepayment
{
    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\AdvanceWithdrawal
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AdvanceWithdrawalRepayment
     */
    private $_advanceRepaymentModel;
    
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
    
    /**
     * 
     * @var int
     */
    private $_managerId = 0;
    
    /**
     * 
     * @var \stdClass
     */
    private $_advance;
    
    /**
     * 
     * @var string
     */
    private $_paymentDate;
    
    /**
     * constructor
     * 
     * @param boolean $manager
     */
    public function __construct($manager = true)
    {
        $this->_manager = $manager;
        $this->_model = new AdvanceWithdrawal();
        $this->_advanceRepaymentModel = new AdvanceWithdrawalRepayment();
        $this->_fields = [ 'amount', 'payment_date', 'note' ];
    }
    
    /**
     * set manager id
     * 
     * @param int $id
     */
    public function setManagerId($id)
    {
        $this->_managerId = $id;
    }
    
    /**
     * manage auction chit
     * 
     * @param \Web\Mvc\AjaxResponse $response
     * @param int $id
     * @return array
     */
    public function run($response, $id)
    {
        $this->_response = $response;
        $this->_response->message('Invalid request');
        $this->_loadAjaxForm();
        if (! $this->_model->hasId($this->_formValues['id'])) {
            return $this->_result;
        }
        
        if ($id != $this->_formValues['id']) {
            return $this->_result;
        }
        
        $this->_id = $this->_formValues['id'];
        $this->_advance = $this->_model->find($this->_id);
        if ($this->_advance->closed > 0) {
            $this->_setError(['Advance already closed']);
            return $this->_result;
        }
        
        if (0 == $this->_advance->overdue_balance) {
            $this->_setError(['No overdue balance exists']);
            return $this->_result;
        }
        
        $valid = $this->_validate();
        if (! $valid) {
            return $this->_result;
        }
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_savePayment();
            $this->_model->getAdapter()->commit();
            $this->_saved('Repayment', true);
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
        }  catch (\ErrorException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
        
        return $this->_result;
    }
    
    /**
     * validate payment
     * 
     * @return boolean
     */
    private function _validate()
    {
        if ($this->_notNumber($this->_formValues['amount'], false)) {
            $this->_setError(['Amount is invalid']);
            return false;
        }
        
        if ($this->_formValues['amount'] < 1 || $this->_formValues['amount'] > $this->_advance->overdue_balance) {
            $this->_setError(['Amount is invalid or out of limit']);
            return false;
        }
        
        $this->_paymentDate = DateHelper::fromDatepicker($this->_formValues['payment_date']);
        
        if (DateHelper::getDateTime($this->_advance->advance_date) > DateHelper::getDateTime($this->_paymentDate)) {
            $this->_setError(['Payment date should be after advance date']);
            return false;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('advance_withdrawal_id', $this->_id);
        if ($this->_advanceRepaymentModel->selectTotal($sql->getWhere()) > 0) {
            $sql->whereGreater('payment_date', $this->_paymentDate);
            if ($this->_advanceRepaymentModel->selectTotal($sql->getWhere()) > 0) {
                $this->_setError(['Repayment exists after selected Payment date']);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * save repayment
     */
    private function _savePayment()
    {
        $repayment = [
            'advance_withdrawal_id' => $this->_id,
            'amount' => $this->_formValues['amount'],
            'payment_date' => $this->_paymentDate,
            'overdue_repayment' => $this->_formValues['amount'],
            'note' => $this->_formValues['note'],
            'deduction_bill_date' => $this->_paymentDate,
            'adjusted' => 1,
            'deduction_bill_adjusted' => 0,
            'created_at' => DateHelper::timestamp(),
            'created_by' => $this->_managerId,
        ];
        
        $deductionMemberDailyModel = new DeductionMemberDaily();
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_advance->member_id)
            ->whereId('deduction_type_id', $this->_advance->deduction_type_id)
            ->whereLess('deduction_date', $this->_paymentDate, true);
        $where = $sql->getWhere();
        if ($deductionMemberDailyModel->selectTotal($where) > 0) {
            $entry = $deductionMemberDailyModel->selectRow($where, 'deduction_date DESC');
            $repayment['deduction_bill_date'] = $entry->deduction_date;
        }
        
        $this->_advanceRepaymentModel->insert($repayment);
        
        $update = [
            'repaid' => $this->_advance->repaid + $repayment['amount'],
            'balance' => $this->_advance->balance - $repayment['amount'],
            'overdue_balance' => $this->_advance->overdue_balance - $repayment['overdue_repayment'],
        ];
        
        if (0 == $update['overdue_balance']) {
            $update['closed'] = 1;
            $update['closing_date'] = $this->_paymentDate;
        }
        
        $this->_model->updateById($update, $this->_id);
    }
    
    
    /**
     * load ajax form
     * 
     * @param array $fields
     */
    private function _loadAjaxForm()
    {
        $this->_form = new AjaxForm();
        $this->_form->addFields($this->_fields);
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
     * set response error
     * 
     * @param array $errors
     */
    private function _setError($errors)
    {
        $this->_response->message(implode('<br>', $errors));
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
     * check for number
     * 
     * @param string $number
     * @param boolean $zeroAllowed
     * @return boolean
     */
    private function _notNumber($number, $zeroAllowed = false)
    {
        if ($zeroAllowed) {
            if ('0' == $number) {
                return false;
            }
        }
        
        return 0 == preg_match('/^[1-9]\d*$/', $number);
    }
    
}
