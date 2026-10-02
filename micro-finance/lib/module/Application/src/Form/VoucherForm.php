<?php
/**
 * Branch TransactionForm Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Application\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Shared\Utility\AccountsHelper;
use Model\{RPTransMemberDetails, Fmc, RPHeads};
use Shared\Utility\AuctionSummary;
use Shared\Utility\Transactions;
use Shared\Utility\AccountSettings;

class VoucherForm extends Form
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
        $fields = ['receipt_date', 'page_no', 'code', 'committee_approval', 'voucher_number', 'donor_id', 'member_id', 'non_member', 'payment_type', 'note', 'bank_id', 'transfer_id'];
        $multiple = ['head', 'amount', 'total'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
        $this->_headModel =  new RPHeads();
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
       // if ($this->validator->isNull($this->_formValues['page_no'])) {
       //     $this->error->notSelected('Page No');
       // }
        if ($this->validator->notSelected($this->_formValues['donor_id']) && $this->validator->notSelected($this->_formValues['member_id']) && $this->validator->isNull($this->_formValues['non_member'])) {
            $this->error->notSelected('Auctioneer/Member/Non Member option');
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
        $bank_heads = [];
        $paymentType = $this->_formValues['payment_type'];
        if($paymentType === 'Bank'){
            $transBy = $this->_formValues['bank_id'];
            $bankHeads = $this->_headModel->checkByColumnByFmc('id', $transBy, $fmc_id);
            $bank_heads['head_name'] = $bankHeads->head_name;
            $bank_heads['code'] = $bankHeads->code;
        }
        if(!$this->_formValues['page_no']){
            $this->_formValues['page_no'] = 0;
        }
         //get Head details
        $rpheads = $this->_headModel->checkByColumnByFmc('id', $this->_formValues['head'][0], $fmc_id);
        $heads['head_name'] = $rpheads->head_name;
        $heads['code'] = $rpheads->code;
        $fmcModel = new Fmc();
        $fmc = $fmcModel->find($fmc_id);
        $insert = [
            'transNo' => $this->_formValues['voucher_number'],
            'transDate' => DateHelper::fromDatepicker($this->_formValues['receipt_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'non_member' => $this->_formValues['non_member'],
            'amount'  => $this->_formValues['amount'][0],
            'headID' => $this->_formValues['head'][0],
            'particulars' =>  $this->_formValues['note'],
            'code_no' =>  $this->_formValues['code'],
            'page_no' =>  $this->_formValues['page_no'],
            'committee_approval' =>  $this->_formValues['committee_approval'],
            'created_by' => $managerId,
            'fmc_id' => $fmc_id,
            'transBy' => $transBy,
            'transType' => 'P',
            'created_at' => $now,
            'updated_at' => $now,
            'trans_to' => $this->_formValues['transfer_id']            
        ];
        $id = $this->save($insert);
        
        if($this->_formValues['transfer_id'] > 0){           
            $trans = new Transactions();            
            $branch_id = $this->_formValues['transfer_id'];
            $bank_heads['transType'] = 'Bank';            
            if($transBy > 0){
               $bankDetails = $trans->getHeadID($bank_heads, $branch_id);
               $transBy = $bankDetails['id'];
            }
            $particulars = $heads['head_name'] ." - Fund Transfer";
            $heads['code'] = "inc_".str_replace(" ", "_", $rpheads->head_name);
            $heads['transType'] = 'Direct Income';
            $options = new AccountSettings($branch_id);
            $options->load();
            $receiptNo = $options->getNextFWSReceiptNo();
            $receipt = [
                'receipt' => $receiptNo,
                'transDate' => DateHelper::fromDatepicker($this->_formValues['receipt_date']),
                'non_member' => $fmc->name,
                'member_id' => 0,
                'amount'  => $this->_formValues['amount'][0],
                'remarks' => $particulars,
                'created_by' => $managerId,
                'transBy' => $transBy,
                'created_at' => $now,
                'updated_at' => $now,
                'trans_from' => $fmc_id,
                'voucher_id' => $id
            ];
            $trans->generateReceipt($heads, $receipt, $branch_id);
        }
        
        /*$default = [
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
            $record['amount'] = intval($this->_formValues['amount'][$index]);
            $record['remarks'] = $accountHeads[$headId];
            $entry->execute(array_values($record));
        }*/
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
            } elseif (! $this->validator->nonZeroInteger($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount is invalid');
            }
        }
    }
}
