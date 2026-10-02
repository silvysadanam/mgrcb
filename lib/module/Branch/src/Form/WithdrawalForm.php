<?php
/**
 * Branch Withdrawal Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{DeductionBill, DeductionBillEntry};


class WithdrawalForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var \Model\DeductionBIll
     */
    private $_deductionBillModel;
    
    /**
     * 
     * @var \Model\DeductionBIllEntry
     */
    private $_deductionBillEntryModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_entry;
    
    /**
     * 
     * @var \stdClass
     */
    private $_bill;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'member_id', 'withdrawal_amount', 'withdrawal_date', 
            'deduction_type_id', 'description', 'note',
        ];
        
        $this->addFields($fields);
        
        $this->_deductionBillModel = new DeductionBill();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        
        if ($this->validator->isNull($this->_formValues['withdrawal_date'])) {
            $this->error->required('Date');
        }
        
        if ($this->validator->notSelected($this->_formValues['member_id'])) {
            $this->error->notSelected('Member');
        }
        
        if ($this->validator->notSelected($this->_formValues['deduction_type_id'])) {
            $this->error->notSelected('Deduction type');
        }
        
        if (! $this->_validateEntry($this->_formValues['withdrawal_amount'])) {
            $this->error->invalid('Amount');
        }
        
        if ($this->validator->isNull($this->_formValues['description'])) {
            $this->error->required('Description');
        }
        
        if ($this->noError()) {
            $sql = $this->_deductionBillModel->getSql();
            $sql->whereId('member_id', $this->_formValues['member_id'])
                ->whereId('deduction_type_id', $this->_formValues['deduction_type_id'])
                ->whereGreater('withdrawal_date', DateHelper::fromDatepicker($this->_formValues['withdrawal_date']), true);
            if ($this->getModel()->selectTotal($sql->getWhere()) > 0) {
                $this->error->add('Withdrawal exists on or after selected date');
            }
        }
        
        if ($this->noError()) {
            $sql = $this->_deductionBillModel->getSql();
            $sql->whereId('member_id', $this->_formValues['member_id'])
                ->whereLess('deduction_date', DateHelper::fromDatepicker($this->_formValues['withdrawal_date']), true);
            $where = $sql->getWhere();
            if (0 == $this->_deductionBillModel->selectTotal($where)) {
                $this->error->add('Previus deduction bill not found');
            } else {
                $this->_bill = $this->_deductionBillModel->selectRow($where, 'deduction_date DESC');
                $sql->clear();
                $sql->whereId('deduction_bill_id', $this->_bill->id)
                    ->whereId('deduction_type_id', $this->_formValues['deduction_type_id']);
                $where2 = $sql->getWhere();
                if (0 == $this->_deductionBillEntryModel->selectTotal($where2)) {
                    $this->error->add('Previous deduction bill closing balance not found');
                } else {
                    $this->_entry = $this->_deductionBillEntryModel->selectRow($where2);
                    if ($this->_formValues['withdrawal_amount'] > $this->_entry->closing_balance) {
                        $this->error->add('Amount exceeds available balance');
                    }
                }
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create withdrawal
     * 
     * @return int
     */
    public function create($userId)
    {
        $insert = [
            'member_id' => $this->_formValues['member_id'],
            'deduction_type_id' => $this->_formValues['deduction_type_id'],
            'withdrawal_date' => DateHelper::fromDatepicker($this->_formValues['withdrawal_date']),
            'withdrawal_amount' => $this->_formValues['withdrawal_amount'],
            'deduction_bill_date' => $this->_bill->deduction_date,
            'adjusted' => 1,
            'description' => $this->_formValues['description'],
            'note' => $this->_formValues['note'],
            'created_at' => DateHelper::timestamp(),
            'created_by' => $userId,
        ];
        
        $id = $this->save($insert);
        
        $deduction = $this->_formValues['withdrawal_amount'];
        $balance = $this->_entry->closing_balance - $deduction;
        
        $update = [
            'withdrawal' => $this->_entry->withdrawal + $deduction,
            'closing_balance' => $balance,
        ];
        
        $this->_deductionBillEntryModel->updateById($update, $this->_entry->id);
        $this->_adjustForwardBalance($deduction);
        
        return $id;
    }
    
    /**
     * adjust balances after withdrawal
     * 
     * @param int $deduction
     * @return null
     */
    private function _adjustForwardBalance($deduction)
    {
        $sql = $this->_deductionBillModel->getSql();
        $sql->whereId('member_id', $this->_formValues['member_id'])
            ->whereGreater('deduction_date', $this->_bill->deduction_date);
        if (0 == $this->_deductionBillModel->selectTotal($sql->getWhere())) {
            return;
        }
        
        $sql->clear();
        $sql->whereId('b.member_id', $this->_formValues['member_id'])
            ->whereGreater('b.deduction_date', $this->_bill->deduction_date)
            ->whereId('e.deduction_type_id', $this->_formValues['deduction_type_id']);
        $where = $sql->getWhere();
        $query = 'SELECT `e`.`id`, `e`.`opening_balance`, `e`.`closing_balance` FROM '
               . $this->_deductionBillEntryModel->getTableQuoted() . ' `e` '
               . 'INNER JOIN ' . $this->_deductionBillModel->getTableQuoted() . ' `b` '
               . 'ON `b`.`id` = `e`.`deduction_bill_id` '
               . 'WHERE ' . $where . ' ORDER BY `e`.`id`';
        $list = $this->_deductionBillModel->queryRows($query);
        if (empty($list)) {
            return;
        }
        
        $query2 = 'UPDATE ' . $this->_deductionBillEntryModel->getTableQuoted()
                . ' SET `opening_balance` = ?, `closing_balance` = ?'
                . ' WHERE `id` = ? LIMIT 1';
        $update = $this->_deductionBillEntryModel->prepare($query2);
        
        foreach ($list as $item) {
            $entry = [0, 0, $item->id];
            if ($item->opening_balance >= $deduction) {
                $entry[0] = $item->opening_balance - $deduction;
            }
            if ($item->closing_balance >= $deduction) {
                $entry[1] = $item->closing_balance - $deduction;
            }
            $update->execute($entry);
        }
    }
    
    /**
     * validate entry
     * 
     * @param string $value
     * @return boolean
     */
    private function _validateEntry($value)
    {
        if ($this->validator->isNull($value)) {
            return false;
        }
        
        if ('0' == $value) {
            return false;
        }
        
        return $this->validator->isPositiveNumber($value);
    }
    
}
