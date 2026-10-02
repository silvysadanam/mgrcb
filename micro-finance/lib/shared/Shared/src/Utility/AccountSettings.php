<?php
/**
 * Shared AccountSettings Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{RPAccountSettings, RPTransMember, RPTransactionDetails};

use Web\Utility\DateHelper;

use Shared\Model\PairModel;


class AccountSettings
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
    private $_auctioneer;
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    private $_fmc_id;
    private $_rpTrans;
    private $_rpTransaction;
    
    private $_hasDeductionBill = false;
    
    /**
     * constructor
     */
    public function __construct($fmc_id)
    {
        $this->_fmc_id = $fmc_id;
        $this->_model = new RPAccountSettings();
        $this->_sql = $this->_model->getSql();
        $this->_rpTrans = new RPTransMember();
        $this->_rpTransaction = new RPTransactionDetails();
    }
    
    /**
     * load auction chit
     */
    public function load()
    {
        $this->_sql->whereEqual('fmc_id', $this->_fmc_id);
        $where = $this->_sql->getWhere();
        $this->_options = $this->_model->select($where);
    }
    
    /**
     * get receipt no
     * 
     * @return \stdClass
     */
    public function receiptNo()
    {
        return $this->_options[0]->receipt_no;
    }
    
     /**
     * get voucher no
     * 
     * @return \stdClass
     */
    public function voucherNo()
    {
        if(isset($this->_options[0]->voucher_no)){
             return $this->_options[0]->voucher_no;
        }else {
            return 0;
        }
    }
    
    public function code()
    {
        return isset($this->_options[0]->code) ?? 0;
    }
    
    /**
     * get next receipt Number
     */
    
    public function getNextReceiptNo()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmc_id);
        $where = $this->_sql->getWhere();
        $currentReceiptNo = $this->_rpTrans->selectMax('receiptNo', $where);
        return $currentReceiptNo;
    }
    
    public function getNextFWSReceiptNo()
    {
        $currentReceiptNo = $this->getNextReceiptNo();
        $currentCashReceiptNo = $this->getCashReceiptNo();
        if($currentReceiptNo > $currentCashReceiptNo){
            $newReceiptNo = $currentReceiptNo;
        } else {
            $newReceiptNo = $currentCashReceiptNo;
        }
        if(!$newReceiptNo){
            $receiptNo = 1;
        }else {
            $receiptNo = $newReceiptNo + 1;
        }
        return $receiptNo;
    }
    
    public function getNextMFReceiptNo()
    {
        $currentReceiptNo = $this->getNextReceiptNo();
        $currentCashReceiptNo = $this->getCashReceiptNo();
        if($currentReceiptNo > $currentCashReceiptNo){
            $newReceiptNo = $currentReceiptNo;
        } else {
            $newReceiptNo = $currentCashReceiptNo;
        }
        if(!$newReceiptNo){
            $receiptNo = 1;
        }else {
            $receiptNo = $newReceiptNo + 1;
        }
        return $receiptNo;
    }

    public function getNextVoucherNo()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmc_id);
        $this->_sql->whereEqual('transType', 'P');
        $where = $this->_sql->getWhere();
        $currentVoucherNo = $this->_rpTransaction->selectMax('transNo', $where);
        return $currentVoucherNo;
    }
    
    public function getCashReceiptNo()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmc_id);
        $this->_sql->whereEqual('transType', 'R');
        $where = $this->_sql->getWhere();
        $currentReceiptNo = $this->_rpTransaction->selectMax('transNo', $where);
        return $currentReceiptNo;
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
     * get auctioneer
     * 
     * @return \stdClass
     */
    public function auctioneer()
    {
        return $this->_auctioneer;
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
     * check auctioneer edit access
     * 
     * @return boolean
     */
    public function edit()
    {
        if (DateHelper::today() != $this->_auctionChit->auction_date) {
            return false;
        }
        
        return 1 == $this->_auctionChit->auctioneer_entry;
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
        $html .= '</td><td>' . $this->_escape($this->_auctioneer->code . ' ' . $this->_auctioneer->name);
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
