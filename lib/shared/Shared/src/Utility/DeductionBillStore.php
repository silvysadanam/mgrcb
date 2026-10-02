<?php
/**
 * Shared DeductionBillStore Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{DeductionBill, DeductionBillEntry, Member, Fmc, AuctionDaily};
use Model\DeductionBillNote;

use Web\Utility\DateHelper;

use Shared\Utility\DeductionHelper;


class DeductionBillStore
{
    /**
     * 
     * @var \Model\DeductionBill
     */
    private $_model;
    
    /**
     * 
     * @var \Model\DeductionBillEntry
     */
    private $_deductionBillEntryModel;
    
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
    private $_deductionBill;
    
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
    private $_fmc;
    
    /**
     * 
     * @var \stdClass
     */
    private $_daily;
    
    /**
     * 
     * @var array
     */
    private $_grouped;
    
    /**
     * 
     * @var array
     */
    private $_notes = [];
    
    /**
     * constructor
     */
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_model = new DeductionBill();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load auction chit
     */
    public function load()
    {
        $this->_deductionBill = $this->_model->find($this->_id);
        $this->_sql->whereId('deduction_bill_id', $this->_id);
        $where = $this->_sql->getWhere();
        $this->_entries = $this->_deductionBillEntryModel->select($where);
        
        $groups = DeductionHelper::deductionTypeGroups();
        foreach ($this->_entries as $item) {
            $groupId = $groups[$item->deduction_type_id];
            if (! isset($this->_grouped[$groupId])) {
                $this->_grouped[$groupId] = [];
            }
            $this->_grouped[$groupId][] = $item;
        }

        $memberFields = ['id', 'name', 'code', 'fmc_id'];
        $memberModel = new Member();
        $this->_member = $memberModel->selectFieldsById($memberFields, $this->_deductionBill->member_id);
        $this->_auctionDate = DateHelper::datepicker($this->_deductionBill->deduction_date);
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_deductionBill->member_id)
                   ->whereEqual('auction_date', $this->_deductionBill->deduction_date);
        $auctionDailyModel = new AuctionDaily();
        $this->_daily = $auctionDailyModel->selectRow($this->_sql->getWhere());
        
        $noteModel = new DeductionBillNote();
        if ($noteModel->selectTotal($where)) {
            $this->_notes = $noteModel->select($where, 'id DESC');
        }
    }
    
    /**
     * get deduction bill
     * 
     * @return \stdClass
     */
    public function deductionBill()
    {
        return $this->_deductionBill;
    }
    
    /**
     * get deduction bill id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * get deduction bill entries
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
     * get daily summary
     * 
     * @return string
     */
    public function daily()
    {
        return $this->_daily;
    }
    
    /**
     * get deduction bill summary view
     * 
     * @return string
     */
    public function getSummary()
    { 
        $html = '<div class="row"><div class="col-12">'
              . '<table class="table table-bordered table-condensed">'
              . '<thead><tr><th>Date</th><th>Member</th><th>Auction Amount</th>'
              . '<th>Member Paid Amount</th></tr></thead><tbody><tr><td>' . $this->_auctionDate . '</td><td>';
        $html .= $this->_escape($this->_member->code . ' ' . $this->_member->name);
        $html .= '</td><td>' . $this->_deductionBill->auction_amount;
        $html .= '</td><td>' . $this->_deductionBill->member_paid_amount;
        $html .= '</td></tr></tbody></table></div></div>';
        $html .= '<div class="row mt-1"><div class="col-12">'
               . '<table class="table table-bordered table-striped table-condensed">'
               . '<thead><tr><th width="10%">Sl. No.</th><th>Item</th><th>Amount</th>'
               . '</tr></thead><tbody>';
        
        $row = '<tr><td>%d</td><td>%s</td><td>%d</td></tr>';
        $types = DeductionHelper::combinedDeductionTypes();
        $number = 0;
        foreach ($this->_entries as $item) {
            ++$number;
            $title = $this->_escape($types[$item->deduction_type_id]);
            $html .= sprintf($row, $number, $this->_escape($title), $this->_amount($item->amount));
        }
        
        $html .= '<tfoot><tr><th>&nbsp;</th><th>Total</th><th>' 
               . $this->_deductionBill->deduction_amount . '</th></tr>'
               . '</tfoot></table></div></div>';
        
        return $html;
    }
    
    /**
     * get grouped entries
     * 
     * @return array
     */
    public function grouped()
    {
        return $this->_grouped;
    }
    
    /**
     * get notes
     * 
     * @return array
     */
    public function notes()
    {
        return $this->_notes;
    }
    
    /**
     * get filtered grouped list
     * 
     * @return array
     */
    public function filtered()
    {
        $list = [];
        foreach ($this->_grouped as $groupId => $items) {
            $list[$groupId] = [];
            foreach ($items as $item) {
                if ($item->amount > 0) {
                    $list[$groupId][] = $item;
                }
            }
        }
        
        return $list;
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
    
    private function _amount($amount)
    {
        if ($amount > 0) {
            return $amount;
        }
        
        return '&nbsp;';
    }
    
}
