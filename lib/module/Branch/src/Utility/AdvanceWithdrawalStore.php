<?php
/**
 * Branch AdvanceWithdrawalStore Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;

use Model\{AdvanceWithdrawal, AdvanceWithdrawalRepayment, Member, Fmc};

use Shared\Utility\DeductionHelper;


class AdvanceWithdrawalStore
{
    /**
     * 
     * @var \Model\AdvanceWithdrawal
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AdvanceWithdrawalRepayment
     */
    private $_advanceRepaymentModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \stdClass
     */
    private $_advance;
    
    /**
     * 
     * @var array
     */
    private $_repayments = [];
    
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
     * constructor
     */
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_model = new AdvanceWithdrawal();
        $this->_advanceRepaymentModel = new AdvanceWithdrawalRepayment();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load auction chit
     */
    public function load()
    {
        $this->_advance = $this->_model->find($this->_id);
        $this->_sql->whereId('advance_withdrawal_id', $this->_id);
        $where = $this->_sql->getWhere();
        $this->_repayments = $this->_advanceRepaymentModel->select($where, 'deduction_bill_date');
        
        $memberFields = ['id', 'name', 'code', 'fmc_id'];
        $memberModel = new Member();
        $this->_member = $memberModel->selectFieldsById($memberFields, $this->_advance->member_id);
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
    }
    
    /**
     * get advance
     * 
     * @return \stdClass
     */
    public function advance()
    {
        return $this->_advance;
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
     * get repayment entries
     * 
     * @return array
     */
    public function repayments()
    {
        return $this->_repayments;
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
     * check balance
     * 
     * @return boolean
     */
    public function hasBalance()
    {
        return $this->_advance->balance > 0;
    }
    
    /**
     * check overdue balance
     * 
     * @return boolean
     */
    public function hasOverdueBalance()
    {
        return $this->_advance->overdue_balance > 0;
    }
    
    /**
     * get deduction type
     * 
     * @return string
     */
    public function deductionType()
    {
        return DeductionHelper::getDeductionTypeName($this->_advance->deduction_type_id);
    }
    
    /**
     * get advance type
     * 
     * @return string
     */
    public function dadvanceType()
    {
        if (0 == $this->_advance->withdrawal) {
            return 'Advance';
        }
        
        return 'Withdrawal';
    }
    
    /**
     * check advance type
     * 
     * @return string
     */
    public function withdrawal()
    {
        return 1 == $this->_advance->withdrawal;
    }
    
}
