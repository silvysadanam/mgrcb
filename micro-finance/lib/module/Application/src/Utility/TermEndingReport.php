<?php
/**
 * Application TermEnding Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Loan, Member, LoanSummary, Fmc};

use Web\Utility\DateHelper;


class TermEndingReport
{
    const WITHIN_3_MONTHS = 0;
    const THIS_MONTH = 1;
    const NEXT_MONTH = 2;
    const NEXT_2_MONTHS = 3;
    const NEXT_3_MONTHS = 4;

    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_memberModel;
    
    /**
     *
     * @var \Model\LoanSummary 
     */
    private $_loanSummaryModel;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var int
     */
    private $_fmcId = 0;
    
    /**
     * 
     * @var int
     */
    private $_instalment = 0;
    
    /**
     * 
     * @var array
     */
    private $_instalmentMap;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var string
     */
    private $_fmc = 'All';
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_memberModel = new Member();
        $this->_loanSummaryModel = new LoanSummary();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * run report
     * 
     * @return null
     */
    public function run()
    {
        $this->_report = [
            'total' => 0,
            'list' => [],
            'ids' => [],
            'members' => [],
            'summary' => [],
            'instalment' => $this->_instalment,
        ];
        
        $this->_preload();
        
        $this->_loadLoanIds();
        if (empty($this->_report['ids'])) {
            return;
        }
        
        $this->_report['total'] = count($this->_report['ids']);
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_report['ids']);
        $where = $this->_sql->getWhere();
        $columns = [
            'id', 'member_id', 'loan_date', 'loan_amount', 'tenure', 
            'loan_type_id', 'loan_source_id', 'account_number',
            'paid_tenures', 'end_date',
            ];
        $this->_report['list'] = $this->_model->selectFields($columns, $where, 'end_date');
        
        $memberIds = $this->_model->selectDistinct('member_id', $where);
        $this->_sql->clear();
        $this->_sql->whereIn('id', $memberIds);
        $where2 = $this->_sql->getWhere();
        $members = $this->_memberModel->selectFields(['id', 'code', 'name', 'fmc_id'], $where2);
        $this->_report['members'] = $this->_memberModel->getIndexedList($members);
        
        $this->_sql->clear();
        $this->_sql->whereIn('loan_id', $this->_report['ids']);
        $summary = $this->_loanSummaryModel->selectFields(['loan_id', 'balance_actual_principal_interest_outstanding'], $this->_sql->getWhere());
        $this->_report['summary'] = $this->_model->getIndexedList($summary, 'loan_id');
    }
    
    /**
     * set fmc id
     * 
     * @param int $id
     * @return $this
     */
    public function setFmcId($id)
    {
        $this->_fmcId = $id;
        return $this;
    }
    
    /**
     * set instalment filter
     * 
     * @param int $instalment
     * @return $this
     */
    public function setInstalmentFilter($instalment)
    {
        $this->_instalment = $instalment;
        return $this;
    }
    
    /**
     * get report
     * 
     * @return array
     */
    public function report()
    {
        return $this->_report;
    }
    
    /**
     * get total
     * 
     * @return int
     */
    public function total()
    {
        return $this->_report['total'];
    }
    
    /**
     * check report
     * 
     * @return boolean
     */
    public function hasReport()
    {
        return $this->_report['total'] > 0;
    }
    
    /**
     * get fmc
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_instalmentMap = array_fill(0, 5, '');
        $today = DateHelper::getDateTime();
        $days = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days);
        $this->_instalmentMap[self::THIS_MONTH] = $today->format(DateHelper::MYSQL_DATE);
        
        $today->modify('+15 days');
        $days1 = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days1);
        $this->_instalmentMap[self::NEXT_MONTH] = $today->format(DateHelper::MYSQL_DATE);
        
        $today->modify('+15 days');
        $days2 = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days2);
        $this->_instalmentMap[self::NEXT_2_MONTHS] = $today->format(DateHelper::MYSQL_DATE);
        
        $today->modify('+15 days');
        $days3 = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days3);
        $this->_instalmentMap[self::NEXT_3_MONTHS] = $today->format(DateHelper::MYSQL_DATE);
        $this->_instalmentMap[self::WITHIN_3_MONTHS] = $this->_instalmentMap[self::NEXT_3_MONTHS];
    }
    
    /**
     * load loan ids
     * 
     * @return null
     */
    private function _loadLoanIds()
    {
        $this->_sql->clear();
        $this->_sql->whereZero('closed');
        if ($this->_fmcId > 0) {
            $memberIds = $this->_memberModel->selectIds(['fmc_id' => $this->_fmcId]);
            $this->_sql->whereIn('member_id', $memberIds);
            $fmcModel = new Fmc();
            $this->_fmc = $fmcModel->selectColumnById('name', $this->_fmcId);
        }
        
        if (self::WITHIN_3_MONTHS == $this->_instalment) {
            $this->_sql->whereBetween('end_date', DateHelper::today(), $this->_instalmentMap[self::WITHIN_3_MONTHS]);
        } else {
            $this->_sql->whereEqual('end_date', $this->_instalmentMap[$this->_instalment]);
        }

        
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            return;
        }
        
        $this->_report['ids'] = $this->_model->selectIds($where);
    }
    
}
