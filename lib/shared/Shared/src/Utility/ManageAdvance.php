<?php
/**
 * Shared ManageAdvance Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Advance, AdvanceRepayment, DeductionBill, DeductionBillEntry};

use Shared\Form\AjaxForm;


use Web\Utility\DateHelper;


class ManageAdvance
{
    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\Advance
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AdvanceRepayment
     */
    private $_advanceRepaymentModel;
    
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
     * 
     * @var \stdClass
     */
    private $_bill;
    
    /**
     * 
     * @var \stdClass
     */
    private $_entry;
    
    /**
     * constructor
     * 
     * @param boolean $manager
     */
    public function __construct($manager = true)
    {
        $this->_manager = $manager;
        $this->_model = new Advance();
        $this->_advanceRepaymentModel = new AdvanceRepayment();
        $this->_deductionBillModel = new DeductionBill();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
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
     * @return array
     */
    public function run($response)
    {
        $this->_response = $response;
        $this->_response->message('Invalid request');
        $this->_loadAjaxForm();
        if (! $this->_model->hasId($this->_formValues['id'])) {
            return $this->_result;
        }
        
        $this->_id = $this->_formValues['id'];
        $this->_advance = $this->_model->find($this->_id);
        if ($this->_advance->closed > 0) {
            $this->_setError(['Advance already closed']);
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
        
        if ($this->_formValues['amount'] < 1 || $this->_formValues['amount'] > $this->_advance->balance) {
            $this->_setError(['Amount is invalid']);
            return false;
        }
        
        $this->_paymentDate = DateHelper::fromDatepicker($this->_formValues['payment_date']);
        
        if (DateHelper::getDateTime($this->_advance->advance_date) > DateHelper::getDateTime($this->_paymentDate)) {
            $this->_setError(['Payment date should be after advance date']);
            return false;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('advance_id', $this->_id);
        if ($this->_advanceRepaymentModel->selectTotal($sql->getWhere()) > 0) {
            $sql->whereGreater('payment_date', $this->_paymentDate);
            if ($this->_advanceRepaymentModel->selectTotal($sql->getWhere()) > 0) {
                $this->_setError(['Repayment exists after selected Payment date']);
                return false;
            }
        }
        
        $sql->clear();
        $sql->whereId('member_id', $this->_advance->member_id)
            ->whereGreater('deduction_date', $this->_advance->deduction_bill_date)
            ->whereLess('deduction_date', $this->_paymentDate, true);
        
        if (0 == $this->_deductionBillModel->selectTotal($sql->getWhere())) {
            $this->_setError(['Deduction Bill for adjustment not found']);
            return false;
        }
        
        return true;
    }
    
    /**
     * save repayment
     */
    private function _savePayment()
    {
        $this->_loadDeductionBill();
        $repayment = [
            'advance_id' => $this->_id,
            'amount' => $this->_formValues['amount'],
            'payment_date' => $this->_paymentDate,
            'overdue_repayment' => 0,
            'deposit' => 0,
            'note' => $this->_formValues['note'],
            'deduction_bill_date' => $this->_bill->deduction_date,
            'adjusted' => 1,
            'deduction_bill_adjusted' => 0,
            'created_at' => DateHelper::timestamp(),
            'created_by' => $this->_managerId,
        ];
        
        if ($this->_advance->overdue_balance > 0) {
            if ($this->_formValues['amount'] > $this->_advance->overdue_balance) {
                $repayment['overdue_repayment'] = $this->_advance->overdue_balance;
                $repayment['deposit'] = $this->_formValues['amount'] - $repayment['overdue_repayment'];
            } else {
                $repayment['overdue_repayment'] = $this->_formValues['amount'];
            }
        } else {
            $repayment['deposit'] = $this->_formValues['amount'];
        }

        $this->_advanceRepaymentModel->insert($repayment);
        
        $update = [
            'repaid' => $this->_advance->repaid + $repayment['amount'],
            'balance' => $this->_advance->balance - $repayment['amount'],
        ];
        
        if ($repayment['overdue_repayment'] > 0) {
            $update['overdue_balance'] = $this->_advance->overdue_balance - $repayment['overdue_repayment'];
        }
        
        if (0 == $update['balance']) {
            $update['closed'] = 1;
            $update['closing_date'] = $this->_paymentDate;
        }
        
        $this->_model->updateById($update, $this->_id);
        if ($repayment['deposit'] > 0) {
            $this->_updateDeductionBill($repayment['deposit']);
        }
    }
    
    /**
     * update deduction bill
     * 
     * @param int $deposit
     */
    private function _updateDeductionBill($deposit)
    {
        $update = [
            'deposit' => $deposit,
            'closing_balance' => $this->_entry->closing_balance + $deposit,
        ];
        
        $this->_deductionBillEntryModel->updateById($update, $this->_entry->id);
        $this->_adjustForwardBalance($deposit);
    }
    
    /**
     * adjust balances after advance payment
     * 
     * @param int $deposit
     * @return null
     */
    private function _adjustForwardBalance($deposit)
    {
        $sql = $this->_deductionBillModel->getSql();
        $sql->whereId('member_id', $this->_advance->member_id)
            ->whereGreater('deduction_date', $this->_bill->deduction_date);
        if (0 == $this->_deductionBillModel->selectTotal($sql->getWhere())) {
            return;
        }
        
        $sql->clear();
        $sql->whereId('b.member_id', $this->_advance->member_id)
            ->whereGreater('b.deduction_date', $this->_bill->deduction_date)
            ->whereId('e.deduction_type_id', $this->_entry->deduction_type_id);
        $where = $sql->getWhere();
        $query = 'SELECT `e`.`id` FROM '
               . $this->_deductionBillEntryModel->getTableQuoted() . ' `e` '
               . 'INNER JOIN ' . $this->_deductionBillModel->getTableQuoted() . ' `b` '
               . 'ON `b`.`id` = `e`.`deduction_bill_id` '
               . 'WHERE ' . $where . ' ORDER BY `e`.`id`';
        $list = $this->_deductionBillModel->queryColumns($query);
        if (empty($list)) {
            return;
        }
        
        $sql->clear();
        $sql->whereIn('id', $list);
        
        $query2 = 'UPDATE ' . $this->_deductionBillEntryModel->getTableQuoted()
                . ' SET `opening_balance` = `opening_balance` + ' . $deposit . ', '
                . '`closing_balance` = `closing_balance` + ' . $deposit 
                . ' WHERE ' . $sql->getWhere() . ' LIMIT ' . count($list);
        $this->_deductionBillEntryModel->exec($query2);
    }
    
    /**
     * load deduction bill and entry
     * 
     * @return null
     */
    private function _loadDeductionBill()
    {
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_advance->member_id)
            ->whereLess('deduction_date', $this->_paymentDate, true);
        
        $this->_bill = $this->_deductionBillModel->selectRow($sql->getWhere(), 'deduction_date DESC');
        
        $sql->clear();
        $sql->whereId('deduction_bill_id', $this->_bill->id)
            ->whereId('deduction_type_id', $this->_advance->deduction_type_id);
        $this->_entry = $this->_deductionBillEntryModel->selectRow($sql->getWhere());
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
