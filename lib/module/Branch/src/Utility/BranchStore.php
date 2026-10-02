<?php
/**
 * Branch Store Utility
 *
 * @category   Branch
 * @package    Branch\Utility
 */

namespace Branch\Utility;

use Model\{Fmc, Member, DeductionFmc};

use Shared\Constant\MemberType;
use Shared\Utility\DeductionHelper;


class BranchStore
{
    /**
     * 
     * @var \Model\member
     */
    private $_memberModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    /**
     * 
     * @var int
     */
    private $_memberTotal;
    
    /**
     * 
     * @var array
     */
    private $_deductions = [];
    
    /**
     * 
     * @var boolean
     */
    private $_perBillUseAuctionChits = false;
    
    /**
     * 
     * @var array
     */
    private $_withdrawalDeductions = [];
    
    /**
     * 
     * @var string
     */
    private $_whereActive;
    
    /**
     * 
     * @var int
     */
    private $_memberActiveTotal;
    
    /**
     * 
     * @var array
     */
    private $_advanceDeductions = [];
    
    /**
     * 
     * @param array
     */
    private $_withdrawalOnlyDeductions = [];
    
    public function __construct($id)
    {
        $id = 1;
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($id);
        $this->_memberModel = new Member();
        $sql = $this->_memberModel->getSql();
        $sql->whereId('fmc_id', $this->_fmc->id);
        $this->_where = $sql->getWhere();
        $sql->whereZero('disabled');
        $this->_whereActive = $sql->getWhere();
        $this->_memberTotal = $this->_memberModel->selectTotal($this->_where);
        $this->_memberActiveTotal = $this->_memberModel->selectTotal($this->_whereActive);
        
        $deductionFmcModel = new DeductionFmc();
        if ($deductionFmcModel->selectTotal($this->_where) > 0) {
            $list = $deductionFmcModel->selectIndexed($this->_where, 'id', null, 'deduction_type_id');
            $types = DeductionHelper::deductionTypeIds();
            $withdrawals = DeductionHelper::withdrawal();
            $advances = DeductionHelper::advance();
            foreach ($types as $typeId) {
                if (isset($list[$typeId])) {
                    $this->_deductions[$typeId] = $list[$typeId];
                    if (in_array($typeId, $withdrawals)) {
                        $this->_withdrawalDeductions[] = $typeId;
                        if (in_array($typeId, $advances)) {
                            $this->_advanceDeductions[] = $typeId;
                        } else {
                            $this->_withdrawalOnlyDeductions[] = $typeId;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * get fmc id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_fmc->id;
    }
    
    /**
     * get fmc name
     * 
     * @return string
     */
    public function name()
    {
        return $this->_fmc->name;
    }
    
    /**
     * get fmc record
     * 
     * @return \stdClass
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    /**
     * get fmc code
     * 
     * @return string
     */
    public function code()
    {
        return $this->_fmc->code;
    }
    
    /**
     * get member total
     * 
     * @return int
     */
    public function memberTotal()
    {
        return $this->_memberTotal;
    }
    
    /**
     * check members
     * 
     * @return boolean
     */
    public function hasMembers()
    {
        return $this->_memberTotal > 0;
    }
    
    /**
     * get member ids
     * 
     * @return array
     */
    public function memberIds()
    {
        if (0 == $this->_memberTotal) {
            return false;
        }
        
        return $this->_memberModel->selectIds($this->_where, 'id');
    }
    
    /**
     * get auction member ids
     * 
     * @param boolean $active 
     * @return array
     */
    public function auctionMemberIds($active = false)
    {
        $sql = $this->_memberModel->getSql();
        $sql->where($this->_where)
            ->whereId('member_type_id', MemberType::MEMBER);
        
        if ($active) {
            $sql->whereZero('disabled');
        }
        
        $where = $sql->getWhere();
        
        if (0 == $this->_memberModel->selectTotal($where)) {
            return false;
        }
        
        return $this->_memberModel->selectIds($where, 'id');
    }
    
    /**
     * get donor ids
     * 
     * @param boolean $active
     * @return array
     */
    public function donorIds($active = false)
    {
        $sql = $this->_memberModel->getSql();
        $sql->where($this->_where)
            ->whereId('member_type_id', MemberType::DONOR);
        if ($active) {
            $sql->whereZero('disabled');
        }
        
        $where = $sql->getWhere();
        
        if (0 == $this->_memberModel->selectTotal($where)) {
            return false;
        }
        
        return $this->_memberModel->selectIds($where, 'id');
    }
    
    /**
     * get members list
     * 
     * @return array
     */
    public function members()
    {
        if (0 == $this->_memberTotal) {
            return false;
        }
        
        return $this->_memberModel->select($this->_where, 'name');
    }
    
    /**
     * get member names
     * 
     * @param boolean $active
     * @return array
     */
    public function memberNames($active = false)
    {
        return $this->_memberPairs('name', $active);
    }
    
    /**
     * get member codes
     * 
     * @param boolean $active
     * @return array
     */
    public function memberCodes($active = false)
    {
        return $this->_memberPairs('code', $active);
    }
    
    /**
     * get member lookup
     * 
     * @param boolean $active
     * @return array
     */
    public function memberLookup($active = false)
    {
        return $this->_memberLookup(MemberType::MEMBER, $active);
    }
    
    /**
     * get donor lookup
     * 
     * @param boolean $active
     * @return array
     */
    public function donorLookup($active = false)
    {
        return $this->_memberLookup(MemberType::DONOR, $active);
    }
    
    /**
     * get donor names
     * 
     * @param boolean $active
     * @return array
     */
    public function donorNames($active = false)
    {
        return $this->_donorPairs('name', $active);
    }
    
    /**
     * get donor codes
     * 
     * @param boolean $active
     * @return array
     */
    public function donorCodes($active = false)
    {
        return $this->_donorPairs('code', $active);
    }
    
    /**
     * check fmc member id
     * 
     * @return boolean
     */
    public function isFmcMember($id)
    {
        return $this->_checkMemberId($id);
    }
    
    /**
     * check type member
     * 
     * @param int $id
     * @return boolean
     */
    public function isMember($id)
    {
        if (! $this->_checkMemberId($id)) {
            return false;
        }
        
        return $this->_checkMemberType($id, MemberType::MEMBER);
    }
    
    /**
     * check type donor
     * 
     * @param int $id
     * @return boolean
     */
    public function isAuctioneer($id)
    {
        if (! $this->_checkMemberId($id)) {
            return false;
        }
        
        return $this->_checkMemberType($id, MemberType::DONOR);
    }
    
    /**
     * get member by id
     * 
     * @param int $id
     * @return \stdClass
     */
    public function getMemberById($id)
    {
        $fields = ['id', 'name', 'code'];
        
        return $this->_memberModel->selectFieldsById($fields, $id);
    }
    
    /**
     * get fmc deductions
     * 
     * @return array
     */
    public function deductions()
    {
        return $this->_deductions;
    }
    
    /**
     * check deductions configured
     * 
     * @return boolean
     */
    public function hasDeductions()
    {
        return ! empty($this->_deductions);
    }
    
    /**
     * get deduction bill draft
     * 
     * @param int $amount
     * @param int $advance
     * @param int $bills
     * 
     * @return array
     */
    public function deductionBillDraft($amount, $advance = 0, $bills = 1)
    {
        $balance = $amount - $advance;
        $draft = [
            'auction_amount' => $amount,
            'deduction' => 0,
            'balance' => $balance,
            'advance' => $advance,
            'available' => $balance,
            'items' => [],
        ];
        
        if (! $this->_perBillUseAuctionChits) {
            $bills = 1;
        }
        
        foreach ($this->_deductions as $item) {
            //echo $balance . ' ----  ';
            $draft['items'][$item->deduction_type_id] = 0;
            if ($balance > 0) {
                $value = DeductionHelper::getDeduction($item, $amount, $balance, $bills);
                if ($value > 0) {
                    $draft['items'][$item->deduction_type_id] = $value;
                    $draft['deduction'] += $value;
                    $balance -= $value;
                }
            }
        }
        
        $draft['balance'] = $balance;
        
        return $draft;
    }
    
    /**
     * branch deduction types
     * 
     * @return array
     */
    public function deductionTypes()
    {
        $list = [];
        $deductionTypes = DeductionHelper::deductionTypes();
        foreach ($deductionTypes as $typeId => $type) {
            if (isset($this->_deductions[$typeId])) {
                $list[$typeId] = $type;
            }
        }
        
        return $list;
    }
    
    /**
     * branch grouped deduction type selector
     * 
     * @return array
     */
    public function groupedSelector()
    {
        $groups = DeductionHelper::deductionGroups();
        $deductionTypes = DeductionHelper::deductionTypes();
        $grouped = DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($this->_deductions));
        $list = [];
        foreach ($grouped as $groupId => $typeIds) {
            $types = [];
            foreach ($typeIds as $id) {
                $types[$id] = $deductionTypes[$id];
            }
            
            $list[$groups[$groupId]] = $types;
        }
        
        return $list;
    }
    
    /**
     * get withdrawal deductions
     * 
     * @return array
     */
    public function withdrawalDeductions()
    {
        return $this->_withdrawalDeductions;
    }
    
    /**
     * get withdrawal only deductions
     * 
     * @return array
     */
    public function withdrawalOnlyDeductions()
    {
        return $this->_withdrawalOnlyDeductions;
    }
    
    /**
     * get advance deductions
     * 
     * @return array
     */
    public function advanceDeductions()
    {
        return $this->_advanceDeductions;
    }
    
    public function groupedWithdrawalDeductions()
    {
        $groups = DeductionHelper::deductionGroups();
        $deductionTypes = DeductionHelper::deductionTypes();
        $grouped = DeductionHelper::groupedDeductionTypeIdsSelection($this->_withdrawalDeductions);
        $list = [];
        foreach ($grouped as $groupId => $typeIds) {
            $types = [];
            foreach ($typeIds as $id) {
                $types[$id] = $deductionTypes[$id];
            }
            
            $list[$groups[$groupId]] = $types;
        }
        
        return $list;
    }


    /**
     * get member pairs by field
     * 
     * @param string $field
     * @param boolean $active
     * @return array
     */
    private function _memberPairs($field, $active = false)
    {
        if (0 == $this->_memberTotal) {
            return [];
        }
        
        if ($active) {
            if (0 == $this->_memberActiveTotal) {
                return [];
            }
            
            return $this->_memberModel->selectPairs(['id', $field], $this->_whereActive, $field);
        }
        
        return $this->_memberModel->selectPairs(['id', $field], $this->_where, $field);
    }
    
    /**
     * get donor pairs by field
     * 
     * @param string $field
     * @param boolean $active
     * @return array
     */
    private function _donorPairs($field, $active = false)
    {
        $donorIds = $this->donorIds($active);
        if (empty($donorIds)) {
            return [];
        }
        
        $sql = $this->_memberModel->getSql();
        $sql->whereIn('id', $donorIds);
        
        return $this->_memberModel->selectPairs(['id', $field], $sql->getWhere(), $field);
    }
    
    /**
     * generate member lookup
     * 
     * @param int $type
     * @param boolean $active
     * @return array
     */
    private function _memberLookup($type, $active = false)
    {
        if (0 == $this->_memberTotal) {
            return [];
        }
        
        $sql = $this->_memberModel->getSql();
        $sql->whereId('fmc_id', $this->_fmc->id)
            ->whereId('member_type_id', $type);
        
        if ($active) {
            $sql->whereZero('disabled');
        }
        
        $where = $sql->getWhere();
        if (0 == $this->_memberModel->selectTotal($where)) {
            return [];
        }
        
        $list = $this->_memberModel->selectFields(['id', 'code', 'name'], $where, 'code');
        $options = [];
        foreach ($list as $item) {
            $options[$item->id] = $item->code . ' ' . $item->name;
        }
        
        return $options;
    }
    
    /**
     * check member of fmc
     * 
     * @param int $id
     * @return boolean
     */
    private function _checkMemberId($id)
    {
        if (! $this->_memberModel->hasId($id)) {
            return false;
        }
        
        return $this->_fmc->id == $this->_memberModel->selectColumnById('fmc_id', $id);
    }
    
    /**
     * check member type
     * @param type $id
     * @param type $type
     * @return type
     */
    private function _checkMemberType($id, $type)
    {
        return $type == $this->_memberModel->selectColumnById('member_type_id', $id);
    }
    
}
