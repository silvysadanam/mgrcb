<?php
/**
 * Lelam Store Utility
 *
 * @category   Lelam
 * @package    Lelam\Utility
 */

namespace Lelam\Utility;

use Model\{Fmc, Member};

use Shared\Constant\MemberType;
use Shared\Utility\Membership;


class AuctioneerStore
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
     * @var \stdClass
     */
    private $_member;
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    public function __construct($id)
    {
        $this->_memberModel = new Member();
        $this->_member = $this->_memberModel->find($id);
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        $sql = $this->_memberModel->getSql();
        $sql->whereId('fmc_id', $this->_member->fmc_id);
        $this->_where = $sql->getWhere();
    }
    
    /**
     * get member id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_member->id;
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
     * get fmc id
     * 
     * @return int
     */
    public function fmcId()
    {
        return $this->_fmc->id;
    }
    
    /**
     * get fmc name
     * 
     * @return string
     */
    public function fmcName()
    {
        return $this->_fmc->name;
    }
    
    /**
     * get fmc code
     * 
     * @return string
     */
    public function fmcCode()
    {
        return $this->_fmc->code;
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
     * get member name
     * 
     * @return string
     */
    public function name()
    {
        return $this->_member->name;
    }
    
    /**
     * get member code
     * 
     * @return string
     */
    public function code()
    {
        return $this->_member->code;
    }
    
    /**
     * get membership
     * 
     * @return \Shared\Utility\Membership
     */
    public function membership()
    {
        $membership = new Membership($this->_member->id, false);
        $membership->load();
        
        return $membership;
    }
    
    /**
     * get member ids
     * 
     * @return array
     */
    public function memberIds()
    {
        return $this->_memberModel->selectIds($this->_where, 'id');
    }
    
    /**
     * get auction member ids
     * 
     * @return array
     */
    public function auctionMemberIds()
    {
        $sql = $this->_memberModel->getSql();
        $sql->where($this->_where)
            ->whereId('member_type_id', MemberType::LIFEMEMBER);
        $where = $sql->getWhere();
        
        if (0 == $this->_memberModel->selectTotal($where)) {
            return false;
        }
        
        return $this->_memberModel->selectIds($where, 'id');
    }
    
    /**
     * get auctioneer ids
     * 
     * @return array
     */
    public function auctioneerIds()
    {
        $sql = $this->_memberModel->getSql();
        $sql->where($this->_where)
            ->whereId('member_type_id', MemberType::ORDMEMBER);
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
        return $this->_memberModel->select($this->_where, 'name');
    }
    
    /**
     * get member names
     * 
     * @return array
     */
    public function memberNames()
    {
        return $this->_memberPairs('name');
    }
    
    /**
     * get member codes
     * 
     * @return array
     */
    public function memberCodes()
    {
        return $this->_memberPairs('code');
    }
    
    /**
     * get member lookup
     * 
     * @return array
     */
    public function memberLookup()
    {
        return $this->_memberLookup(MemberType::LIFEMEMBER);
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
        
        return $this->_checkMemberType($id, MemberType::LIFEMEMBER);
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
     * get member pairs by field
     * 
     * @param string $field
     * @return array
     */
    private function _memberPairs($field)
    {
        return $this->_memberModel->selectPairs(['id', $field], $this->_where, $field);
    }
    
    /**
     * generate member lookup
     * 
     * @param int $type
     * @return array
     */
    private function _memberLookup($type)
    {
        $sql = $this->_memberModel->getSql();
        $sql->whereId('fmc_id', $this->_fmc->id)
            ->whereId('member_type_id', $type);
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
