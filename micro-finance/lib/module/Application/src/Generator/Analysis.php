<?php
/**
 * Application Analysis Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Generator;
use Application\Helper\MemberCatchLogger;


class Analysis extends Generator
{
    public function __construct()
    {
        parent::__construct();
        $this->_generatorType = static::TYPE_ANALYSIS;
    }
    
    /**
     * load members
     * 
     * @param array $ids
     */
    protected function loadMembers($ids)
    {
        $this->_sql->clear();
        $this->_sql->whereIn('id', $ids);
        $this->_members = $this->_memberModel->selectIndexed($this->_sql->getWhere());
    }
    
    /**
     * load loans
     * 
     * @param array $ids
     */
    protected function loadLoans($ids)
    {
        $this->_sql->clear();
        $this->_sql->whereIn('id', $ids);
        $this->_loans = $this->_model->selectIndexed($this->_sql->getWhere());
    }
    
    /**
     * get member catch
     * 
     * @return array
     */
    protected function _getCatch()
    {
        /*
        $this->_sql->clear();
        $this->_sql->whereEqual('pqam_date', $this->_reportingDate)
                   ->whereIn('member_id', array_keys($this->_members));
        $pqacModel = new Pqac();
        
        return $pqacModel->selectPairs(['member_id', 'catch_amount'], $this->_sql->getWhere());
         * 
         */
        
        $helper = new MemberCatchLogger();
        $helper->setDate($this->_reportingDate);
        
        return $helper->members(array_keys($this->_members));
    }
    
}
