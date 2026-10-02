<?php
/**
 * Branch Deduction Create Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Deduction
 */

namespace Branch\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Shared\Utility\AccountsHelper;
use Model\{Deduction, DeductionNote, DeductionType};
use Model\{RPTransMemberDetails, RPTransMember, RPHeads};
use Shared\Utility\{DeductionHelper, DeductionSupport};


class CreateForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var \Model\Deduction
     */
    private $_deductionModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_auctionChit;
     /**
     * 
     * @var \Model\DeductionType
     */
    private $_deductionTypeModel;
    private $_fmc_id;
    private $_headModel;
    
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
        
        $this->_deductionModel = new Deduction();
        $this->_deductionTypeModel = new DeductionType();
        $this->_headModel = new RPHeads();
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
        foreach ($this->_formValues['deduction_item'] as $type) {
            if (! $this->_validateEntry($this->_formValues['deduction_amount'][$type])) {
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
     * @param \stdClass $auctionChit
     * @return int
     */
    public function create($auctionChit, $userId, $fmc_id = 0)
    {
        $this->_auctionChit = $auctionChit;
        $this->_fmc_id = $fmc_id;
        $update = [
            'has_deduction_bill' => 1,
            'member_paid_amount' => $this->_formValues['member_paid_amount'],
            'deduction_amount' => $this->_formValues['total_deduction'],
            'deduction_balance' => $this->_formValues['balance'],
            'deduction_note' => $this->_formValues['note'],
        ];
        
        $this->getModel()->updateById($update, $this->_auctionChit->id);
        $this->_saveEntries();
        $this->_saveNote($userId);
        
        $account = new DeductionSupport($this->_auctionChit->id);
        $account->created();

        return true;
    }
    
    /**
     * save entries
     */
    private function _saveEntries()
    {
        $default = [
            'auction_chit_id' => $this->_auctionChit->id,
            'deduction_type_id' => 0,
            'deduction_amount' => 0,
        ];
        $accountHelper = new AccountsHelper($this->_fmc_id);
        $heads = $accountHelper->getHeads();
        $accountHeads = [];
        foreach ($heads as $head) {
            $accountHeads[$head->code] = $head->id;
        }
        $list = $this->_deductionTypeModel->select(null, 'priority');
        $deductionTypes = [];
        foreach ($list as $item) {
            $deductionTypes[$item->id]['code'] = $item->code;
            $deductionTypes[$item->id]['deduction_type'] = $item->deduction_type;
        }
        $entryInsert = $this->_deductionModel->getInsertPreparedStatement(array_keys($default));        
        
        $today = DateHelper::today();
         
        //Auctioner Collection
        $accountHelper->deductionReceiptTransactions($this->_auctionChit);
         // Payment Entries
        $masterModel = new RPTransMember($this->_auctionChit);
        $insert = [
            'action_chit_id' => $this->_auctionChit->id,
            'fmc_id' => $this->_fmc_id,
            'donor_id' => 0,
            'member_id' => $this->_auctionChit->member_id,
            'transType' =>'R',
            'transBy' => 0,
            'transDate' => $today
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        foreach ($this->_formValues['deduction_item'] as $type) {
            $headID = $accountHeads[$deductionTypes[$type]['code']];
            $record = $default;
            $record['deduction_type_id'] = $type;
            $record['deduction_amount'] = (int) $this->_formValues['deduction_amount'][$type];
            $entryInsert->execute(array_values($record));
            if((int) $this->_formValues['deduction_amount'][$type] >0 ){
            $insert = [
                'transID' => $transid,
                'headID' => $headID,
                'amount' => (int) $this->_formValues['deduction_amount'][$type],
                'remarks' => $deductionTypes[$type]['deduction_type']
            ];            
            $dataModel->insert($insert);      
            }
        }
         //Member Payment
        //$accountHelper->deductionPaymentTransactions($this->_auctionChit);
        $accountHelper->deductionPayment($this->_auctionChit); 
        //$balancePayment = $this->_formValues['balance'];     
        if($this->_auctionChit->balance > 0) {
            $accountHelper->deductionBalancePayment($this->_auctionChit);
        }
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
            'auction_chit_id' => $this->_auctionChit->id,
            'note' => $this->_formValues['note'],
            'created_by' => $userId,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $noteModel = new DeductionNote();
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
    
}
