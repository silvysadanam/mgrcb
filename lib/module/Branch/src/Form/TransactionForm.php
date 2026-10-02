<?php
/**
 * Branch TransactionForm Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Shared\Utility\AccountsHelper;
use Model\RPTransMemberDetails;

use Shared\Utility\AuctionSummary;


class TransactionForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var boolean
     */
    private $_allowDuplicate = true;

    public function __construct()
    {
        parent::__construct();
        $fields = ['receipt_date', 'receipt_number', 'donor_id', 'member_id', 'non_member', 'payment_type', 'note', 'bank_id'];
        $multiple = ['head', 'amount', 'total'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
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
        if ($this->validator->isNull($this->_formValues['receipt_date'])) {
            $this->error->notSelected('Date');
        }
        if ($this->validator->notSelected($this->_formValues['donor_id']) && $this->validator->notSelected($this->_formValues['member_id']) && $this->validator->isNull($this->_formValues['non_member'])) {
            $this->error->notSelected('Donor/Member/Non Member option');
        }
        
        if($this->_formValues['payment_type'] == 'Bank') {
            if ($this->validator->notSelected($this->_formValues['bank_id'])) {
                $this->error->notSelected('Bank option');
            }
        }
        
        $this->_validateEntries();
        
        return $this->noError();
    }
    
    /**
     * create receipt
     * 
     * @param int $managerId
     * @return int
     */
    public function create($managerId, $fmc_id)
    {
        $now = DateHelper::timestamp();
        $transBy =0;
        $paymentType = $this->_formValues['payment_type'];
        if($paymentType === 'Bank'){
            $transBy = $this->_formValues['bank_id'];
        }
        $insert = [
            'receiptNo' => $this->_formValues['receipt_number'],
            'transDate' => DateHelper::fromDatepicker($this->_formValues['receipt_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'non_member' => $this->_formValues['non_member'],
            'remarks' =>  $this->_formValues['note'],
            'created_by' => $managerId,
            'fmc_id' => $fmc_id,
            'transBy' => $transBy,
            'transType' => 'R',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $id = $this->save($insert);
        
        $default = [
            'transID' => $id,
            'headID' => 0,
            'amount' => 0,
            'remarks' => ''
        ];
        $accountHelper = new AccountsHelper($fmc_id);
        $heads = $accountHelper->getHeads();
        $accountHeads = [];
        foreach ($heads as $head) {
            $accountHeads[$head->id] = $head->head_name;
        }
        $entryModel = new RPTransMemberDetails();
        $entry = $entryModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($this->_formValues['head'] as $index => $headId) {
            $record = $default;
            $record['headID'] = $headId;
            $record['amount'] = floatval($this->_formValues['amount'][$index]);
            $record['remarks'] = $accountHeads[$headId];
            $entry->execute(array_values($record));
        }
        return $id;
    }
    
   
    
    private function _validateEntries()
    {
        if (0 == count($this->_formValues['head'])) {
            $this->error->add('No entries added');
            
            return;
        }
        
        $counter = 0;
        foreach ($this->_formValues['head'] as $index => $fishId) {
            ++$counter;
            if ($this->validator->notSelected($fishId)) {
                $this->error->add('Entry ' . $counter . ': Head not selected');
            }
            
            if ($this->validator->isNull($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount required');
            } elseif (!is_numeric($this->_formValues['amount'][$index]) || $this->_formValues['amount'][$index] <= 0) {
                $this->error->add('Entry ' . $counter . ': Amount is invalid');
            }
        }
    }
}
