<?php
/**
 * Application DcbClose Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\{LoanHelper, LoanStore};
use Shared\Utility\Transactions;
use Model\{Loan, Schedule, Summary, LoanSummary, LoanSource};
use Application\Helper\CommentLogger;
use Web\Utility\DateHelper;


class DcbClose
{
    const ZERO = 0.00;
    
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
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var array
     */
    private $_demand;
    
    /**
     * 
     * @var boolean
     */
    private $_lastInstalment = false;
    private $_loanSource;
    
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
        $this->_loan = $this->_store->loan();
        $this->_demand = $this->_store->closingDemand();
        $this->_sql->whereId('loan_id', $this->_loan->id)
                   ->whereZero('has_dcb_entry');
        $this->_schedule = $this->_model->selectRow($this->_sql->getWhere(), 'instalment');
        $this->_loanSource = new LoanSource();
    }
    
    /**
     * run
     */
    public function run($formValues)
    {
        $this->_formValues = $formValues;
        $this->_preload();
        $this->_buildSummary();
        $this->_save();
        
        return true;
    }
    
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_lastInstalment = $this->_schedule->instalment == $this->_loan->tenure;
        $this->_initializeEntry();
        $this->_entry['scheduled_demand_interest'] = $this->_demand['interest'];
        $this->_entry['arrears_demand_penal_interest'] = $this->_demand['penal_interest'];
        $this->_entry['collection_penal_interest'] = $this->_demand['penal_interest'];
        $this->_entry['collection_current_interest'] = $this->_demand['interest'];
        $this->_entry['collection_prepayment'] = $this->_demand['outstanding'];
        $this->_entry['collection_prepayment'] = $this->_demand['outstanding'];
        $this->_entry['actual_opening_principal_outstanding'] = $this->_demand['opening_pricipal'];
    }
    
    /**
     * initialize
     */
    private function _initializeEntry()
    {
        $this->_entry = [
            'has_dcb_entry' => 1,
            'scheduled_demand_loan_outstanding' => self::ZERO,
            'scheduled_demand_interest' => self::ZERO,
            'scheduled_demand_principal' => self::ZERO,
            'scheduled_demand_principal_outstanding' => self::ZERO,
            'arrears_demand_penal_interest' =>  self::ZERO,
            'arrears_demand_interest_arrears' => self::ZERO,
            'arrears_demand_principal_arrears' => self::ZERO,
            'arrears_demand_total' => self::ZERO,
            'total_demand' => self::ZERO,
            'collection_penal_interest' => self::ZERO,
            'collection_interest_arrears' => self::ZERO,
            'collection_current_interest' => self::ZERO,
            'collection_principal_arrears' => self::ZERO,
            'collection_current_principal' => self::ZERO,
            'collection_prepayment' => self::ZERO,
            'collection_repaid' => self::ZERO,
            'catch_amount' => self::ZERO,
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
            'actual_opening_principal_outstanding' =>  self::ZERO,
            
        ];
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
        
        // update loan
        $this->_updateLoan();
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
            'closed' => 1,
            'closing_date' => DateHelper::fromDatepicker($this->_formValues['closing_date']),
        ];
        
        if (! $this->_lastInstalment) {
            $update['loan_status_id'] = LoanHelper::STATUS_PRECLOSED;
        }
        
        $this->_loanModel->updateById($update, $this->_store->id());
        $this->_updateLoanSummary();
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
            'ageing' => 0,
            'par' => 0,
            'as_on_date' => $this->_schedule->instalment_date,
            'closed_within_tenure' => 1,
            'closing_amount_payable' => LoanHelper::amount($this->_formValues['total']),
            'closing_payment_date' => DateHelper::fromDatepicker($this->_formValues['payment_date']),
            'closing_amount_paid' => LoanHelper::amount($this->_formValues['paid_amount']),
            'closing_receipt_number' => $this->_formValues['receipt_number'],
            'closing_fmc_voucher_number' => $this->_formValues['fmc_voucher_number'],
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
        
        $loanSummaryModel = new LoanSummary();
        $loanSummaryModel->update($update, ['loan_id' => $this->_store->id()], 1);
        if (LoanHelper::LOAN_SOURCE_FMC != $this->_loan->loan_source_id) { 
            $this->_generateTransactions();
        } /*else {
            //FMC receipt creation
            $store = new LoanStore($this->_loan->id);        
            $fmc = $store->fmc();
            $fmc_id = $fmc->id;
            $now = DateHelper::timestamp();
            $mfHeadData['head_name'] =  "DCB Pre-closure";
            $mfHeadData['code'] = "DCB_pre_closure";                
            $mfHeadData['transType'] = 'Direct Income';
            $insert = [
                'receipt' => $this->_formValues['receipt_number'],
                'transDate' => DateHelper::fromDatepicker($this->_formValues['closing_date']),
                'member_id' => $this->_loan->member_id,
                'remarks' =>  "DCB Pre-closure",
                'created_by' => 9999,
                'transBy' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'loan_id' => $this->_loan->id,
            ];
            $insert['amount'] = $this->_formValues['paid_amount'];
            $trans = new Transactions();
            $trans->generateReceipt($mfHeadData, $insert, $fmc_id);  
        }*/
    }
    
    private function _generateTransactions()
    {
        //Payment Voucher
        $store = new LoanStore($this->_loan->id);        
        $fmc = $store->fmc();
        $fmc_id = $fmc->id;
        $now = DateHelper::timestamp();
        $transBy = 0;
        $loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$this->_loan->loan_source_id}";
        $loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
        $code = $loanSourceType[0]->code;
        $headData['head_name'] =  $loanSourceType[0]->loan_source. " Loan Pre-closure";
        $headData['code'] = $code."_loan_pre_closure";
        $headData['transType'] = 'Direct Expense';  
        $notes = " Loan Pre-closure [FMC: {$fmc_id}, Amount:{$this->_formValues['paid_amount']}]";
        $userID = 9;
        $this->_saveComment($this->_loan->id, $userID, $notes);
        $voucherInsert = [
            'transDate' => DateHelper::fromDatepicker($this->_formValues['closing_date']),
            'member_id' => $this->_loan->member_id,
            'amount'  => $this->_formValues['paid_amount'],
            'transBy' => $transBy,
            'created_by' => 9999,
            'created_at' => $now,
            'updated_at' => $now, 
            'loan_id' => $this->_loan->id,
            'loan_source_id' => $this->_loan->loan_source_id
        ];
        $trans = new Transactions();
        $trans->generateVoucher($headData, $voucherInsert, $fmc_id);
        
        //Receipt Creation
        $fmc_id = LoanHelper::MF_ACCOUNT_ID;
        //$loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$loan->loan_source_id}";
        //$loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
        $code = $loanSourceType[0]->code;
        
        $particulars = $loanSourceType[0]->loan_source ." Loan Pre-closure";
        $mfHeadData['head_name'] =  $loanSourceType[0]->loan_source." Loan Pre-closure";
        $mfHeadData['code'] = $code. "_loan_pre_closure"; 
        $notes = " Loan Pre-closure [FMC: MF, Amount:{$this->_formValues['paid_amount']}]";
        $this->_saveComment($this->_loan->id, $userID, $notes);
        $mfHeadData['transType'] = 'Direct Income';
        $insert = [
            'receipt' => 0,
            'transDate' => DateHelper::fromDatepicker($this->_formValues['closing_date']),
            'member_id' => $this->_loan->member_id,
            'amount'  => $this->_formValues['paid_amount'],
            'remarks' => $particulars,
            'created_by' => 9999,
            'transBy' => 0,
            'created_at' => $now,
            'updated_at' => $now, 
            'loan_id' => $this->_loan->id
        ];
        $trans->generateReceipt($mfHeadData, $insert, $fmc_id);
    }
    
     private function _saveComment($loanId, $administratorId, $notes = null)
    {
        $logger = new CommentLogger($administratorId);
        $logger->id($loanId)
               ->save($notes);
    }
}
