<?php
/**
 * Shared DeductionBillStore Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{AuctionChit, Deduction, Member, Fmc, DeductionNote};

use Web\Utility\DateHelper;

use Shared\Utility\DeductionHelper;


class DeductionStore
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\Deduction
     */
    private $_deductionModel;
    
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
    private $_fmc;
    
    /**
     * 
     * @var \stdClass
     */
    private $_donor;
    
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
        $this->_model = new AuctionChit();
        $this->_deductionModel = new Deduction();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load auction chit
     */
    public function load()
    {
        $this->_auctionChit = $this->_model->find($this->_id);
        $this->_sql->whereId('auction_chit_id', $this->_id);
        $where = $this->_sql->getWhere();
        $this->_entries = $this->_deductionModel->select($where);
        
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
        $this->_member = $memberModel->selectFieldsById($memberFields, $this->_auctionChit->member_id);
        $this->_donor = $memberModel->selectFieldsById($memberFields, $this->_auctionChit->donor_id);
        $this->_auctionDate = DateHelper::datepicker($this->_auctionChit->auction_date);
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        $noteModel = new DeductionNote();
        if ($noteModel->selectTotal($where)) {
            $this->_notes = $noteModel->select($where, 'id DESC');
        }
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
     * get deduction bill summary view
     * 
     * @return string
     */
    public function getSummary()
    { 
        $html = '<div class="row"><div class="col-12">'
              . '<table class="table table-bordered table-condensed">'
              . '<thead><tr><th>Date</th><th>Member</th><th>Auctioneer</th><th>Auction Amount</th>'
              . '<th>Member Paid Amount</th></tr></thead><tbody><tr><td>' . $this->_auctionDate . '</td><td>';
        $html .= $this->_escape($this->_member->code . ' ' . $this->_member->name) . '</td><td>';
        $html .= $this->_escape($this->_donor->code . ' ' . $this->_donor->name);
        $html .= '</td><td>' . $this->_auctionChit->amount;
        $html .= '</td><td>' . $this->_auctionChit->member_paid_amount;
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
            $html .= sprintf($row, $number, $this->_escape($title), $this->_amount($item->deduction_amount));
        }
        
        $html .= '<tfoot><tr><th>&nbsp;</th><th>Total</th><th>' 
               . $this->_auctionChit->deduction_amount . '</th></tr>'
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
                if ($item->deduction_amount > 0) {
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
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', true);
    }
    
    /**
     * get amount value
     * 
     * @param string $amount
     * @return string
     */
    private function _amount($amount)
    {
        if ($amount > 0) {
            return $amount;
        }
        
        return '&nbsp;';
    }
    
}
