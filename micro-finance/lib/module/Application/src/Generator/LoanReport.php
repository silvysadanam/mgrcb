<?php
/**
 * Application LoanReport Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Report;
use Application\Utility\LoanHelper;


class LoanReport extends Report
{
    private $_ageingLoans = [];
    private $_ageingMembers = [];
    private $_outstanding = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->loansLevel();
        $this->fields([ 'source', 'type', 'amount']);
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
        
        $this->_recordCount = $this->_pqamModel->selectTotal($this->_where);

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
    
    public function ageingLoans()
    {
        return $this->_ageingLoans;
    }
    
    public function ageingMembers()
    {
        return $this->_ageingMembers;
    }
    
    public function outstanding()
    {
        return $this->_outstanding;
    }
    
    
    /**
     * get filter condition
     * 
     * @return null
     */
    private function _getWhere()
    {
        $this->_sql->clear();
        $loanFilter = $this->_filterLoans();
        if (false === $loanFilter) {
            return;
        }
        
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
        
        $this->_where = $where;
    }
    
    /**
     * filter loans
     * 
     * @return boolean
     */
    private function _filterLoans()
    {
        $sql = $this->_model->getSql();
        $sql->whereEqual('p.pqam_date', $this->_reportingDate);
        if (! empty($this->_values['type'])) {
            $sql->whereIn('l.loan_type_id', $this->_values['type']);
        }
            
        if (! empty($this->_values['source'])) {
            $sql->whereIn('l.loan_source_id', $this->_values['source']);
        }
        
        if ($this->_memberId > 0) {
            $sql->whereId('l.member_id', $this->_memberId);
        } elseif (! empty ($this->_memberIds)) {
            $sql->whereIn('l.member_id', $this->_memberIds);
        }
        
        $amountFilter = $this->_getAmountFilter();
        if (false !== $amountFilter) {
            $sql->whereGroup($amountFilter);
        }
        
        $from = ' FROM ' . $this->_pqamModel->getTableQuoted() . ' `p` '
              . 'INNER JOIN ' . $this->_model->getTableQuoted() . ' `l` '
              . 'ON `l`.`id` = `p`.`loan_id` '
              . 'WHERE ' . $sql->getWhere();
        $count = 'SELECT COUNT(`p`.`id`) ' . $from;
        if (0 == $this->_model->queryTotal($count)) {
            return false;
        }

        $select= 'SELECT DISTINCT `p`.`loan_id` ' . $from;
        $loanIds = $this->_model->queryColumns($select);
        $this->_sql->whereIn('loan_id', $loanIds);
        
        return true;
    }
    
    /**
     * get amount filter condition
     * 
     * @return boolean|string
     */
    private function _getAmountFilter()
    {
        if (empty($this->_values['amount'])) {
            return false;
        }
            
        $amountSql = $this->_model->getSql();
        foreach ($this->_values['amount'] as $amountItem) {
            $amountOption = LoanHelper::getLoanAmount($amountItem);
            if (0 == $amountOption[1]) {
                $amountSql->whereLess('l.loan_amount', $amountOption[0]);
            } elseif (1 == $amountOption[1]) {
                $amountSql->whereGreater('l.loan_amount', $amountOption[0], true);
            } else {
                $amountSql->whereGreater('l.loan_amount', $amountOption[0], true)
                        ->whereLess('l.loan_amount', $amountOption[1]);
            }
        }
        $amountSql->whereGlueOr();
        
        return $amountSql->getWhere();
    }
    
    /**
     * load defaults
     */
    private function _loadDefaults()
    {
        $this->_percentages['loans'] = [];
        $this->_percentages['portfolio'] = [];
        $this->_defaults['entry'] = [
            'loans' => 0, 'amount' => 0, 'scheduled_outstanding' => 0,
            'actual_outstanding' => 0, 'overdue' => 0, 'par' => 0,
            'percentage_loans' => 0, 'percentage_portfolio' => 0,
            'llr_percentage' => 0, 'llr_amount' => 0, 'principal_outstanding' => 0,
        ];
        
        $this->_total = [
            'loans' => 0, 'amount' => 0, 'scheduled_outstanding' => 0,
            'actual_outstanding' => 0,  'overdue' => 0, 'par' => 0,
            'percentage_loans' => 100, 'percentage_portfolio' => 100,
            'llr_amount' => 0, 'principal_outstanding' => 0,
        ];
        
        $this->_defaults['total'] = [
            'loans', 'amount', 'scheduled_outstanding', 'actual_outstanding',
            'overdue', 'llr_amount', 'par', 'principal_outstanding',
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
        $memberIds = [];
        $loanIds = [];
        $entries = [];
        $list = $this->_pqamModel->select($this->_where);
        
        foreach ($list as $pqam) {
            $ageingIndex = LoanHelper::getAgeingIndex($pqam->ageing);
            if (! isset($entries[$ageingIndex])) {
                $entries[$ageingIndex] = [];
            }
            $loanIds[] = $pqam->loan_id;
            $entries[$ageingIndex][] = $pqam;
            $this->_outstanding[$pqam->loan_id] = $pqam->principal_outstanding;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $loanIds);
        $loanAmounts = $this->_model->selectPairs(['id', 'loan_amount'], $this->_sql->getWhere());
        
        foreach ($entries as $index => $items) {
            if (! isset($this->_ageingLoans[$index])) {
                $this->_ageingLoans[$index] = [];
                $this->_ageingMembers[$index] = [];
            }
            $this->_entries[$index]['loans'] = count($items);
            foreach ($items as $item) {
                $this->_ageingLoans[$index][] = $item->loan_id;
                if (! in_array($item->member_id, $this->_ageingMembers[$index])) {
                    $this->_ageingMembers[$index][] = $item->member_id;
                }
                if (! in_array($item->member_id, $memberIds)) {
                    $memberIds[] = $item->member_id;
                }
                $this->_entries[$index]['amount'] += $loanAmounts[$item->loan_id];
                $this->_entries[$index]['scheduled_outstanding'] += $item->scheduled_outstanding;
                $this->_entries[$index]['actual_outstanding'] += $item->actual_outstanding;
                $this->_entries[$index]['principal_outstanding'] += $item->principal_outstanding;
            }
        }
        
        foreach ($this->_ageingKeys as $key) {
            if ($this->_entries[$key]['actual_outstanding'] > $this->_entries[$key]['scheduled_outstanding']) {
                 $this->_entries[$key]['overdue'] = $this->_entries[$key]['actual_outstanding'] - $this->_entries[$key]['scheduled_outstanding'];
                 $this->_entries[$key]['par'] = $this->_entries[$key]['actual_outstanding'];
                 $this->_entries[$key]['llr_amount'] = $this->_llrAmount($this->_entries[$key]['llr_percentage'], $this->_entries[$key]['par']);
            } else {
                $this->_entries[$key]['overdue'] = 0; //$this->_entries[$key]['actual_outstanding'] - $this->_entries[$key]['scheduled_outstanding'];
            }
        }
        
        foreach ($this->_ageingKeys as $keyIndex) {
            foreach ($this->_defaults['total'] as $field) {
                if (LoanHelper::AGEING_ONTIME == $keyIndex && 'overdue' == $field) {
                    // do not add overdue for ontime
                } else {
                    $this->_total[$field] += $this->_entries[$keyIndex][$field];
                }
            }
        }
        
        foreach ($this->_ageingKeys as $keyId) {
            if ($this->_entries[$keyId]['loans'] > 0) {
                $this->_entries[$keyId]['percentage_loans'] = $this->_percentage($this->_entries[$keyId]['loans'], $this->_total['loans']);
                if ($this->_total['actual_outstanding'] > 0 && $this->_entries[$keyId]['actual_outstanding'] > 0) {
                    $this->_entries[$keyId]['percentage_portfolio'] = $this->_percentage($this->_entries[$keyId]['actual_outstanding'], $this->_total['actual_outstanding']);
                }
            }
            $this->_percentages['loans'][$keyId] = $this->_entries[$keyId]['percentage_loans'];
            $this->_percentages['portfolio'][$keyId] = $this->_entries[$keyId]['percentage_portfolio'];
        }
        
        $this->_fixPercentageLoans();
        $this->_fixPercentagePortfolio();
        $this->_sql->clear();
        $this->_sql->whereIn('id', $loanIds);
        $this->_loans = $this->_model->selectIndexed($this->_sql->getWhere(), 'id');
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $memberIds);
        $this->_members = $this->_memberModel->selectIndexed($this->_sql->getWhere());
    }
    
    
}
