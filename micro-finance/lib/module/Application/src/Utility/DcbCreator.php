<?php
/**
 * Application DcbCreator Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\{LoanHelper, DcbColumn};
use Application\Helper\MemberCatchLogger;

use Model\{Loan, Schedule, Summary, LoanSummary, Pqam, Member, LoanSource, RPTransactionDetails, RPTransMember, RPTransMemberDetails};

use Web\Utility\DateHelper;
use Shared\Utility\Transactions;

class DcbCreator
{
    const ZERO = 0.00;
    
    /**
     * 
     * @var float
     */
    private $_repayment;
    
    /**
     * 
     * @var int
     */
    private $_catch;
    
    /**
     * 
     * @var boolean
     */
    private $_lastInstalment = false;
    
    /**
     * 
     * @var \stdClass
     */
    private $_loan;
    
    /**
     * 
     * @var \stdClass
     */
    private $_schedule;
    
    /**
     * 
     * @var array
     */
    private $_entry = [];
    
    /**
     * 
     * @var array
     */
    private $_summary = [];
    
    /**
     * 
     * @var boolean
     */
    private $_hasPayment = false;
    
    /**
     * 
     * @var \Model\Schedule
     */
    private $_model;
    
    /**
     * 
     * @var \Model\Loan
     */
    private $_loanModel;
    
    /**
     * 
     * @var \Model\Summary
     */
    private $_summaryModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var \Application\Utility\LoanStore
     */
    private $_store;
    
    /**
     * 
     * @var boolean
     */
    private $_leapYear = false;
    
    /**
     * 
     * @var boolean
     */
    private $_hasInterest = false;
    
    /**
     * 
     * @var boolean
     */
    private $_hasPenalInterest = false;
    
    /**
     * 
     * @var float
     */
    private $_collectionBalance = 0;
    
    /**
     * 
     * @var boolean
     */
    private $_collectionExceeded = false;
    
    /**
     * 
     * @var boolean
     */
    private $_delinquent = false;
    
    /**
     * 
     * @var boolean
     */
    private $_closing = false;
    
    /**
     * 
     * @var \stdClass
     */
    private $_loanSummary;
    private $_paymentMode = '';
    /**
     * 
     * @param \Application\Utility\LoanStore $store
     */
    public function __construct($store)
    {
        $this->_store = $store;
        $this->_model = new Schedule();
        $this->_loanModel = new Loan();
        $this->_summaryModel = new Summary();
        $this->_sql = $this->_model->getSql();
        $this->_schedule = $this->_store->entry();
        $this->_loan = $this->_store->loan();
        $this->_loanSource = new LoanSource();
        $this->_membTrans = new RPTransMember();
        $this->_membTransaction = new RPTransMemberDetails();
    }

    /**
     * set repayment
     * 
     * @param float $amount
     * @return $this
     */
    public function repayment($amount)
    {
        $this->_repayment = LoanHelper::amount($amount);
        return $this;
    }
    
    public function paymentMode($paymentType)
    {
        $this->_paymentMode = $paymentType;
        return $this;
    }


    /**
     * set catch
     * 
     * @param type $amount
     * @return $this
     */
    public function catchAmount($amount)
    {
        $this->_catch = (int) $amount;
        return $this;
    }
    
     public function isDcbUpdate($scheduleId)
    {
        $this->_isDcbUpdate = $scheduleId;
        return $this;
    }
    
    /**
     * set closing entry
     */
    public function closing()
    {
        $this->_closing = true;
    }
    
    /**
     * check demand
     * 
     * @return boolean
     */
    public function checkDemand()
    {
        if ($this->_closing) {
            $closingDemand = $this->_store->closingDemand();
            if ($this->_repayment >= $closingDemand['total']) {
                return false;
            }
            
            return true;
        }
        
        $demand = $this->_store->dcbDemand();
        if ($this->_repayment > 0) {
            if ($this->_repayment >= $demand[30] || $demand[27] < $this->_schedule->emi) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * run
     */
    public function run()
    {
        if (empty($this->_schedule)) {
            return false;
        }
        
        $this->_preload();
        $this->_demand();
        if ($this->_collectionExceeded) {
            return false;
        }
        
        $this->_collection();
        $this->_balance();
        $this->_ageing();
        $this->_buildSummary();
        $this->_save();
        $this->_updatePortfolio();
        $this->_delinquentPqam();
        
        return true;
    }
    
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_lastInstalment = $this->_schedule->instalment == $this->_loan->tenure;
        $this->_initializeEntry();
        $this->_leapYear = LoanHelper::leapYear($this->_schedule->instalment_date);
        if ($this->_loan->interest_rate > self::ZERO) {
            $this->_hasInterest = true;
        }
        if ($this->_loan->penal_interest_rate > self::ZERO) {
            $this->_hasPenalInterest = true;
        }
        
        if ($this->_repayment > self::ZERO) {
            $this->_hasPayment = true;
            if ($this->_catch > 0) {
                if ($this->_repayment <= $this->_catch) {
                    $this->_entry['percentage_catch'] = LoanHelper::percentage($this->_repayment, $this->_catch);
                }
            }
        }
        
        $this->_loanSummary = $this->_store->loanSummary();
    }
    
    /**
     * initialize
     */
    private function _initializeEntry()
    {
        $this->_entry = [
            'has_dcb_entry' => 1,
            'scheduled_demand_loan_outstanding' => $this->_schedule->opening_principal,
            'scheduled_demand_interest' => $this->_schedule->interest_component,
            'scheduled_demand_principal' => $this->_schedule->principal_component,
            'scheduled_demand_principal_outstanding' => $this->_schedule->closing_principal,
            'arrears_demand_penal_interest' => self::ZERO,
            'arrears_demand_interest_arrears' => self::ZERO,
            'arrears_demand_principal_arrears' => self::ZERO,
            'arrears_demand_total' => self::ZERO,
            'total_demand' => $this->_schedule->emi,
            'collection_penal_interest' => self::ZERO,
            'collection_interest_arrears' => self::ZERO,
            'collection_current_interest' => self::ZERO,
            'collection_principal_arrears' => self::ZERO,
            'collection_current_principal' => self::ZERO,
            'collection_prepayment' => self::ZERO,
            'collection_repaid' => $this->_repayment,
            'catch_amount' => $this->_catch,
            'percentage_catch' => self::ZERO,
            'balance_interest_overdue' => self::ZERO,
            'balance_principal_overdue' => self::ZERO,
            'balance_total_overdue' => self::ZERO,
            'balance_scheduled_principal_outstanding' => $this->_schedule->closing_principal,
            'balance_actual_principal_outstanding' => self::ZERO,
            'balance_actual_principal_interest_outstanding' => self::ZERO,
            'ageing_factor' => 0.0,
            'ageing_days' => 0,
            'dcb_created_at' => DateHelper::timestamp(),
            'actual_opening_principal_outstanding' => $this->_schedule->opening_principal,
        ];
    }
    
    /**
     * calculate demand
     */
    private function _demand()
    {
        if ($this->_closing) {
            $demand = $this->_store->closingDemand();
            $totalOutstanding = $demand['total'];
            if ($this->_repayment >= $totalOutstanding) {
                $this->_collectionExceeded = true;
                return;
            }
        } else {
            $demand = $this->_store->dcbDemand();
            $totalOutstanding = $demand[30];
            if ($this->_repayment >= $totalOutstanding || $demand[27] < $this->_schedule->emi) {
                $this->_collectionExceeded = true;
                return;
            }
        }
        
        $this->_entry['scheduled_demand_loan_outstanding'] = $demand[DcbColumn::LOAN_OUTSTANDING];
        $this->_entry['scheduled_demand_interest'] = $demand[DcbColumn::INTEREST];
        $this->_entry['scheduled_demand_principal'] = $demand[DcbColumn::PRINCIPAL];
        $this->_entry['scheduled_demand_principal_outstanding'] = $demand[DcbColumn::PRINCIPAL_OUTSTANDING];
        $this->_entry['arrears_demand_penal_interest'] = $demand[DcbColumn::PENAL_INTEREST_DEMAND];
        $this->_entry['arrears_demand_interest_arrears'] = $demand[DcbColumn::INTEREST_ARREARS_DEMAND];
        $this->_entry['arrears_demand_principal_arrears'] = $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND];
        $this->_entry['arrears_demand_total'] = $demand[DcbColumn::TOTAL_ARREARS_DEMAND];
        $this->_entry['total_demand'] = $demand[DcbColumn::TOTAL_DEMAND];
        $this->_entry['actual_opening_principal_outstanding'] = $demand[DcbColumn::SCHEDULED_PRINCIPAL_OUTSTANDING];
    }
    
    /**
     * calculate collection
     * 
     * @return null
     */
    private function _collection()
    {
        if ($this->_repayment < 0.01) {
            return;
        }
        
        $this->_collectionBalance = $this->_repayment;
        $this->_penalInterestCollection();
    }
    
    /**
     * penal interest collection
     */
    private function _penalInterestCollection()
    {
        $this->_collect('arrears_demand_penal_interest', 'collection_penal_interest');
        
        if ($this->_collectionBalance > self::ZERO) {
            $this->_interestArrearsCollection();
        }
    }
    
    /**
     * interest arrears collection
     */
    private function _interestArrearsCollection()
    {
        $this->_collect('arrears_demand_interest_arrears', 'collection_interest_arrears');
        
        if ($this->_collectionBalance > self::ZERO) {
            $this->_currentInterestCollection();
        }
    }
    
    /**
     * current interest collection
     */
    private function _currentInterestCollection()
    {
        $this->_collect('scheduled_demand_interest', 'collection_current_interest');
        
        if ($this->_collectionBalance > self::ZERO) {
            $this->_principalArrearsCollection();
        }
    }
    
    /**
     * principal arrears collection
     */
    private function _principalArrearsCollection()
    {
        $this->_collect('arrears_demand_principal_arrears', 'collection_principal_arrears');
                
        if ($this->_collectionBalance > self::ZERO) {
            $this->_currentPrincipalCollection();
        }
    }
    
    /**
     * current principal collection
     */
    private function _currentPrincipalCollection()
    {
        $this->_collect('scheduled_demand_principal', 'collection_current_principal');
        
        if ($this->_collectionBalance > self::ZERO) {
            $this->_entry['collection_prepayment'] = $this->_collectionBalance;
        }
    }
    
    /**
     * collect demand
     * 
     * @param string $demand
     * @param string $collection
     */
    private function _collect($demand, $collection)
    {
        if ($this->_entry[$demand] > self::ZERO) {
            if ($this->_entry[$demand] > $this->_collectionBalance) {
                $this->_entry[$collection] = $this->_collectionBalance;
                $this->_collectionBalance = self::ZERO;
            } else {
                $this->_entry[$collection] = $this->_entry[$demand];
                $this->_collectionBalance -= $this->_entry[$demand];
            }
        }
    }
    
    /**
     * calculate balance
     */
    private function _balance()
    {
        if ($this->_closing) {
            return;
        }
        
        $this->_interestBalance();
        $this->_principalBalance();
        $this->_overdueBalance();
    }
    
    /**
     * interest balance
     */
    private function _interestBalance()
    {
        $demandFields = [
            'scheduled_demand_interest',
            'arrears_demand_penal_interest',
            'arrears_demand_interest_arrears',
        ];
        $demand = $this->_entrySum($demandFields);
        
        $collectionFields = [
            'collection_penal_interest',
            'collection_interest_arrears',
            'collection_current_interest',
        ];
        $collection = $this->_entrySum($collectionFields);
        
        $balance = $demand - $collection;
        
        if ($this->_loan->interest_rate > 0.00 && $this->_loanSummary->collection_prepayment > 0.00) {
            $dailyRate = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $this->_schedule->instalment_date);
            $prepayInterest = LoanHelper::amount($this->_loanSummary->collection_prepayment * $dailyRate * $this->_schedule->emi_days);
            $balance = $balance - $prepayInterest;
        }
        
        $this->_entry['balance_interest_overdue'] = $balance;
    }
    
    /**
     * principal balance
     */
    private function _principalBalance()
    {
        $demandFields = [
            'scheduled_demand_principal',
            'arrears_demand_principal_arrears',
        ];
        $demand = $this->_entrySum($demandFields);

        $collectionFields = [
            'collection_principal_arrears',
            'collection_current_principal',
            'collection_prepayment',
        ];
        $collection = $this->_entrySum($collectionFields);
        
        $this->_entry['balance_principal_overdue'] = $demand - $collection;
    }
    
    /**
     * overdue balance
     */
    private function _overdueBalance()
    {
        $this->_entry['balance_total_overdue'] = $this->_entry['balance_interest_overdue'] + $this->_entry['balance_principal_overdue'];
        $this->_entry['balance_actual_principal_outstanding'] = $this->_entry['scheduled_demand_principal_outstanding'] + $this->_entry['balance_principal_overdue'];
        $this->_entry['balance_actual_principal_interest_outstanding'] = $this->_entry['balance_actual_principal_outstanding'] + $this->_entry['balance_interest_overdue'];
    }
    
    /**
     * calculate ageing
     */
    private function _ageing()
    {
        $difference = $this->_entry['balance_actual_principal_interest_outstanding'] - $this->_schedule->closing_principal;
        $factor = 0;
        if ($this->_schedule->emi > 0.00) {
            $factor = $difference / $this->_schedule->emi;
        } else {
            $factor = $difference / $this->_loan->emi;
        }
        
        $this->_entry['ageing_factor'] = sprintf('%0.1f', $factor);
        if ($this->_entry['ageing_factor'] > self::ZERO) {
            $this->_entry['ageing_days'] = ceil($this->_entry['ageing_factor'] * 30);
        }
    }
    
    /**
     * calculate summary
     */
    private function _buildSummary()
    {
        $direct = [
            'scheduled_demand_loan_outstanding', 'scheduled_demand_principal_outstanding',
            'balance_interest_overdue', 'balance_principal_overdue', 'balance_total_overdue',
            'balance_scheduled_principal_outstanding', 'balance_actual_principal_outstanding',
            'balance_actual_principal_interest_outstanding', 'ageing_factor', 'ageing_days',
        ];
        
        $cumulative = [
            'scheduled_demand_interest', 'scheduled_demand_principal',
            'arrears_demand_penal_interest', 'arrears_demand_interest_arrears',
            'arrears_demand_principal_arrears', 'arrears_demand_total', 
            'total_demand', 'collection_penal_interest', 'collection_interest_arrears',
            'collection_current_interest', 'collection_principal_arrears',
            'collection_current_principal', 'collection_prepayment', 'collection_repaid',
            'catch_amount', 
        ];

        $this->_summary = [
            'schedule_id' => $this->_schedule->id,
            'created_at' => DateHelper::timestamp(),
        ];
        
        foreach ($direct as $item) {
            $this->_summary[$item] = $this->_entry[$item];
        }
        
        $previous = $this->_store->previous();
        
        if (empty($previous) || 1 == $this->_schedule->first_instalment) {
            foreach ($cumulative as $field) {
                $this->_summary[$field] = $this->_entry[$field];
            }
            $this->_summary['percentage_catch'] = $this->_entry['percentage_catch'];
            $this->_summary['emi'] = $this->_schedule->emi;
        } else {
            $last = $this->_summaryModel->selectByIdColumn('schedule_id', $previous->id);
            foreach ($cumulative as $column) {
                $this->_summary[$column] = $last->{$column} + $this->_entry[$column];
            }
            $this->_summary['emi'] = $last->emi + $this->_schedule->emi;
            $this->_summary['percentage_catch'] = LoanHelper::percentage($this->_summary['collection_repaid'], $this->_summary['catch_amount']);
        }
    }
    
    /**
     * save entry
     * 
     * @return null
     */
    private function _save()
    {
        // save dcb entry
        $this->_model->updateById($this->_entry, $this->_schedule->id);
        
        // update summay
        $this->_summaryModel->insert($this->_summary);
        
        // update catch
        $this->_updateCatch();
        
        // update loan
        $this->_updateLoan();
        //
        //Create Voucher and Receipt
        if (LoanHelper::LOAN_SOURCE_FMC != $this->_loan->loan_source_id) { 
                if($this->_paymentMode == 'current_installment'){
                    if($this->_repayment>0){
                        $this->_generateTransactions();
                    }
                } else  if($this->_paymentMode == 'update_installment'){
                    $this->_updateTransactions($this->_schedule->id);
                }
        }
        }
    
    /**
     * update loan
     * 
     * @return null
     */
    private function _updateLoan()
    {
        $update = [
            'interest_repayment' => $this->_summary['collection_current_interest'],
            'principal_repayment' => $this->_summary['collection_current_principal'],
            'paid_tenures' => $this->_schedule->instalment,
            'updated_at' => $this->_summary['created_at'],
            'last_updated' => $this->_schedule->instalment_date,
        ];
        
        if ($this->_closing) {
            $update['closed'] = 1;
            $update['closing_date'] = $this->_schedule->instalment_date;
        } else {
            if ($this->_lastInstalment || ($this->_entry['balance_actual_principal_interest_outstanding'] < 0.01 
                    && $this->_entry['balance_total_overdue'] < 0.01 )) {
                $update['closed'] = 1;
                $update['closing_date'] = $this->_schedule->instalment_date;
                if ($this->_entry['balance_total_overdue'] > 0.00 || $this->_entry['balance_actual_principal_interest_outstanding'] > 0.00) {
                    $update['loan_status_id'] = LoanHelper::STATUS_DELINQUENT;
                    $this->_delinquent = true;
                }
            }
        }
        
        $this->_loanModel->updateById($update, $this->_store->id());
        $this->_updateLoanSummary();
    }
    
    private function _generateTransactions()
    {
        //Payment Voucher
        $memberModel = new Member();
        $member = $memberModel->find($this->_loan->member_id);
        $fmc_id = $member->fmc_id;
        $now = DateHelper::timestamp();
        $transBy = 0;
        $loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$this->_loan->loan_source_id}";
        $loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
        $code = $loanSourceType[0]->code;
        $headData['head_name'] =  $loanSourceType[0]->loan_source. " Loan Repaid";
        $headData['code'] = $code."_loan_repaid";
        $headData['transType'] = 'Direct Expense';        
        //$type = "Loan Repaid";
        $voucherInsert = [
            //'transDate' => DateHelper::today(),
            'transDate' => $this->_schedule->instalment_date,
            'member_id' => $this->_loan->member_id,
            'amount'  => $this->_repayment,
            'transBy' => $transBy,
            'created_by' => 9999,
            'created_at' => $now,
           // 'type' => $type,
            'updated_at' => $now, 
            'loan_id' => $this->_loan->id,
            'loan_source_id' => $this->_loan->loan_source_id,
            'loan_schedule_id' => $this->_schedule->id
        ];
        $trans = new Transactions();
        $trans->generateVoucher($headData, $voucherInsert, $fmc_id);
        
        //Receipt Creation
        $fmc_id = LoanHelper::MF_ACCOUNT_ID;
        //$loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$loan->loan_source_id}";
        //$loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
        $code = $loanSourceType[0]->code;
        
        $particulars = $loanSourceType[0]->loan_source ." Loan Repayment";
        $mfHeadData['head_name'] =  $loanSourceType[0]->loan_source." Loan Repayment";
        $mfHeadData['code'] = $code. "_loan_repayment"; 
        
        $mfHeadData['transType'] = 'Direct Income';
        $insert = [
            'receipt' => 0,
           // 'transDate' => DateHelper::today(),
            'transDate' => $this->_schedule->instalment_date,
            'member_id' => $this->_loan->member_id,
            'amount'  => $this->_repayment,
            'remarks' => $particulars,
            'created_by' => 9999,
            'transBy' => $transBy,
            'created_at' => $now,
            'updated_at' => $now, 
            'loan_id' => $this->_loan->id,
            'loan_schedule_id' => $this->_schedule->id
        ];
        $trans->generateReceipt($mfHeadData, $insert, $fmc_id);
    }
    /**
     * update loan summary
     */
    private function _updateLoanSummary()
    {
        $update = [
            'modified_at' => $this->_summary['created_at'],
            'has_dcb' => 1,
            'paid_tenures' => $this->_schedule->instalment,
            'ageing' => $this->_entry['ageing_days'],
            'par' => 0,
            'as_on_date' => $this->_schedule->instalment_date,
        ];
        
        $direct = [
            'scheduled_demand_loan_outstanding', 'collection_penal_interest',
            'collection_interest_arrears', 'collection_current_interest', 
            'collection_principal_arrears', 'collection_current_principal', 
            'collection_prepayment', 'collection_repaid',
            'balance_interest_overdue', 'balance_principal_overdue', 'balance_total_overdue',
            'balance_scheduled_principal_outstanding', 'balance_actual_principal_outstanding',
            'balance_actual_principal_interest_outstanding', 
        ];
        
        foreach ($direct as $field) {
            $update[$field] = $this->_summary[$field];
        }
        
        if ($update['balance_total_overdue'] > 0 && $update['ageing'] > 0) {
            $update['par'] = $update['balance_actual_principal_interest_outstanding'];
        }
        
        $loanSummaryModel = new LoanSummary();
        $loanSummaryModel->update($update, ['loan_id' => $this->_store->id()], 1);
    }
    
    /**
     * update catch
     * 
     * @return null
     */
    private function _updateCatch()
    {
        if (0 == $this->_catch) {
            return;
        }
        
        $logger = new MemberCatchLogger($this->_loan->member_id);
        $logger->setDate($this->_schedule->instalment_date)
               ->save($this->_catch);
    }
    
    /**
     * update credit portfolio
     */
    private function _updatePortfolio()
    {
        $pqam = $this->_extractPqam();
        $pqam['member_id'] = $this->_loan->member_id;
        $pqam['pqam_date'] = $this->_schedule->instalment_date;
        $pqam['loan_id'] = $this->_loan->id;
        $pqam['instalment'] = $this->_schedule->instalment;
        $pqam['created_at'] = DateHelper::timestamp();
        
        $model = new Pqam();
        $model->insert($pqam);
    }
    
    /**
     * extract pqam data from summary
     * 
     * @param \stdClass $summary
     * @return array
     */
    private function _extractPqam()
    {
        $principal = $this->_summary['collection_principal_arrears']
                   + $this->_summary['collection_current_principal']
                   + $this->_summary['collection_prepayment'];
        $interest = $this->_summary['collection_penal_interest']
                  + $this->_summary['collection_interest_arrears']
                  + $this->_summary['collection_current_interest'];
        $total = $principal + $interest;
        $overdue = $this->_summary['balance_actual_principal_interest_outstanding'] - $this->_summary['balance_scheduled_principal_outstanding'];
        
        $par = 0;
        $ageing = 0;
        if ($overdue > 0.00) {
            $par = $this->_summary['balance_actual_principal_interest_outstanding'];
            $ageing = ceil(($overdue / $this->_loan->emi) * 30);
        }
        
        return [
            'principal' => $principal,
            'interest' => $interest,
            'total' => $total,
            'scheduled_outstanding' => $this->_summary['balance_scheduled_principal_outstanding'],
            'actual_outstanding' => $this->_summary['balance_actual_principal_interest_outstanding'],
            'overdue' => $overdue,
            'ageing' => $ageing,
            'par' => $par,
            'repaid' => $this->_summary['collection_repaid'],
            'catch_amount' => $this->_summary['catch_amount'],
            'principal_arrears' => $this->_summary['collection_principal_arrears'],
            'current_principal' => $this->_summary['collection_current_principal'],
            'prepayment' => $this->_summary['collection_prepayment'],
            'principal_prepaid_current' => $this->_summary['collection_current_principal'] + $this->_summary['collection_prepayment'],
            'principal_outstanding' => $this->_summary['balance_actual_principal_outstanding'],
        ];
    }
    private function _updateTransactions($schedule_id)
    {
        
        $voucherModel = new RPTransactionDetails();       
        //Payment Voucher
        $memberModel = new Member();
        $member = $memberModel->find($this->_loan->member_id);
        $fmc_id = $member->fmc_id;
        
        $sql = $voucherModel->getSql();
        $sql->whereEqual('fmc_id', $fmc_id)
            ->whereEqual('loan_schedule_id', $schedule_id);
        $where = $sql->getWhere();
        if ($this->_repayment > 0) {
            $voucherModel->update(['amount' => $this->_repayment], $where, 1);
        }      
        if ($this->_repayment > 0) {
            $fmc_id = LoanHelper::MF_ACCOUNT_ID;
            $query1 = "UPDATE ". $this->_membTransaction->getTableQuoted() . " `t` "
                     . "inner join " . $this->_membTrans->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` "
                     . "SET `t`.`amount`= {$this->_repayment}"
                     . "where `m`.`fmc_id` = {$fmc_id} and `m`.`loan_schedule_id` = {$schedule_id}";
            $this->_membTransaction->exec($query1);
        }
    }
    /**
     * calculate entry fields sum
     * 
     * @param array $fields
     * @return float
     */
    private function _entrySum($fields)
    {
        $values = [];
        foreach ($fields as $item) {
            $values[] = $this->_entry[$item];
        }
        
        return $this->_sum($values);
    }
    
    /**
     * sum of amount values
     * 
     * @param array $values
     * @return float
     */
    private function _sum($values)
    {
        return LoanHelper::sum($values);
    }
    
    /**
     * 
     * @return null
     */
    private function _delinquentPqam()
    {
        if (! $this->_delinquent) {
            return;
        }
        
        $reportingDate = LoanHelper::reportingDate();
        if ($this->_loan->end_date == $reportingDate) {
            return;
        }
        
        $dates = LoanHelper::overdueDates($this->_loan->end_date, $reportingDate);
        if (empty($dates)) {
            return;
        }

        $outstanding = $this->_entry['balance_actual_principal_interest_outstanding'];
        $instalments = $this->_schedule->instalment;
        $default = [
            'pqam_date' => '',
            'member_id' => '',
            'loan_id' => '',
            'instalment' => '',
            'actual_outstanding' => 0,
            'overdue' => 0,
            'ageing' => 0,
            'par' => 0,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $pqamModel = new Pqam();
        $insert = $pqamModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($dates as $item) {
            $date = $item['date'];
            $record = $default;
            $record['pqam_date'] = $date;
            $record['member_id'] = $this->_loan->member_id;
            $record['loan_id'] = $this->_loan->id;
            $record['instalment'] = $instalments;

            if ($this->_loan->interest_rate > 0.00) {
                $dailyRate = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $date);
                $interest = LoanHelper::amount($outstanding * $dailyRate * $item['days']);
                $outstanding += $interest;
            }

            $record['actual_outstanding'] = $outstanding;
            $record['overdue'] = $outstanding;
            $record['par'] = $outstanding;
            $ageing = ceil(($outstanding / $this->_loan->emi) * 30);
            if ($ageing > 0) {
                $record['ageing'] = $ageing;
            }
            $insert->execute(array_values($record));
        }
    }
    
}
