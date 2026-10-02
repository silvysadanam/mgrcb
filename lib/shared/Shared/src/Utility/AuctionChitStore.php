<?php
/**
 * Shared AuctionChitStore Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{AuctionChit, AuctionChitEntry, Member, Fmc, DeductionBill};

use Web\Utility\DateHelper;

use Shared\Model\PairModel;


class AuctionChitStore
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AuctionChitEntry
     */
    private $_auctionChitEntryModel;
    
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
    private $_auctionChit;
    
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
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    
    private $_hasDeductionBill = false;
    
    /**
     * constructor
     */
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_model = new AuctionChit();
        $this->_auctionChitEntryModel = new AuctionChitEntry();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load auction chit
     */
    public function load()
    {
        $this->_auctionChit = $this->_model->find($this->_id);
        $this->_sql->whereId('auction_chit_id', $this->_id);
        $this->_entries = $this->_auctionChitEntryModel->select($this->_sql->getWhere());
        
        $memberFields = ['id', 'name', 'code', 'fmc_id'];
        $memberModel = new Member();
        $this->_member = $memberModel->selectFieldsById($memberFields, $this->_auctionChit->member_id);
        $this->_donor = $memberModel->selectFieldsById($memberFields, $this->_auctionChit->donor_id);
        $this->_auctionDate = DateHelper::datepicker($this->_auctionChit->auction_date);
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        /*
        $deductionBillModel = new DeductionBill();
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_auctionChit->member_id)
                   ->whereEqual('deduction_date', $this->_auctionChit->auction_date);
        if ($deductionBillModel->selectTotal($this->_sql->getWhere()) > 0) {
            $this->_hasDeductionBill = true;
        }
         * 
         */
        
        $this->_hasDeductionBill = $this->_auctionChit->has_deduction_bill > 0;
    }
    
    /**
     * get auction chit
     * 
     * @return \stdClass
     */
    public function auctionChit()
    {
        return $this->_auctionChit;
    }
    
    /**
     * get auction chit id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * get auction chit entries
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
    
    /**
     * get auction date
     * 
     * @return string
     */
    public function auctionDate()
    {
        return $this->_auctionDate;
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
     * get auction chit summary view
     * 
     * @return string
     */
    public function getSummary()
    {
        $pairModel = new PairModel();
        $medicine = $pairModel->medicine();
        
        $html = '<div class="row"><div class="col-12">'
              . '<table class="table table-bordered table-condensed">'
              . '<thead><tr><th>Date</th><th>Member</th><th>Auctioneer</th>'
              . '</tr></thead><tbody><tr><td>' . $this->_auctionDate . '</td><td>';
        $html .= $this->_escape($this->_member->code . ' ' . $this->_member->name);
        $html .= '</td><td>' . $this->_escape($this->_donor->code . ' ' . $this->_donor->name);
        $html .= '</td></tr></tbody></table></div></div>';
        $html .= '<div class="row mt-1"><div class="col-12">'
               . '<table class="table table-bordered table-striped table-condensed">'
               . '<thead><tr><th>Sl. No.</th><th>Medicine</th><th>Weight</th><th>Amount</th>'
               . '</tr></thead><tbody>';
        
        $row = '<tr><td>%d</td><td>%s</td><td>%d</td><td>%d</td></tr>';
        $number = 0;
        foreach ($this->_entries as $item) {
            ++$number;
            $html .= sprintf($row, $number, $this->_escape($medicine[$item->medicine_id]), $item->quantity, $item->amount);
        }
        
        $html .= '<tfoot><tr><th>&nbsp;</th><th>Total</th><th>' . $this->_auctionChit->quantity . '</th><th>'
               . $this->_auctionChit->amount . '</th></tr><tr><th>&nbsp;</th>'
               . '<th>Advance</th><th>&nbsp;</th><th>' . $this->_auctionChit->advance
               . '</th></tr><tr><th>&nbsp;</th><th>Balance</th><th>&nbsp;</th><th>'
               . $this->_auctionChit->balance . '</th></tr></tfoot></table></div></div>';
        
        return $html;
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
