<?php
/**
 * Application MemberAnalysis Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Analysis;
use Application\Utility\LoanHelper;

use Model\{Schedule, MemberCatch};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;


class MemberAnalysis extends Analysis
{
    public function __construct()
    {
        parent::__construct();
        $this->membersLevel();
        $this->fields(['code', 'amount', 'ageing']);
    }
    
    /**
     * check result
     * 
     * @return boolean
     */
    public function check()
    {
        $this->_getWhere();
        $this->_loadCount();

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
        $this->_sort();
        $this->_loadGrandTotal();
        $this->_loadLookups();
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
    
    
    /**
     * load count
     * 
     * @return null
     */
    private function _loadCount()
    {
        if (false === $this->_where) {
            return;
        }
        
        $this->_amountFilter();
        
        if ($this->_recordCount > 0) {
            $this->_ageingFilter();
        }
    }
    
    /**
     * load defaults
     */
    private function _loadDefaults()
    {
        $this->_defaults['member'] = [
            'loans' => 0,
            'loan_amount' => 0,
            'emi' => 0,
            'principal_prepaid' => 0,
            'principal_arrears' => 0,
            'principal_prepaid_current' => 0,
            'principal' => 0,
            'interest' => 0,
            'total' => 0,
            'scheduled_outstanding' => 0,
            'actual_outstanding' => 0,
            'overdue' => 0,
            'ageing' => 0,
            'par' => 0,
            'repaid' => 0,
            'catch' => 0,
            'percentage_catch' => 0,
            'principal_outstanding' => 0,
        ];
        
        $this->_defaults['total_fields'] = [
            'principal', 'interest', 'total', 'scheduled_outstanding', 
            'actual_outstanding', 'repaid',  'principal_prepaid_current', 
            'principal_arrears', 'principal_outstanding',
        ];
    }
    
    /**
     * run analysis
     */
    private function _run()
    {
        $allLoanIds = [];
        $list = $this->_pqamModel->select($this->_where);
        foreach ($list as $pqam) {
            if (! isset($this->_entries[$pqam->member_id])) {
                $this->_entries[$pqam->member_id] = $this->_defaults['member'];
                $loanIds[$pqam->member_id] = [];
            }
            $allLoanIds[] = $pqam->loan_id;
            $loanIds[$pqam->member_id][] = $pqam->loan_id;
            foreach ($this->_defaults['total_fields'] as $field) {
                $this->_entries[$pqam->member_id][$field] += $pqam->{$field};
            }
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $allLoanIds);
        $loans = $this->_model->selectIndexed($this->_sql->getWhere());
        
        foreach ($loanIds as $memberId => $memberLoans) {
            $this->_entries[$memberId]['loans'] = count($memberLoans);
            foreach ($memberLoans as $loanId) {
                $this->_entries[$memberId]['loan_amount'] += $loans[$loanId]->loan_amount;
                $this->_entries[$memberId]['emi'] += $loans[$loanId]->emi;
            }
        }

        $this->_loadMemberCatch($loanIds);
        $this->_loadOverdue();
    }
    
    /**
     * load overdue and catch
     */
    private function _loadOverdue()
    {
        $this->loadMembers(array_keys($this->_entries));
        //$catch = $this->_getCatch();
        $list = $this->_entries;
        foreach ($list as $id => $item) {
            $overdue = $this->_overdue($item['scheduled_outstanding'], $item['actual_outstanding'], $item['emi']);
            $this->_entries[$id]['overdue'] = $overdue['overdue'];
            $this->_entries[$id]['ageing'] = $overdue['ageing'];
            $this->_entries[$id]['par'] = $overdue['par'];
            $this->_entries[$id]['catch'] = $this->_memberCatch[$id];
            $this->_entries[$id]['percentage_catch'] = $this->_percentage($item['repaid'], $this->_memberCatch[$id]);
        }
    }
    
    /**
     * sort result
     */
    private function _sort()
    {
        if (1 == count($this->_entries)) {
            return;
        }
        
        $memberIds = array_keys($this->_entries);
        $this->_sql->clear();
        if ($this->_fmcId > 0) {
            $this->_sql->whereIn('id', $memberIds);
            $sorted = $this->_memberModel->selectColumns('id', $this->_sql->getWhere(), 'name');
        } else {
            $this->_sql->whereIn('m.id', $memberIds);
            $query = 'SELECT `m`.`id` FROM ' . $this->_memberModel->getTableQuoted() . ' `m` '
                    . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() . ' `f` ON `f`.`id` = `m`.`fmc_id` '
                    . 'WHERE ' . $this->_sql->getWhere() . ' ORDER BY `f`.`code`, `m`.`name`';
            $sorted = $this->_model->queryColumns($query);
        }
        
        $original = $this->_entries;
        $this->_entries = [];
        foreach ($sorted as $memberId) {
            $this->_entries[$memberId] = $original[$memberId];
        }
    }
    
    /**
     * amount filter
     * 
     * @return null
     */
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
     * ageing filter
     * 
     * @return null
     */
    private function _ageingFilter()
    {
        if (0 == $this->_values['ageing']) {
            return;
        }
        
        $default = ['scheduled_outstanding' => 0, 'actual_outstanding' => 0, 'emi' => 0];
        $loanIds = [];
        $entries = [];
        $list = $this->_pqamModel->selectFields(['member_id', 'loan_id', 'scheduled_outstanding', 'actual_outstanding'], $this->_where);
        $memberLoans = [];
        foreach ($list as $pqam) {
            if (! isset($entries[$pqam->member_id])) {
                $entries[$pqam->member_id] = $default;
                $memberLoans[$pqam->member_id] = [];
            }
            $memberLoans[$pqam->member_id][] = $pqam->loan_id;
            $loanIds[] = $pqam->loan_id;
            $entries[$pqam->member_id]['scheduled_outstanding'] += $pqam->scheduled_outstanding;
            $entries[$pqam->member_id]['actual_outstanding'] += $pqam->actual_outstanding;
        }

        $this->_sql->clear();
        $this->_sql->whereIn('id', $loanIds);
        $loans = $this->_model->selectPairs(['id', 'emi'], $this->_sql->getWhere());
        foreach ($memberLoans as $memberId => $items) {
            foreach ($items as $loanId) {
                $entries[$memberId]['emi'] += $loans[$loanId];
            }
        }
        $memberIds = [];
        $limits = LoanHelper::getAgeing($this->_values['ageing']);
        
        foreach ($entries as $memberId => $summary) {
            $ageing = 0;
            $include = false;
            if ($summary['actual_outstanding'] > $summary['scheduled_outstanding']) {
                $overdue = $summary['actual_outstanding'] - $summary['scheduled_outstanding'];
                $ageing = ceil(($overdue / $summary['emi']) * 30);
            }
            
            if (is_array($limits)) {
                if ($ageing > $limits[0] && $ageing < $limits[1]) {
                    $include = true;
                }
            } elseif (0 == $limits) {
                if (0 == $ageing) {
                    $include = true;
                }
            } else {
                if ($ageing > $limits) {
                    $include = true;
                }
            }
            
            if ($include) {
                $memberIds[] = $memberId;
            }
        }
        
        $this->_recordCount = count($memberIds);
        if (0 == $this->_recordCount) {
            return;
        }
        
        $ids = $this->_pqamModel->selectIds($this->_where);
        $this->_sql->clear();
        $this->_sql->whereIn('id', $ids)
                   ->whereIn('member_id', $memberIds);
        $this->_where = $this->_sql->getWhere();
    }
    
    /**
     * load grand total
     */
    private function _loadGrandTotal()
    {
        $fields = [
            'loans', 'loan_amount', 'emi', 'principal_prepaid', 'principal_arrears',
            'principal_prepaid_current', 'principal', 'interest', 'total', 
            'scheduled_outstanding', 'principal_outstanding', 'actual_outstanding', 
            'overdue', 'par', 'repaid', 'catch',
        ];
        
        
        foreach ($fields as $field) {
            $this->_grandTotal[$field] = 0;
        }
        
        foreach ($this->_entries as $item) {
            foreach ($fields as $key) {
                if ($item[$key] > 0) {
                    $this->_grandTotal[$key] += $item[$key];
                }
            }
        }
    }
    
    /**
     * load lookups
     */
    private function _loadLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmcCode());
    }
    
    /**
     * load member catch
     */
    private function _loadMemberCatch($memberLoans)
    {
        $scheduleModel = new Schedule();
        $memberCatchModel = new MemberCatch();
        foreach ($memberLoans as $memberId => $loanIds) {
            $this->_memberCatch[$memberId] = 0;
            
            $this->_sql->clear();
            $this->_sql->whereIn('loan_id', $loanIds)
                       ->whereOne('has_dcb_entry');
            $dates = $scheduleModel->selectDistinct('instalment_date', $this->_sql->getWhere(), 'instalment_date');
            
            $this->_sql->clear();
            $this->_sql->whereIn('catch_date', $dates, false)
                       ->whereId('member_id', $memberId);
            $where = $this->_sql->getWhere();
            if ($memberCatchModel->selectTotal($where) > 0) {
                $this->_memberCatch[$memberId] = $memberCatchModel->selectSum('amount', $where);
            }
        }
    }
    
}
