<?php
/**
 * Application PortfolioBuilder Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Member, Loan, Schedule, Summary, Pqam, Pqac};

use Application\Utility\LoanHelper;

use Web\Utility\DateHelper;


class PortfolioBuilder
{
    const ALL = '`id` > 0';
    
    /**
     * 
     * @var \Model\Loan
     */
    private $_loanModel;
    
     /**
     * 
     * @var \Model\Member
     */
    private $_memberModel;
    
     /**
     * 
     * @var \Model\Summary
     */
    private $_summaryModel;
    
     /**
     * 
     * @var \Model\Pqam
     */
    private $_pqamModel;
    
     /**
     * 
     * @var \Model\Pqac
     */
    private $_pqacModel;
    
     /**
     * 
     * @var \Model\Schedule
     */
    private $_scheduleModel;
    
    /**
     * 
     * @var int
     */
    private $_now;
    
    /**
     * 
     * @var array
     */
    private $_pqam;
    
    /**
     * 
     * @var array
     */
    private $_pqac;
    
    /**
     * 
     * @var array
     */
    private $_pqacUpdate;
    
    /**
     * 
     * @var array
     */
    private $_pqacCumulativeFields;
    
    public function __construct()
    {
        $this->_loanModel = new Loan();
        $this->_memberModel = new Member();
        $this->_scheduleModel = new Schedule();
        $this->_summaryModel = new Summary();
        $this->_pqamModel = new Pqam();
        $this->_pqacModel = new Pqac();
        
        $this->_now = DateHelper::timestamp();
    }
    
    /**
     * rebuild pqam/pqac for member's period
     * 
     * @param int $memberId
     * @param array $period
     */
    public function rebuildPeriod($memberId, $period)
    {
        return;
        $this->_loadDefaults();
        $dates = [];
        foreach ($period as $instalmentDate) {
            $dateId = DateHelper::format('Ymd', $instalmentDate);
            $dates[$dateId] = $instalmentDate;
        }

        $loans = [];
        $sql = $this->_pqamModel->getSql();
        $summary = [];
        foreach ($dates as $dateKey => $pqamDate) {
            $sql->clear();
            $sql->whereId('member_id', $memberId)
                ->whereEqual('pqam_date', $pqamDate);
            $wherePqam = $sql->getWhere();
            if ($this->_pqamModel->selectTotal($wherePqam) > 0) {
                $summary[$dateKey] = $this->_pqacUpdate;
                $entries = $this->_pqamModel->select($wherePqam);
                foreach ($entries as $pqam) {
                    if (! isset($loans[$pqam->loan_id])) {
                        $loans[$pqam->loan_id] = $this->_loanModel->find($pqam->loan_id);
                    }
                    $summary[$dateKey]['loans'] += 1;
                    $summary[$dateKey]['loan_amount'] += $loans[$pqam->loan_id]->loan_amount;
                    $summary[$dateKey]['emi'] += $loans[$pqam->loan_id]->emi;
                    foreach ($this->_pqacCumulativeFields as $field1) {
                        $summary[$dateKey][$field1] += $pqam->{$field1};
                    }
                    
                    if ($pqam->catch_amount > $summary[$dateKey]['catch_amount']) {
                        $summary[$dateKey]['catch_amount'] = $pqam->catch_amount;
                    }
                }
            }
        }
        
        foreach ($dates as $dateIndex => $date) {
            $sql->clear();
            $sql->whereId('member_id', $memberId)
                ->whereEqual('pqam_date', $date);
            $wherePqac = $sql->getWhere();
            $current = null;
            if ($this->_pqacModel->selectTotal($wherePqac) > 0) {
                $current = $this->_pqacModel->selectRow($wherePqac);
            }
            
            if (! isset($summary[$dateIndex])) {
                if (null !== $current) {
                    $this->_pqacModel->deleteById($current->id);
                }
            } else {
                $calculated = $this->_calculateSummary($summary[$dateIndex]);
                foreach ($calculated as $column => $item) {
                    $summary[$dateIndex][$column] = $item;
                }
                
                if (null !== $current) {
                    $this->_pqacModel->updateById($summary[$dateIndex], $current->id);
                } else {
                    $summary[$dateIndex]['member_id'] = $memberId;
                    $summary[$dateIndex]['pqam_date'] = $date;
                    $summary[$dateIndex]['created_at'] = $this->_now;
                    $this->_pqacModel->insert($summary[$dateIndex]);
                }
            }
        }
    }
    
    /**
     * update pqam/pqac for dcb entry
     * 
     * @param int $scheduleId
     * @return null
     */
    public function dcbEntry($scheduleId)
    {
        return;
        $this->_loadDefaults();
        $schedule = $this->_scheduleModel->find($scheduleId);
        $summary = $this->_summaryModel->selectByIdColumn('schedule_id', $scheduleId);
        $loan = $this->_loanModel->find($schedule->loan_id);
        $extracted = $this->_extractPqam($summary, $loan->emi);
        $this->_pqam['member_id'] = $loan->member_id;
        $this->_pqam['pqam_date'] = $schedule->instalment_date;
        $this->_pqam['loan_id'] = $loan->id;
        $this->_pqam['instalment'] = $schedule->instalment;
        foreach ($extracted as $key => $value) {
            $this->_pqam[$key] = $value;
        }
        $this->_pqam['created_at'] = $this->_now;
        $this->_pqamModel->insert($this->_pqam);
        
        
        
        $sql = $this->_pqamModel->getSql();
        $sql->whereId('member_id', $loan->member_id)
            ->whereEqual('pqam_date', $schedule->instalment_date);
        $where = $sql->getWhere();
        if (0 == $this->_pqacModel->selectTotal($where)) {
            $this->_pqac['member_id'] = $loan->member_id;
            $this->_pqac['pqam_date'] = $schedule->instalment_date;
            $this->_pqac['loans'] = 1;
            $this->_pqac['loan_amount'] = $loan->loan_amount;
            $this->_pqac['catch_amount'] = $this->_pqam['catch_amount'];
            $this->_pqac['emi'] = $loan->emi;
            foreach ($this->_pqacCumulativeFields as $field) {
                $this->_pqac[$field] = $this->_pqam[$field];
            }
            
            $calculated = $this->_calculateSummary($this->_pqac);
            foreach ($calculated as $column => $item) {
                $this->_pqac[$column] = $item;
            }
            $this->_pqac['created_at'] = $this->_now;
            
            $this->_pqacModel->insert($this->_pqac);
            
            return;
        }
        
        $pqac = $this->_pqacModel->selectRow($where);
        $this->_pqacUpdate['loans'] = $pqac->loans + 1;
        $this->_pqacUpdate['loan_amount'] = $pqac->loan_amount + $loan->loan_amount;
        $this->_pqacUpdate['emi'] = $pqac->emi + $loan->emi;
        $this->_pqacUpdate['catch_amount'] = $this->_pqamModel->selectMax('catch_amount', $where);
        
        foreach ($this->_pqacCumulativeFields as $pqacField) {
            $this->_pqacUpdate[$pqacField] = $pqac->{$pqacField} + $this->_pqam[$pqacField];
        }
        
        $claculatedPqac = $this->_calculateSummary($this->_pqacUpdate);
        foreach ($claculatedPqac as $pqacColumn => $pqacValue) {
            $this->_pqacUpdate[$pqacColumn] = $pqacValue;
        }
        
        $this->_pqacModel->updateById($this->_pqacUpdate, $pqac->id);
    }
    
    /**
     * rebuild all
     * 
     * @return null
     */
    public function rebuild()
    {
        return;
        if (0 == $this->_summaryModel->selectTotal()) {
            return;
        }
        
        if ($this->_pqamModel->selectTotal() > 0) {
            $this->_pqamModel->delete(self::ALL);
        }
        
        if ($this->_pqacModel->selectTotal() > 0) {
            $this->_pqacModel->delete(self::ALL);
        }
        
        $this->_loadDefaults();
        $list = $this->_summaryModel->select(null, 'id');
        $members = [];
        $summary = [];
        foreach ($list as $item) {
            $schedule = $this->_scheduleModel->find($item->schedule_id);
            $loan = $this->_loanModel->find($schedule->loan_id);
            if (! isset($members[$loan->member_id])) {
                $members[$loan->member_id] = [];
            }
            $date = DateHelper::format('Ymd', $schedule->instalment_date);
            if (! isset($members[$loan->member_id][$date])) {
                $members[$loan->member_id][$date] = [];
            }
            
            $extracted = $this->_extractPqam($item, $loan->emi);
            $members[$loan->member_id][$date][] = [
                'member_id' => $loan->member_id,
                'pqam_date' => $schedule->instalment_date,
                'loan_id' => $loan->id,
                'instalment' => $schedule->instalment,
                'principal' => $extracted['principal'],
                'interest' => $extracted['interest'],
                'total' => $extracted['total'],
                'scheduled_outstanding' => $extracted['scheduled_outstanding'],
                'actual_outstanding' => $extracted['actual_outstanding'],
                'overdue' => $extracted['overdue'],
                'ageing' => $extracted['ageing'],
                'par' => $extracted['par'],
                'repaid' => $extracted['repaid'],
                'catch_amount' => $extracted['catch_amount'],
                'principal_arrears' => '',
                'current_principal' => '',
                'prepayment' => '',
                'principal_prepaid_current' => '',
                'created_at' => $this->_now,
            ];
            
            if (! isset($summary[$loan->member_id])) {
                $summary[$loan->member_id] = [];
            }
            
            if (! isset($summary[$loan->member_id][$date])) {
                $summary[$loan->member_id][$date] = [
                    'member_id' => $loan->member_id,
                    'pqam_date' => $schedule->instalment_date,
                    'loans' => 0,
                    'loan_amount' => $loan->loan_amount,
                    'emi' => $loan->emi,
                    'principal' => $extracted['principal'],
                    'interest' => $extracted['interest'],
                    'total' => $extracted['total'],
                    'scheduled_outstanding' => $extracted['scheduled_outstanding'],
                    'actual_outstanding' => $extracted['actual_outstanding'],
                    'overdue' => $extracted['overdue'],
                    'catch_amount' => $extracted['catch_amount'],
                    'repaid' => $extracted['repaid'],
                    'ageing' => 0,
                    'par' => 0,
                    'percentage_catch' => 0,
                    'principal_arrears' => $extracted['principal_arrears'],
                    'current_principal' => $extracted['current_principal'],
                    'prepayment' => $extracted['prepayment'],
                    'principal_prepaid_current' => $extracted['principal_prepaid_current'],
                ];
            } else {
                $summary[$loan->member_id][$date]['loans'] += 1;
                $summary[$loan->member_id][$date]['loan_amount'] += $loan->loan_amount;
                $summary[$loan->member_id][$date]['emi'] += $loan->emi;
                $summary[$loan->member_id][$date]['principal'] += $extracted['principal'];
                $summary[$loan->member_id][$date]['interest'] += $extracted['interest'];
                $summary[$loan->member_id][$date]['total'] += $extracted['total'];
                $summary[$loan->member_id][$date]['scheduled_outstanding'] += $extracted['scheduled_outstanding'];
                $summary[$loan->member_id][$date]['actual_outstanding'] += $extracted['actual_outstanding'];
                $summary[$loan->member_id][$date]['overdue'] += $extracted['overdue'];
                $summary[$loan->member_id][$date]['repaid'] += $extracted['repaid'];
                $summary[$loan->member_id][$date]['principal_arrears'] += $extracted['principal_arrears'];
                $summary[$loan->member_id][$date]['current_principal'] += $extracted['current_principal'];
                $summary[$loan->member_id][$date]['prepayment'] += $extracted['prepayment'];
                $summary[$loan->member_id][$date]['principal_prepaid_current'] += $extracted['principal_prepaid_current'];
                
                if ($extracted['catch_amount'] > $summary[$loan->member_id][$date]['catch_amount']) {
                    $summary[$loan->member_id][$date]['catch_amount'] = $extracted['catch_amount'];
                }
            }
        }

        $insertPqam = $this->_pqamModel->getInsertPreparedStatement(array_keys($this->_pqam));
        
        foreach ($members as $memberItems) {
            $listDates = $memberItems;
            ksort($listDates, SORT_NUMERIC);
            foreach ($listDates as $loans) {
                foreach ($loans as $loanItem) {
                    $insertPqam->execute(array_values($loanItem));
                }
            }
        }
        
        $insertSummary = $this->_pqacModel->getInsertPreparedStatement(array_keys($this->_pqac));
        
        foreach ($summary as $summaryItem) {
            $summaryDates = $summaryItem;
            ksort($summaryDates);
            foreach ($summaryDates as $memberItem) {
                $record = $memberItem;
                $claculated2 = $this->_calculateSummary($memberItem);
                foreach ($claculated2 as $field2 => $value2) {
                    $record[$field2] = $value2;
                }
                
                $record['created_at'] = $this->_now;
                $insertSummary->execute(array_values($record));
            }
        }
    }
    
    /**
     * extract pqam data from summary
     * 
     * @param \stdClass $summary
     * @return array
     */
    private function _extractPqam($summary, $emi)
    {
        $principal = $summary->collection_principal_arrears
                   + $summary->collection_current_principal
                   + $summary->collection_prepayment;
        $interest = $summary->collection_penal_interest
                  + $summary->collection_interest_arrears
                  + $summary->collection_current_interest;
        $total = $principal + $interest;
        if ($summary->balance_actual_principal_interest_outstanding > $summary->balance_scheduled_principal_outstanding) {
            $overdue = $summary->balance_actual_principal_interest_outstanding - $summary->balance_scheduled_principal_outstanding;
        } else {
            $overdue = 0.00;
        }
        
        $par = 0;
        $ageing = 0;
        if ($overdue > 0.00) {
            $par = $summary->balance_actual_principal_interest_outstanding;
            $ageing = ceil(($overdue / $emi) * 30);
        }
        
        return [
            'principal' => $principal,
            'interest' => $interest,
            'total' => $total,
            'scheduled_outstanding' => $summary->balance_scheduled_principal_outstanding,
            'actual_outstanding' => $summary->balance_actual_principal_interest_outstanding,
            'overdue' => $overdue,
            'ageing' => $ageing,
            'par' => $par,
            'repaid' => $summary->collection_repaid,
            'catch_amount' => $summary->catch_amount,
            'principal_arrears' => $summary->collection_principal_arrears,
            'current_principal' => $summary->collection_current_principal,
            'prepayment' => $summary->collection_prepayment,
            'principal_prepaid_current' => $summary->collection_current_principal + $summary->collection_prepayment,
        ];
    }
    
    /**
     * load default values
     */
    private function _loadDefaults()
    {
        $this->_pqam = [
            'member_id' => '',
            'pqam_date' => '',
            'loan_id' => '',
            'instalment' => '',
            'principal' => '',
            'interest' => '',
            'total' => '',
            'scheduled_outstanding' => '',
            'actual_outstanding' => '',
            'overdue' => '',
            'ageing' => '',
            'par' => '',
            'repaid' => '',
            'catch_amount' => '',
            'principal_arrears' => '',
            'current_principal' => '',
            'prepayment' => '',
            'principal_prepaid_current' => '',
            'created_at' => $this->_now,
        ];
        
        $this->_pqac = [
            'member_id' => '',
            'pqam_date' => '',
            'loans' => 0,
            'loan_amount' => 0,
            'emi' => 0,
            'principal' => 0.00,
            'interest' => 0.00,
            'total' => 0.00,
            'scheduled_outstanding' => 0.00,
            'actual_outstanding' => 0.00,
            'overdue' => 0.00,
            'catch_amount' => 0,
            'repaid' => 0,
            'ageing' => 0,
            'par' => 0,
            'percentage_catch' => 0,
            'principal_arrears' => 0,
            'current_principal' => 0,
            'prepayment' => 0,
            'principal_prepaid_current' => 0,
            'created_at' => 0,
        ];
        
        $this->_pqacUpdate = [
            'loans' => 0,
            'loan_amount' => 0,
            'emi' => 0,
            'principal' => 0.00,
            'interest' => 0.00,
            'total' => 0.00,
            'scheduled_outstanding' => 0.00,
            'actual_outstanding' => 0.00,
            'overdue' => 0.00,
            'catch_amount' => 0,
            'repaid' => 0,
            'ageing' => 0,
            'par' => 0,
            'percentage_catch' => 0,
            'principal_arrears' => 0.00,
            'current_principal' => 0.00,
            'prepayment' => 0.00,
            'principal_prepaid_current' => 0.00,
        ];
        
        $this->_pqacCumulativeFields = [
            'principal', 'interest', 'total', 'scheduled_outstanding', 
            'actual_outstanding', 'overdue', 'repaid', 'principal_arrears',
            'current_principal', 'prepayment', 'principal_prepaid_current',
        ];
    }
    
    /**
     * calculate pqac summary entries
     * 
     * @param array $record
     * @return array
     */
    private function _calculateSummary($record)
    {
        $calculated = [
            'ageing' => 0.0,
            'par' => 0,
            'percentage_catch' => 0.00,
        ];
        
        if ($record['overdue'] > 0.00) {
            $ageing = ($record['overdue'] / $record['emi']) * 30;
            $calculated['ageing'] = sprintf('%0.1f', $ageing);
            $calculated['par'] = $record['actual_outstanding'];
        }
        
        if ($record['catch_amount'] > 0) {
            $calculated['percentage_catch'] = LoanHelper::percentage($record['repaid'], $record['catch_amount']);
        }
        
        return $calculated;
    }
    
}