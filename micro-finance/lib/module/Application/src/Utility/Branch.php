<?php
/**
 * Application Branch Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Fmc, Member};


class Branch
{
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    
    /**
     * 
     * @var \Model\Fmc
     */
    private $_model;
    
    /**
     * 
     * @var \Model\Member
     */
    private $_member;
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    public function __construct($id)
    {
        $this->_id = $id;
        $this->_model = new Fmc();
        $this->_fmc = $this->_model->find($id);
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $id);
        $this->_where = $sql->getWhere();
        $this->_member = new Member();
    }
    
    public function id()
    {
        return $this->_id;
    }
    
    public function fmc()
    {
        return $this->_fmc;
    }
    
    public function name()
    {
        return $this->_fmc->name;
    }
    
    public function code()
    {
        return $this->_fmc->code;
    }
    
    public function members()
    {
        return $this->_member->select($this->_where, 'name');
    }
    
    public function memberIds()
    {
        return $this->_member->selectIds($this->_where, 'id');
    }
    
    public function memberCodes()
    {
        return $this->_member->selectPairs(['id', 'code'], $this->_where, 'code');
    }
    
    public function memberNames()
    {
        return $this->_member->selectPairs(['id', 'name'], $this->_where, 'name');
    }
    
    public function option()
    {
        return [$this->_id => $this->_fmc->name];
    }
    
}
