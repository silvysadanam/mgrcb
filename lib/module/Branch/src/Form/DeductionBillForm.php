<?php
/**
 * Branch DeductionBill Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{DeductionBillEntry, InitialDeductionBalance};
use Model\{Advance, AdvanceRepayment, DeductionBillNote};

use Shared\Utility\DeductionHelper;


class DeductionBillForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var int
     */
    private $_memberId;
    
    /**
     * 
     * @var \Model\DeductionBIllEntry
     */
    private $_deductionBIllEntryModel;
    
    /**
     * 
     * @var \Model\InitialDeductionBalance
     */
    private $_initialDeductionBalanceModel;
    
    /**
     * 
     * @var \Model\Advance
     */
    private $_advanceModel;
    
    /**
     * 
     * @var \Model\AdvanceRepayment
     */
    private $_advanceRepaymentModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_daily;
    
    /**
     * 
     * @var int
     */
    private $_deductionBillId;
    
    /**
     * 
     * @var array
     */
    private $_advances = [];
    
    /**
     * 
     * @var int
     */
    private $_totalAvailable = 0;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'auction_amount', 'total_deduction', 'balance', 'advance', 'available',
            'member_paid_amount', 'total_available', 'note',
        ];
        $multiple = ['deduction_item', 'deduction_amount'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
        
        $this->_deductionBIllEntryModel = new DeductionBillEntry();
        $this->_initialDeductionBalanceModel = new InitialDeductionBalance();
        $this->_advanceModel = new Advance();
        $this->_advanceRepaymentModel = new AdvanceRepayment();
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
        
        if (! $this->_validateEntry($this->_formValues['member_paid_amount'])) {
            $this->error->invalid('Amount paid by Member');
        }
        
        $deductionTypes = DeductionHelper::deductionTypes();
        foreach ($this->_formValues['deduction_item'] as $index => $type) {
            if (! $this->_validateEntry($this->_formValues['deduction_amount'][$index])) {
                $this->error->invalid('Deduction amount for ' . $deductionTypes[$type]);
            }
        }
        
        if ($this->_formValues['member_paid_amount'] > 0) {
            if ($this->_formValues['member_paid_amount'] > $this->_formValues['advance']) {
                $this->error->add('Amount paid by Member exceeds limit');
            }
            
            if ($this->_formValues['balance'] > 0) {
                $this->error->add('Balance after deductions should be zero');
            }
        }
        
        if ($this->noError()) {
            if ($this->_formValues['total_deduction'] > $this->_formValues['total_available']) {
                $this->error->add('Total deduction exceeds auction amount / available balance');
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create deduction bill
     * 
     * @param \stdClass $daily
     * @return int
     */
    public function create($daily, $userId)
    {
        $this->_daily = $daily;
        $this->_memberId = $daily->member_id;
        $this->_totalAvailable = ($daily->amount - $daily->advance) + $this->_formValues['member_paid_amount'];
        $insert = [
            'member_id' => $this->_memberId,
            'deduction_date' => $daily->auction_date,
            'auction_amount' => $daily->amount,
            'available_amount' => $this->_totalAvailable,
            'auction_chits' => $daily->entries,
            'created_at' => DateHelper::timestamp(),
            'created_by' => $userId,
        ];
        
        $this->_deductionBillId = $this->save($insert);
        
        $this->_loadAdvances();
        $this->_saveEntries();
        $this->_saveNote($userId);

        return $this->_deductionBillId;
    }
    
    /**
     * save entries
     */
    private function _saveEntries()
    {
        $default = [
            'deduction_bill_id' => $this->_deductionBillId,
            'deduction_type_id' => 0,
            'opening_balance' => 0,
            'amount' => 0,
            'closing_balance' => 0,
            'repayment' => 0,
        ];
        
        $entryInsert = $this->_deductionBIllEntryModel->getInsertPreparedStatement(array_keys($default));

        $totalDeduction = 0;
        foreach ($this->_formValues['deduction_item'] as $index => $type) {
            $record = $default;
            $record['deduction_type_id'] = $type;
            $deductionAmount = (int) $this->_formValues['deduction_amount'][$index];
            $record['amount'] = $deductionAmount;
            $record['opening_balance'] = $this->_getOpeningBalance($type);
            $record['closing_balance'] = $record['opening_balance'] + $deductionAmount;
            $collection = $this->_advanceCollection($record);
            if ($collection > 0) {
                $record['repayment'] = $collection;
                $record['closing_balance'] -= $collection;
            }
            
            $totalDeduction += $record['amount'];
            $entryInsert->execute(array_values($record));
        }
        
        $update = [
            'deduction_amount' => $totalDeduction,
            'deduction_balance' => $this->_totalAvailable - $totalDeduction,
        ];
        
        $this->getModel()->updateById($update, $this->_deductionBillId);
    }
    
    /**
     * save note
     * 
     * @param int $userId
     * @return null
     */
    private function _saveNote($userId)
    {
        if ('' == $this->_formValues['note']) {
            return;
        }
        
        $note = [
            'deduction_bill_id' => $this->_deductionBillId,
            'note' => $this->_formValues['note'],
            'created_by' => $userId,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $noteModel = new DeductionBillNote();
        $noteModel->insert($note);
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
            return true;
        }
        
        return $this->validator->isPositiveNumber($value);
    }
    
    /**
     * get opening balance
     * 
     * @param int $deductionTypeId
     * @return int
     */
    private function _getOpeningBalance($deductionTypeId)
    {
        $sql = $this->_deductionBIllEntryModel->getSql();
        $sql->whereId('d.member_id', $this->_memberId)
            ->whereId('e.deduction_type_id', $deductionTypeId);
        $where = $sql->getWhere();
        
        $from = ' FROM ' . $this->getModel()->getTableQuoted() . ' `d` '
              . 'INNER JOIN ' . $this->_deductionBIllEntryModel->getTableQuoted() 
              . ' `e` ON `e`.`deduction_bill_id` = `d`.`id` '
              . 'WHERE ' . $where;
            
        $query1 = 'SELECT COUNT(`d`.`id`)' . $from;
        
        if ($this->_deductionBIllEntryModel->queryTotal($query1) > 0) {
            $query2 = 'SELECT `e`.`closing_balance` ' . $from 
                    . ' ORDER BY `d`.`deduction_date` DESC LIMIT 1';
            
            return $this->_deductionBIllEntryModel->queryColumn($query2);
        }
        
        $sql->clear();
        $sql->whereId('member_id', $this->_memberId)
            ->whereId('deduction_type_id', $deductionTypeId);
        $where2 = $sql->getWhere();
        
        if ($this->_initialDeductionBalanceModel->selectTotal($where2) > 0) {
            return $this->_initialDeductionBalanceModel->selectColumn('opening_balance', $where2);
        }
        
        return 0;
    }
    
    /**
     * load advances
     * 
     * @return null
     */
    private function _loadAdvances()
    {
        $sql = $this->_advanceModel->getSql();
        $sql->whereId('member_id', $this->_memberId)
            ->whereZero('closed')
            ->whereGreater('overdue_balance', 0);
        $where = $sql->getWhere();
        if (0 == $this->_advanceModel->selectTotal($where)) {
            return;
        }
        
        $list = $this->_advanceModel->select($where, 'advance_date');
        foreach ($list as $item) {
            if (! isset($this->_advances[$item->deduction_type_id])) {
                $this->_advances[$item->deduction_type_id] = $item;
            }
        }
    }
    
    /**
     * advance collection
     * 
     * @param array $record
     * @return int
     */
    private function _advanceCollection($record)
    {
        if (0 == $record['amount']) {
            return 0;
        }
        
        $type = $record['deduction_type_id'];
        if (! isset($this->_advances[$type])) {
            return 0;
        }
        
        $advance = $this->_advances[$type];
        $overdue = $advance->overdue_balance;
        if ($overdue <= $record['amount']) {
            $collected = $overdue;
        } else {
            $collected = $record['amount'];
        }
        
        $repayment = [
            'advance_id' => $advance->id,
            'amount' => $collected,
            'payment_date' => $this->_daily->auction_date,
            'overdue_repayment' => $collected,
            'deduction_bill_date' => $this->_daily->auction_date,
            'adjusted' => 1,
            'note' => 'Overdue collection auto adjusted from Deduction Bill entry',
            'deduction_bill_adjusted' => 1,
        ];
        
        $this->_advanceRepaymentModel->insert($repayment);
        
        $update = [
            'repaid' => $advance->repaid + $collected,
            'balance' => $advance->balance - $collected,
            'overdue_balance' => $overdue - $collected,
        ];
        
        if (0 == $update['balance']) {
            $update['closed'] = 1;
            $update['closing_date'] = $this->_daily->auction_date;
        }
        
        $this->_advanceModel->updateById($update, $advance->id);
        
        return $collected;
    }
    
}
