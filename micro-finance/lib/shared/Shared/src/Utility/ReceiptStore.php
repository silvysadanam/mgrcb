<?php
/**
 * Shared ReceiptStore Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Member, Fmc};
use Model\{RPTransMemberDetails, RPTransMember, RPHeads};
use Web\Utility\DateHelper;

use Shared\Model\PairModel;


class ReceiptStore
{
    /**
     * 
     * @var \Model\RPTransMember
     */
    private $_model;
    
    /**
     * 
     * @var \Model\RPTransMemberDetails
     */
    private $_receiptEntryModel;
    private $_rpTransModel;
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;

    /**
     * 
     * @var string
     */
    private $_auctionDate;
    
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \stdClass
     */
    private $_receipt;
    private $_headModel;
    
    /**
     * 
     * @var array
     */
    private $_entries;
    
    /**
     * 
     * @var \stdClass
     */
    private $_member;
    
    /**
     * 
     * @var \stdClass
     */
    private $_donor;
    private $_getBankName;
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    private $_fmc_id;
    private $_number = 0;
    private $_amount = 0;
    private $_paymentType  = 'Cash';
    private $_hasDeductionBill = false;
    /**
     * constructor
     */
    public function __construct($id, $fmc_id = 0)
    {
        $this->_id = $id;
        $this->_model = new RPTransMember();
        $this->_rpTransModel = new RPTransMemberDetails();
        $this->_sql = $this->_model->getSql();
        $this->_headModel = new RPHeads();
        $this->_fmc_id = $fmc_id;
    }
    
    /**
     * load receipt
     */
    public function load()
    {
        $this->_receipt = $this->_model->find($this->_id);
        $this->_sql->whereId('transID', $this->_id);
        $this->_entries = $this->_rpTransModel->select($this->_sql->getWhere());
         
        $memberFields = ['id', 'name', 'code', 'fmc_id'];
        $memberModel = new Member();
        if($this->_receipt->member_id) {
            $this->_member = $memberModel->selectFieldsById($memberFields, $this->_receipt->member_id);
        }
        if($this->_receipt->donor_id){
            $this->_donor = $memberModel->selectFieldsById($memberFields, $this->_receipt->donor_id);
        }
        $this->_receiptDate = DateHelper::datepicker($this->_receipt->transDate);
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_receipt->fmc_id);
        $number = 0;
        $amount =0;
        foreach ($this->_entries as $item) {
            $number ++;
            $amount += $item->amount;
        }
        $this->_number = $number;
        $this->_amount = $amount;
        $this->_hasDeductionBill = $this->_receipt->action_chit_id > 0;
        if($this->_receipt->loan_id > 0){
            $this->_hasDeductionBill = true;
        }
    }
    
    public function getTotalRows()
    {
        return $this->_number;
    }
    
    public function totalReceiptAmount()
    {
        return $this->_amount;
    }
    
     /**
     * check deduction bill
     * 
     * @return boolean
     */
    public function hasDeductionBill()
    {
        return $this->_hasDeductionBill;
    }

    public function receiptNo()
    {
        return $this->_receipt->receiptNo;
    }

    public function updated_at()
    {
        return $this->_receipt->updated_at;
    }

    public  function nonMember()
    {
        return $this->_receipt->non_member;
    }
    
    public  function remarks()
    {
        return $this->_receipt->remarks;
    }

    /**
     * get receipt
     * 
     * @return \stdClass
     */
    public function transaction()
    {
        return $this->_receipt;
    }
    
    /**
     * get receipt id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * get receipt entries
     * 
     * @return array
     */
    public function entries()
    {
        return $this->_entries;
    }
    
    /**
     * get member
     * 
     * @return \stdClass
     */
    public function member()
    {
        return $this->_member;
    }
    
    /**
     * get donor
     * 
     * @return \stdClass
     */
    public function donor()
    {
        return $this->_donor;
    }
    
    /**
     * get fmc
     * 
     * @return \stdClass
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    public function getBankName()
    {
        if($this->_receipt->transBy) {
            $this->head = $this->_headModel->find($this->_receipt->transBy);
            return $this->head->head_name;
        }
        return false;
    }
    
    public function transBy()
    {
        return $this->_receipt->transBy;
    }
    
    public function paymentType()
    {
        if($this->_receipt->transBy){
            $this->_paymentType = 'Bank';
        } else {
            $this->_paymentType = 'Cash';
        }
        return $this->_paymentType;
    }

    /**
     * get auction date
     * 
     * @return string
     */
    public function receiptDate()
    {
        return $this->_receiptDate;
    }
    
    /**
     * check donor edit access
     * 
     * @return boolean
     */
    public function edit()
    {
        if (DateHelper::today() != $this->_auctionChit->auction_date) {
            return false;
        }
        
        return 1 == $this->_auctionChit->donor_entry;
    }
    
    /**
     * get receipt summary view
     * 
     * @return string
     */
    public function getSummary()
    {
        
        $pairModel = new PairModel();
        $columns = ['id', 'head_name'];
        $donor_code = '' ;
        $member_code = '';
        $where = "transType in('Direct Income', 'Indirect Income')";
        $heads = $pairModel->custom('rp_head', $columns, $where);
        foreach ($heads as $id => $head) {
            $accountHeads[$id] = $head;
        }
        if(isset($this->_donor->code)){
            $donor_code = $this->_escape($this->_donor->code . ' ' . $this->_donor->name);
        }
        if(isset($this->_member->code)){
            $member_code = $this->_member->code . ' ' . $this->_member->name;
        }
        $html = '<div class="row"><div class="col-12">'
              . '<table class="table table-bordered table-condensed">'
              . '<thead><tr><th>Date</th><th>Member</th><th>Auctioneer</th>'
              . '</tr></thead><tbody><tr><td>' . $this->_receiptDate . '</td><td>';
        $html .= $this->_escape($member_code);
        $html .= '</td><td>' . $donor_code. '</td></tr>';
        $html .= '<thead><tr><th>Non Member</th><th>Payment Type</th><th>Bank</th>';
        $html .= '<tr><td>'.$this->_receipt->non_member. '</td><td>' . $this->paymentType(). '</td><td>'.$this->getBankName().'</td></tr>';
        $html .= '</tbody></table></div></div>';
        $html .= '<div class="row mt-1"><div class="col-12">'
               . '<table class="table table-bordered table-striped table-condensed">'
               . '<thead><tr><th>Sl. No.</th><th>Head Name</th><th>Amount</th>'
               . '</tr></thead><tbody>';
        
        $row = '<tr><td>%d</td><td>%s</td><td>%d</td></tr>';
        $number = 0;
        foreach ($this->_entries as $item) {
            ++$number;
            $html .= sprintf($row, $number, $this->_escape($accountHeads[$item->headID]), $item->amount);
        }
        
        $html .= '<tfoot><tr><th>&nbsp;</th><th>Total</th><th>' . $this->_amount . '</th>'
               .  '</tr>'
               . '<tr><th>&nbsp;</th><th></th>'
               .  '</tr></tfoot></table></div></div>';
        
        return $html;
    }
    
  
    
    
    /**
     * escape html
     * 
     * @param string $string
     * @return string
     */
    private function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
}
