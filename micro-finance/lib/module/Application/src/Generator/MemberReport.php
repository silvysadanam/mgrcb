<?php
/**
 * Application MemberReport Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Report;
use Application\Utility\LoanHelper;


class MemberReport extends Report
{
    private $_ageingMembers = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->membersLevel();
        $this->fields(['amount']);
    }
    
    /**
     * check result
     * 
     * @return boolean
     */
    public function check()
    {
        $this->_getWhere();
        if (false === $this->_where) {
            return false;
        }
        
        $this->_amountFilter();

        return $this->_recordCount > 0;
    }
    
    /**
     * load result
     */
    public function load()
    {
        if (0 == $this->_recordCount) {
            return;
        }
        
        $this->_loadDefaults();
        $this->_run();
        $this->_loadLookups();
    }
    
    public function ageingMembers()
    {
        return $this->_ageingMembers;
    }
    
    
    /**
     * get filter condition
     * 
     * @return null
     */
    private function _getWhere()
    {
        $this->_sql->clear();
        if ($this->_memberId > 0) {
            $this->_sql->whereId('member_id', $this->_memberId);
        } elseif (! empty ($this->_memberIds)) {
            $this->_sql->whereIn('member_id', $this->_memberIds);
        }
        $this->_sql->whereEqual('pqam_date', $this->_reportingDate);
        $where = $this->_sql->getWhere();
        if (0 == $this->_pqamModel->selectTotal($where)) {
            return;
        }
        
        $loanIds = $this->_pqamModel->selectColumns('loan_id', $where);
        $loanSql = $this->_model->getSql();
        $loanSql->whereIn('id', $loanIds);
        $loanWhere = $loanSql->getWhere();
        if (0 == $this->_model->selectTotal($loanWhere)) {
            return;
        }
        
        $activeLoans = $this->_model->selectColumns('id', $loanWhere);
        $this->_sql->whereIn('loan_id', $activeLoans);
        
        if (0 == $this->_pqamModel->selectTotal($this->_sql->getWhere())) {
            return;
        }
        
        $this->_where = $this->_sql->getWhere();
    }
    
    private function _amountFilter()
    {
        if (empty($this->_values['amount'])) {
            $query = 'SELECT COUNT(DISTINCT `member_id`) FROM ' 
                   . $this->_pqamModel->getTableQuoted() . ' WHERE ' . $this->_where;
            $this->_recordCount = $this->_pqamModel->queryTotal($query);
            
            return;
        }
        
        $amountLimits = [];
        foreach ($this->_values['amount'] as $amountKey) {
            $amountLimits[] = LoanHelper::getTotalLoanAmount($amountKey);
        }

        $loanIds = [];
        $entries = [];
        $list = $this->_pqamModel->selectFields(['member_id', 'loan_id'], $this->_where);
        $memberTotal = [];
        foreach ($list as $pqam) {
            if (! isset($entries[$pqam->member_id])) {
                $entries[$pqam->member_id] = [];
                $memberTotal[$pqam->member_id] = 0;
            }
            $loanIds[] = $pqam->loan_id;
            $entries[$pqam->member_id][] = $pqam->loan_id;
        }

        $this->_sql->clear();
        $this->_sql->whereIn('id', $loanIds);
        $loans = $this->_model->selectPairs(['id', 'loan_amount'], $this->_sql->getWhere());

        foreach ($entries as $memberId => $items) {
            foreach ($items as $loanId) {
                $memberTotal[$memberId] += $loans[$loanId];
            }
        }
        
        $memberIds = [];
        foreach ($memberTotal as $id => $item) {
            $include = false;
            foreach ($amountLimits as $limit) {
                if (0 == $limit[1]) {
                    if ($item < $limit[0]) {
                        $include = true;
                        break;
                    }
                } elseif (1 == $limit[1]) {
                    if ($item >= $limit[0]) {
                        $include = true;
                        break;
                    }
                } elseif ($item >= $limit[0] && $item < $limit[1]) {
                    $include = true;
                    break;
                }
            }

            if ($include) {
                $memberIds[] = $id;
            }
        }
        
        $this->_recordCount = count($memberIds);
        if ($this->_recordCount > 0) {
            $this->_sql->clear();
            $this->_sql->where($this->_where)
                       ->whereIn('member_id', $memberIds);
            $this->_where = $this->_sql->getWhere();
        }
    }
    
    /**
     * load defaults
     */
    private function _loadDefaults()
    {
        $this->_percentages['members'] = [];
        $this->_percentages['portfolio'] = [];
        $this->_defaults['entry'] = [
            'members' => 0, 'loans' => 0, 'amount' => 0, 'scheduled_outstanding' => 0,
            'actual_outstanding' => 0, 'overdue' => 0, 'par' => 0,
            'percentage_members' => 0, 'percentage_loans' => 0,
            'percentage_portfolio' => 0, 'llr_percentage' => 0, 'llr_amount' => 0,
            'principal_outstanding' => 0,
        ];
        $this->_total = [
            'members' => 0, 'loans' => 0, 'amount' => 0, 'emi' => 0,
            'scheduled_outstanding' => 0,  'actual_outstanding' => 0, 'overdue' => 0,
            'par' => 0, 'percentage_members' => 100, 'percentage_loans' => 100,
            'percentage_portfolio' => 100, 'llr_amount' => 0, 'principal_outstanding' => 0,
        ];
        
        $this->_defaults['member'] = [
            'loans' => 0, 'amount' => 0,  'emi' => 0, 'scheduled_outstanding' => 0,
            'actual_outstanding' => 0, 'overdue' => 0, 'principal_outstanding' => 0,
        ];
        
        
        $this->_defaults['total'] = [
            'members', 'loans', 'amount', 'scheduled_outstanding', 
            'actual_outstanding', 'llr_amount', 'overdue', 'par', 
            'principal_outstanding', 
        ];
        
        $llrOptions = LoanHelper::getLlrOptions();
        foreach ($this->_ageingKeys as $keyId) {
            $this->_entries[$keyId] = $this->_defaults['entry'];
            $this->_entries[$keyId]['llr_percentage'] = $llrOptions[$keyId];
        }
    }
    
    /**
     * run report
     */
    private function _run()
    {
        $loanIds = [];
        $list = $this->_pqamModel->select($this->_where);
        $memberWise = [];
        foreach ($list as $pqam) {
            if (! isset($memberWise[$pqam->member_id])) {
                $memberWise[$pqam->member_id] = [];
            }
            $memberWise[$pqam->member_id][] = $pqam;
            $loanIds[] = $pqam->loan_id;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $loanIds);
        $loans = $this->_model->selectIndexed($this->_sql->getWhere());
        
        $memberList = [];
        foreach ($memberWise as $memberId => $memberLoans) {
            $memberEntry = $this->_defaults['member'];
            $memberEntry['loans'] = count($memberLoans);
            foreach ($memberLoans as $pqamRow) {
                $memberEntry['amount'] += $loans[$pqamRow->loan_id]->loan_amount;
                $memberEntry['emi'] += $loans[$pqamRow->loan_id]->emi;
                $memberEntry['scheduled_outstanding'] += $pqamRow->scheduled_outstanding;
                $memberEntry['actual_outstanding'] += $pqamRow->actual_outstanding;
                $memberEntry['principal_outstanding'] += $pqamRow->principal_outstanding;
            }
            $memberList[$memberId] = $memberEntry;
        }
        
        $memberSummary = [];
        foreach ($memberList as $memberId => $item) {
            $memberEntry = $item;
            $ageing = 0;
            if ($memberEntry['actual_outstanding'] > $memberEntry['scheduled_outstanding']) {
                $memberEntry['overdue'] = $memberEntry['actual_outstanding'] - $memberEntry['scheduled_outstanding'];
                $ageing = ceil(($memberEntry['overdue'] / $memberEntry['emi']) * 30);
            }

            $ageingIndex = LoanHelper::getAgeingIndex($ageing);
            if (! isset($memberSummary[$ageingIndex])) {
                $memberSummary[$ageingIndex] = [];
                $this->_ageingMembers[$ageingIndex] = [];
            }
            $memberSummary[$ageingIndex][] = $memberEntry;
            $this->_ageingMembers[$ageingIndex][] = $memberId;
        }

        foreach ($memberSummary as $index => $memberEntries) {
            $this->_entries[$index]['members'] = count($memberEntries);
            foreach ($memberEntries as $item) {
                $this->_entries[$index]['loans'] += $item['loans'];
                $this->_entries[$index]['amount'] += $item['amount'];
                $this->_entries[$index]['scheduled_outstanding'] += $item['scheduled_outstanding'];
                $this->_entries[$index]['actual_outstanding'] += $item['actual_outstanding'];
                $this->_entries[$index]['principal_outstanding'] += $item['principal_outstanding'];
            }
            if ($this->_entries[$index]['actual_outstanding'] > $this->_entries[$index]['scheduled_outstanding']) {
                $this->_entries[$index]['overdue'] = $this->_entries[$index]['actual_outstanding'] - $this->_entries[$index]['scheduled_outstanding'];
                $this->_entries[$index]['par'] = $this->_entries[$index]['actual_outstanding'];
            } else {
                $this->_entries[$index]['overdue'] = 0; // $this->_entries[$index]['actual_outstanding'] - $this->_entries[$index]['scheduled_outstanding'];
            }
            
            $this->_entries[$index]['llr_amount'] = $this->_llrAmount($this->_entries[$index]['llr_percentage'], $this->_entries[$index]['par']);
            foreach ($this->_defaults['total'] as $field) {
                if (LoanHelper::AGEING_ONTIME == $index && 'overdue' == $field) {
                    // do not add
                } else {
                    $this->_total[$field] += $this->_entries[$index][$field];
                }
            }
        }

        $this->_percentages['members'] = [];
        foreach ($this->_ageingKeys as $key) {
            $this->_entries[$key]['percentage_members'] = $this->_percentage($this->_entries[$key]['members'], $this->_total['members']);
            $this->_entries[$key]['percentage_loans'] = $this->_percentage($this->_entries[$key]['loans'], $this->_total['loans']);
            if ($this->_entries[$key]['actual_outstanding'] > 0 && $this->_total['actual_outstanding'] > 0) {
                $this->_entries[$key]['percentage_portfolio'] = $this->_percentage($this->_entries[$key]['actual_outstanding'], $this->_total['actual_outstanding']);
            }
            $this->_percentages['loans'][$key] = $this->_entries[$key]['percentage_loans'];
            $this->_percentages['members'][$key] = $this->_entries[$key]['percentage_members'];
            $this->_percentages['portfolio'][$key] = $this->_entries[$key]['percentage_portfolio'];
        }
        
        $this->_fixPercentageLoans();
        $this->_fixPercentageMembers();
        $this->_fixPercentagePortfolio();
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', array_keys($memberWise));
        $this->_members = $this->_memberModel->selectIndexed($this->_sql->getWhere());
    }
    
}
