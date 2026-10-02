<?php
/**
 * Application LoanAnalysis Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Analysis;
use Application\Utility\LoanHelper;
use Application\Helper\MemberCatchLogger;

use Model\{Schedule, MemberCatch};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;


class LoanAnalysis extends Analysis
{
    /**
     * 
     * @var array
     */
    private $_loanCatch = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->loansLevel();
        $this->fields(['code', 'source', 'type', 'amount', 'ageing']);
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
        $this->_sort();
        $this->_loadLookups();
    }
    
    /**
     * get loan catch
     * 
     * @return array
     */
    public function loanCatch()
    {
        return $this->_loanCatch;
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
        
        $this->_ageingFilter();
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
     * get ageing filter condition
     * 
     * @return null
     */
    private function _ageingFilter()
    {
        if (0 == $this->_values['ageing']) {
            return;
        }
        
        $limits = LoanHelper::getAgeing($this->_values['ageing']);
        if (is_array($limits)) {
            $from = $limits[0] + 1;
            $to = $limits[1] - 1;
            $this->_sql->whereBetween('ageing', $from, $to);
        } elseif (0 == $limits) {
            $this->_sql->whereZero('ageing');
        } else {
            $this->_sql->whereGreater('ageing', $limits);
        }
    }
    
    /**
     * load defaults
     */
    private function _loadDefaults()
    {
        $this->_defaults['member'] = [
            'loans' => 0, 'amount' => 0, 'emi' => 0, 'principal_prepaid_current' => 0, 
            'principal_arrears' => 0, 'principal' => 0, 'interest' => 0,
            'total' => 0, 'scheduled_outstanding' => 0, 'principal_outstanding' => 0, 'actual_outstanding' => 0, 
            'overdue' => 0, 'ageing' => 0, 'par' => 0, 'catch' => 0, 'repaid' => 0, 
            'percentage_catch' => 0,
        ];
        
        $this->_defaults['total_fields'] = [
            'principal_prepaid_current', 'principal_arrears', 'principal', 'interest',
            'total', 'scheduled_outstanding', 'principal_outstanding', 'actual_outstanding', 'repaid', 
        ];
    }
    
    /**
     * run analysis
     */
    private function _run()
    {
        $loanIds = [];
        $list = $this->_pqamModel->select($this->_where);
        $entries = [];
        foreach ($list as $pqam) {
            if (! isset($entries[$pqam->member_id])) {
                $entries[$pqam->member_id] = [];
                $this->_memberTotal[$pqam->member_id] = $this->_defaults['member'];
            }
            $loanIds[] = $pqam->loan_id;
            $entries[$pqam->member_id][] = $pqam;
        }
        
        foreach ($entries as $memberId => $items) {
            if (count($items) > 1) {
                $map = [];
                foreach ($items as $index => $item) {
                    $map[$item->loan_id] = $index;
                }
                $this->_sql->clear();
                $this->_sql->whereIn('id', array_keys($map));
                $orderedLoans = $this->_model->selectIds($this->_sql->getWhere(), 'loan_date');
                $this->_entries[$memberId] = [];
                foreach ($orderedLoans as $loanId) {
                    $this->_entries[$memberId][] = $items[$map[$loanId]];
                }
            } else {
                $this->_entries[$memberId] = $items;
            }
        }
        
        $this->loadLoans($loanIds);
        //$this->_loadLoanCatch($loanIds);
        $this->_loadMemberTotals();
        $this->_loadOverdue();
        $this->_loadGrandTotal();
    }
    
    /**
     * load member totals
     */
    private function _loadMemberTotals()
    {   
        foreach ($this->_entries as $memberId => $items) {
            $this->_memberTotal[$memberId]['loans'] = count($items);
            foreach ($items as $item) {
                $loan = $this->_loans[$item->loan_id];
                $this->_memberTotal[$memberId]['amount'] += $loan->loan_amount;
                $this->_memberTotal[$memberId]['emi'] += $loan->emi;
                foreach ($this->_defaults['total_fields'] as $field) {
                    $this->_memberTotal[$memberId][$field] += $item->{$field};
                }
            }
        }
    }
    
    /**
     * load overdue and catch
     */
    private function _loadOverdue()
    {
        $this->loadMembers(array_keys($this->_memberTotal));
        $this->_loadMemberCatch();
        //$catch = $this->_getCatch();
        $list = $this->_memberTotal;
        foreach ($list as $id => $item) {
            $overdue = $this->_overdue($item['scheduled_outstanding'], $item['actual_outstanding'], $item['emi']);
            $this->_memberTotal[$id]['overdue'] = $overdue['overdue'];
            $this->_memberTotal[$id]['ageing'] = $overdue['ageing'];
            $this->_memberTotal[$id]['par'] = $overdue['par'];
            $this->_memberTotal[$id]['catch'] = $this->_memberCatch[$id];
            $this->_memberTotal[$id]['percentage_catch'] = $this->_percentage($item['repaid'], $this->_memberCatch[$id]);
        }
    }
    
    /**
     * load grand total
     */
    private function _loadGrandTotal()
    {
        $fields = [
            'loans', 'amount', 'emi', 'principal_prepaid_current', 'principal_arrears',
            'principal', 'interest', 'total', 'scheduled_outstanding', 'principal_outstanding', 
            'actual_outstanding', 'repaid', 'overdue', 'par', 'catch',
        ];
        
        foreach ($fields as $field) {
            $this->_grandTotal[$field] = 0;
        }
        
        foreach ($this->_memberTotal as $item) {
            foreach ($fields as $key) {
                if ($item[$key] > 0) {
                    $this->_grandTotal[$key] += $item[$key];
                }
            }
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
        
        $memberIds = array_keys($this->_memberTotal);
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
     * load lookups
     */
    private function _loadLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('type', $pairModel->loanType());
        LookupStore::register('source', $pairModel->loanSource());
        LookupStore::register('fmc', $pairModel->fmcCode());
    }
    
    /**
     * load catch for each loan
     * 
     * @param array $loanIds
     */
    private function _loadLoanCatch($loanIds)
    {
        $logger = new MemberCatchLogger();
        foreach ($loanIds as $id) {
            $this->_loanCatch[$id] = $logger->loanCatch($id);
        }
    }
    
    /**
     * load member catch
     */
    private function _loadMemberCatch()
    {
        $scheduleModel = new Schedule();
        $memberCatchModel = new MemberCatch();
        foreach ($this->_entries as $memberId => $items) {
            $this->_memberCatch[$memberId] = 0;
            $loanIds = [];
            $this->_memberTotal[$memberId]['loans'] = count($items);
            foreach ($items as $item) {
                $loanIds[] = $item->loan_id;
            }
            
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
