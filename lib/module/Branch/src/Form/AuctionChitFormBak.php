<?php
/**
 * Branch AuctionChit Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\AuctionChitEntry;
use Model\DeductionBill;
use Model\{RPTransMemberDetails, RPTransMember, RPHeads};
use Shared\Utility\AuctionSummary;
use Shared\Utility\AccountsHelper;

class AuctionChitForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    private $_headModel;
    /**
     * 
     * @var boolean
     */
    private $_allowDuplicate = true;

    public function __construct()
    {
        parent::__construct();
        $fields = ['auction_date', 'donor_id', 'member_id', 'advance', 'balance'];
        $multiple = ['medicine', 'amount', 'quantity', 'total'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
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
        
        if ($this->validator->isNull($this->_formValues['auction_date'])) {
            $this->error->notSelected('Date');
        }
        
        if ($this->validator->notSelected($this->_formValues['donor_id'])) {
            $this->error->notSelected('Donor');
        }
        
        if ($this->validator->notSelected($this->_formValues['member_id'])) {
            $this->error->notSelected('Member');
        }
        
        $this->_validateAdvance();
        $this->_validateEntries();
        if ($this->noError()) {
            if ($this->_formValues['advance'] > $this->_formValues['total'][0]) {
                $this->error->add('Advance exceeds total Amount');
            }
        }
        
        if (! $this->_allowDuplicate) {
            if ($this->noError()) {
                $sql = $this->getModel()->getSql();
                $sql->whereEqual('auction_date', DateHelper::fromDatepicker($this->_formValues['auction_date']))
                    ->whereId('donor_id', $this->_formValues['donor_id'])
                    ->whereId('member_id', $this->_formValues['member_id']);
                if ($this->getModel()->selectTotal($sql->getWhere()) > 0) {
                    $this->error->exists('Auction chit');
                }    
            }
        }
        
        if ($this->noError()) {
            $deductionBIllModel = new DeductionBill();
            $sql = $this->getModel()->getSql();
            $sql->whereId('member_id', $this->_formValues['member_id'])
                ->whereGreater('deduction_date', DateHelper::fromDatepicker($this->_formValues['auction_date']), true);
            if ($deductionBIllModel->selectTotal($sql->getWhere()) > 0) {
                $this->error->add('Deduction bill exists on or after selected date');
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create auction chit
     * 
     * @param int $managerId
     * @return int
     */
    public function create($managerId, $fmc_id = 0)
    {
        $now = DateHelper::timestamp();
        $insert = [
            'auction_date' => DateHelper::fromDatepicker($this->_formValues['auction_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'advance' => intval($this->_formValues['advance']),
            'created_by' => $managerId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $id = $this->save($insert);
        
        $default = [
            'auction_chit_id' => $id,
            'medicine_id' => 0,
            'quantity' => 0,
            'amount' => 0,
            'created_at' => $now,
            'created_by' => $managerId,
            'updated_at' => $now,
            'updated_by' => $managerId,
        ];
        //Account entries start here
        //Receipt
        $heads = $this->_headModel->checkByColumnByFmc('head_name', 'Auction Chit', $fmc_id);
        if(isset($heads->id)){
            $headID = $heads->id;
            $headName = $heads->head_name;
        } else {
            //create new head
            $insert = [];
            $insert['head_name'] = 'Auction Chit';
            $insert['head_alias'] = 'Auction Chit';
            $insert['code'] = 'auct_chit';
            $insert['transType'] = 'Indirect Income';
            $insert['opb'] = 0;
            $insert['fmc_id'] = $fmc_id;
            $insert['status'] = 'N';
            $headID = $this->_headModel->insert($insert);
            $headName =  'Auction Chit';
        }
        //Payment head
         $paymentHead = $this->_headModel->checkByColumnByFmc('head_name', 'Auctioner Advance', $fmc_id);
        if(isset($paymentHead->id)){
            $pheadID = $paymentHead->id;
            $pheadName = $paymentHead->head_name;
        } else {
            //create new head
            $insert = [];
            $insert['head_name'] = 'Auctioner Advance';
            $insert['head_alias'] = 'Auctioner Advance';
            $insert['code'] = 'auctioner_advance';
            $insert['transType'] = 'Indirect Expense';
            $insert['opb'] = 0;
            $insert['fmc_id'] = $fmc_id;
            $insert['status'] = 'N';
            $pheadID = $this->_headModel->insert($insert);
            $pheadName =  'Auctioner Advance';
        }
        //Receipt Entries
        $masterModel = new RPTransMember();
        $insert = [
            'action_chit_id' => $id,
            'fmc_id' => $fmc_id,
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'transType' =>'P',
            'transBy' => 0,
            'transDate' => DateHelper::fromDatepicker($this->_formValues['auction_date'])
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        $insert = [];
        $insert = [
            'transID' => $transid,
            'headID' => $headID,
            'amount' => intval($this->_formValues['advance']),
            'remarks' => $headName
        ];
        $dataModel->insert($insert); 
        $insert = [];
        // Payment Entries
        $masterModel = new RPTransMember();
        $insert = [
            'action_chit_id' => $id,
            'fmc_id' => $fmc_id,
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'transType' =>'P',
            'transBy' => 0,
            'transDate' => DateHelper::fromDatepicker($this->_formValues['auction_date'])
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        $insert = [];
        $insert = [
            'transID' => $transid,
            'headID' => $pheadID,
            'amount' => intval($this->_formValues['balance']),
            'remarks' => $pheadName
        ];
        $dataModel->insert($insert); 
        //Accounts transactions ends here
        $entryModel = new AuctionChitEntry();
        $entry = $entryModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($this->_formValues['medicine'] as $index => $medicineId) {
            $record = $default;
            $record['medicine_id'] = $medicineId;
            $record['amount'] = intval($this->_formValues['amount'][$index]);
            $record['quantity'] = intval($this->_formValues['quantity'][$index]);
            $entry->execute(array_values($record));
        }
        
        $summary = new AuctionSummary($managerId);
        $summary->created($id);

        return $id;
    }
    
    private function _validateAdvance()
    {
        if ($this->validator->notNull($this->_formValues['advance'])) {
            if (! $this->validator->integer($this->_formValues['advance'])) {
                $this->error->invalid('Advance');
            }
        }
    }
    
    private function _validateEntries()
    {
        if (0 == count($this->_formValues['medicine'])) {
            $this->error->add('No entries added');
            
            return;
        }
        
        $counter = 0;
        foreach ($this->_formValues['medicine'] as $index => $medicineId) {
            ++$counter;
            if ($this->validator->notSelected($medicineId)) {
                $this->error->add('Entry ' . $counter . ': Medicine not selected');
            }
            
            if ($this->validator->isNull($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount required');
            } elseif (! $this->validator->nonZeroInteger($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount is invalid');
            }
            
            if (! $this->_validateQuantity($this->_formValues['quantity'][$index])) {
                $this->error->add('Entry ' . $counter . ': Weight is invalid');
            }
        }
    }
    
    private function _validateQuantity($value)
    {
        if ($this->validator->isNull($value)) {
            return true;
        }
        
        return $this->validator->integer($value);
    }
    
}
