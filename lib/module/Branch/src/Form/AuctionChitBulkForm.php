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

use Shared\Utility\AuctionSummary;


class AuctionChitBulkForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    

    public function __construct()
    {
        parent::__construct();
        $fields = ['auction_date', 'donor_id'];
        $multiple = [
            'member', 'medicine', 'amount', 'quantity', 'advance', 'balance', 
        ];
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
        
        if ($this->validator->isNull($this->_formValues['auction_date'])) {
            $this->error->notSelected('Date');
        }
        
        if ($this->validator->notSelected($this->_formValues['donor_id'])) {
            $this->error->notSelected('Donor');
        }
        
        $this->_validateEntries();
        
        if ($this->noError()) {
            $this->_checkDeductionBill();
        }
        
        return $this->noError();
    }
    
    /**
     * create auction chit
     * 
     * @param int $managerId
     * @return int
     */
    public function create($managerId)
    {
        $now = DateHelper::timestamp();
        $defaultAuctionChit = [
            'auction_date' => DateHelper::fromDatepicker($this->_formValues['auction_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => 0,
            'advance' => 0,
            'created_by' => $managerId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        
        $insert = $this->getModel()->getInsertPreparedStatement(array_keys($defaultAuctionChit));
        
        $default = [
            'auction_chit_id' => 0,
            'medicine_id' => 0,
            'quantity' => 0,
            'amount' => 0,
            'created_at' => $now,
            'created_by' => $managerId,
            'updated_at' => $now,
            'updated_by' => $managerId,
        ];
        
        $entryModel = new AuctionChitEntry();
        $entry = $entryModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($this->_formValues['member'] as $index => $memberId) {
            $auctionChit = $defaultAuctionChit;
            $auctionChit['member_id'] = $memberId;
            $auctionChit['advance'] = intval($this->_formValues['advance'][$index]);
            $insert->execute(array_values($auctionChit));
            $id = $this->getModel()->getAdapter()->lastInsertId();
            
            $record = $default;
            $record['auction_chit_id'] = $id;
            $record['medicine_id'] = $this->_formValues['medicine'][$index];
            $record['amount'] = intval($this->_formValues['amount'][$index]);
            $record['quantity'] = intval($this->_formValues['quantity'][$index]);
            $entry->execute(array_values($record));
            
            $summary = new AuctionSummary($managerId);
            $summary->created($id);
        }

        return true;
    }
    
    /*
     * validate entries
     */
    private function _validateEntries()
    {
        if (0 == count($this->_formValues['member'])) {
            $this->error->add('No member entries added');
            
            return;
        }
        
        $counter = 0;
        foreach ($this->_formValues['member'] as $index => $memberId) {
            ++$counter;
            if ($this->validator->notSelected($memberId)) {
                $this->error->add('Entry ' . $counter . ': Member not selected');
            }
            
            if ($this->validator->notSelected($this->_formValues['medicine'][$index])) {
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
            
            if ($this->validator->isNull($this->_formValues['advance'][$index])) {
                $this->error->add('Entry ' . $counter . ': Advance required');
            } elseif (! $this->validator->isPositiveNumber($this->_formValues['advance'][$index])) {
                $this->error->add('Entry ' . $counter . ': Advance is invalid');
            } elseif ($this->_formValues['advance'][$index] > $this->_formValues['amount'][$index]) {
                $this->error->add('Entry ' . $counter . ': Advance is more than Auction Amount');
            }
        }
    }
    
    /**
     * validate quantity
     * 
     * @param string $value
     * @return boolean
     */
    private function _validateQuantity($value)
    {
        if ($this->validator->isNull($value)) {
            return true;
        }
        
        return $this->validator->integer($value);
    }
    
    private function _checkDeductionBill()
    {
        $deductionBIllModel = new DeductionBill();
        $sql = $this->getModel()->getSql();
        $acutionDate = DateHelper::fromDatepicker($this->_formValues['auction_date']);
        $counter = 0;
        
        foreach ($this->_formValues['member'] as $memberId) {
            ++$counter;
            $sql->clear();
            $sql->whereId('member_id', $memberId)
                ->whereGreater('deduction_date', $acutionDate, true);
            if ($deductionBIllModel->selectTotal($sql->getWhere()) > 0) {
                $this->error->add('Entry ' . $counter . ': Deduction bill exists on or after selected date');
            }
        }
    }
    
}
