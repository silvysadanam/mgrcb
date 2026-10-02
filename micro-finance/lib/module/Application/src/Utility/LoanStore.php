<?php
/**
 * Application LoanStore Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\{DcbColumn, LoanHelper};

use Model\{Loan, Schedule, LoanType, LoanSource, Summary, Member, Fmc, LoanTimeline, LoanComment};
use Model\{LoanReschedule, LoanSummary, LoanStatus, Pqam, WriteOff, Inactive, RPHeads};
use Application\Helper\MemberCatchLogger;
use Application\Helper\CommentLogger;
use Shared\Model\TitleModel;
use Shared\Utility\Transactions;
use Web\Utility\DateHelper;


class LoanStore
{
    const ZERO_NUMERIC = 0.00;
    const ZERO_STRING = '0.00';
    
    /**
     * 
     * @var int
     */
    private $_id;
    
    /**
     * 
     * @var \stdClass
     */
    private $_loan;
    
    /**
     * 
     * @var array
     */
    private $_schedule;
    
    /**
     * 
     * @var \Model\Loan
     */
    private $_model;
    
    /**
     * 
     * @var \Model\Schedule
     */
    private $_scheduleModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_summary;

    private $approval_status;
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_scheduleIds = [];
    
    /**
     * 
     * @var boolean
     */
    private $_hasDcb = false;
    
    /**
     * 
     * @var boolean
     */
    private $_dataEntry = false;
    
    /**
     * 
     * @var \stdClass
     */
    private $_member;
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    
    /**
     * 
     * @var string
     */
    private $_loanType;
    
    /**
     * 
     * @var \stdClass
     */
    private $_entry;
    
    /**
     * 
     * @var \stdClass
     */
    private $_previous;
    
    /**
     * 
     * @var string
     */
    private $_loanSource;
    
    /**
     * 
     * @var \Model\Summary
     */
    private $_summaryModel;
    
    /**
     * 
     * @var \Model\LoanSummary
     */
    private $_loanSummaryModel;
    
    /**
     * 
     * @var boolean
     */
    private $_hasBacklog = false;
    
    /**
     * 
     * @var \stdClass
     */
    private $_loanSummary;
    
    /**
     * 
     * @var string
     */
    private $_whereLoan;
    private $_loanTypeModel;
    private $_loanSourceModel;
    private $_headModel;
    /**
     * 
     * @param int $loanId
     */
    public function __construct($loanId)
    {
        $this->_id = $loanId;
        $this->_model = new Loan();
        $this->_sql = $this->_model->getSql();
        $this->_sql->whereId('loan_id', $loanId);
        $this->_whereLoan = $this->_sql->getWhere();
        $this->_sql->clear();
        $this->_loan = $this->_model->find($loanId);
        $this->_summaryModel = new Summary();
        $this->_scheduleModel = new Schedule();
        $this->_loanSummaryModel = new LoanSummary();
        $this->_loanSummary = $this->_loanSummaryModel->selectRow($this->_whereLoan);
        $this->_schedule = $this->_scheduleModel->selectIndexed($this->_whereLoan, 'instalment');
        $this->_scheduleIds = array_keys($this->_schedule);
        //$this->_loanStatus = $this->loanStatus($this->_loan->loan_status_id);
        $this->_sql->whereIn('schedule_id', $this->_scheduleIds);
        $where = $this->_sql->getWhere();
        if ($this->_summaryModel->selectTotal($where) > 0) {
            $this->_summary = $this->_summaryModel->selectRow($where, 'id DESC');
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $loanId)
                   ->whereOne('has_dcb_entry');
        if ($this->_scheduleModel->selectTotal($this->_sql->getWhere()) > 0) {
            $this->_hasDcb = true;
        }
        
        if (0 == $this->_loan->closed) {
            if (! DateHelper::isFutureDate($this->_loan->start_date)) {
                $reportingDate = DateHelper::timestamp(LoanHelper::reportingDate());
                foreach ($this->_schedule as $item) {
                    if (0 == $item->has_dcb_entry) {
                        if (! DateHelper::isFutureDate($item->instalment_date)) {
                            if (DateHelper::timestamp($item->instalment_date) < $reportingDate) {
                                $this->_hasBacklog = true;
                            }
                            $this->_dataEntry = true;
                            $this->_entry = $item;
                        }
                        break;
                    }
                    $this->_previous = $item;
                }
            }
        }
        
        $memberModel = new Member();
        $this->_member = $memberModel->find($this->_loan->member_id);
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        $titleModel = new TitleModel();
        $this->_loanType = $titleModel->loanType($this->_loan->loan_type_id);
        $this->_loanSource = $titleModel->loanSource($this->_loan->loan_source_id);
        $this->_loanTypeModel = new LoanType();
        $this->_loanSourceModel = new LoanSource();
        $this->_headModel = new RPHeads();
    }
    
    /**
     * get loan summary
     * 
     * @return \stdClass
     */
    public function loanSummary()
    {
        return $this->_loanSummary;
    }
    
    /**
     * get loan reschedule
     * 
     * @return boolean|\stdClass
     */
    public function loanReschedule()
    {
        $rescheduleModel = new LoanReschedule();
        if ($rescheduleModel->checkByColumnId('source_loan_id', $this->_id)) {
            return $rescheduleModel->selectByIdColumn('source_loan_id', $this->_id);
        }
        
        return false;
    }
    
    /**
     * get rescheduled information
     * 
     * @return array
     */
    public function rescheduled()
    {
        $rescheduled = [];
        $rescheduleModel = new LoanReschedule();
        if ($rescheduleModel->checkByColumnId('source_loan_id', $this->_id)) {
            $rescheduled['source'] = $rescheduleModel->selectByIdColumn('source_loan_id', $this->_id);
        }
        
        if ($rescheduleModel->checkByColumnId('new_loan_id', $this->_id)) {
            $rescheduled['target'] = $rescheduleModel->selectByIdColumn('new_loan_id', $this->_id);
        }
        
        return $rescheduled;
    }
    
    /**
     * get write off details
     * 
     * @return \stdClass
     */
    public function writeOff()
    {
        $writeOffModel = new WriteOff();
        if ($writeOffModel->checkByColumnId('loan_id', $this->_id)) {
            return $writeOffModel->selectByIdColumn('loan_id', $this->_id);
        }
        
        return null;
    }
    
    /**
     * get loan status
     * 
     * @return string
     */
    public function status()
    {
        if ($this->_loan->closed > 0) {
            if ($this->_loan->loan_status_id > 0) {
                $statusModel = new LoanStatus();

                return $statusModel->selectColumnById('loan_status', $this->_loan->loan_status_id);
            }
            
            return 'Closed within tenure';
        }
        
        return 'Active';
    }

     /**
     * get loan actual status
     * 
     * @return string
     */
    public function loanStatus()
    {
            if ($this->_loan->loan_status_id > 0) {
                $statusModel = new LoanStatus();

                return $statusModel->selectColumnById('loan_status', $this->_loan->loan_status_id);
            }
            
        return 'Pre-Approved';    
    }
    
    /**
     * get oan id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * get schedule
     * 
     * @return array
     */
    public function schedule()
    {
        return $this->_schedule;
    }
    
    /**
     * get loan
     * 
     * @return \stdClass
     */
    public function loan()
    {
        return $this->_loan;
    }
    
    /**
     * get summary
     * 
     * @return \stdClass
     */
    public function summary()
    {
        return $this->_summary;
    }
    
    /**
     * get dcb status
     * 
     * @return boolean
     */
    public function hasDcb()
    {
        return $this->_hasDcb;
    }
    
    /**
     * get data entry enabled
     * 
     * @return boolean
     */
    public function dataEntry()
    {
        return $this->_dataEntry;
    }
    
    /**
     * get member
     * 
     * @return \stdClass
     */
    public function member()
    {
        return $this->_member;
    }
    
    /**
     * get fmc
     * 
     * @return \stdClass
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    /**
     * get loan type
     * 
     * @return string
     */
    public function loanType()
    {
        return $this->_loanType;
    }
    
    /**
     * get loan source
     * 
     * @return string
     */
    public function loanSource()
    {
        return $this->_loanSource;
    }
    
    /**
     * get current entry
     * 
     * @return \stdClass
     */
    public function entry()
    {
        return $this->_entry;
    }
    
    /**
     * get previous entry
     * 
     * @return \stdClass
     */
    public function previous()
    {
        return $this->_previous;
    }
    
    /**
     * check backlog
     * 
     * @return boolean
     */
    public function hasBacklog()
    {
        return $this->_hasBacklog;
    }
    
    /**
     * get payment
     * 
     * @return array
     */
    public function payment()
    {
        $entries = [];
        if ($this->_hasDcb) {
            foreach ($this->_schedule as $item) {
                if ($item->has_dcb_entry > 0) {
                    $entries[] = $item;
                } else {
                    break;
                }
            }
        }
        
        return $entries;
    }
    
    /**
     * get catch period
     * 
     * @return array
     */
    public function catchPeriod()
    {
        $period = [];
        if ($this->_hasDcb) {
            $this->_sql->clear();
            $this->_sql->whereIn('id', $this->_scheduleIds)
                       ->whereOne('has_dcb_entry');
            $period = $this->_scheduleModel->selectColumns('instalment_date', $this->_sql->getWhere(), 'id');
            
        }
        
        return $period;
    }
    
    /**
     * check closed status
     * 
     * @return boolean
     */
    public function closed()
    {
        return $this->_loan->closed > 0;
    }
    
    /**
     * check defaulter
     * 
     * @return boolean
     */
    public function defaulter()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_loan->member_id)
                   ->whereOne('closed')
                   ->whereGreater('loan_status_id', 0);
        
        return $this->_model->selectTotal($this->_sql->getWhere()) > 0;
    }
    
    /**
     * get reporting date
     * 
     * @return string
     */
    public function reportingDate()
    {
        return LoanHelper::reportingDate();
    }
    
    /**
     * get summary list
     * 
     * @return array
     */
    public function summaryAll()
    {
        if (empty($this->_summary)) {
            return [];
        }

        $this->_sql->clear();
        $this->_sql->whereIn('schedule_id', $this->_scheduleIds);

        return $this->_summaryModel->select($this->_sql->getWhere(), 'id');
    }
    
    /**
     * get backlog list
     * 
     * @return array
     */
    public function getBacklog()
    {
        $reportingDate = LoanHelper::reportingDate();
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_scheduleIds)
                   ->whereLess('instalment_date', $reportingDate)
                   ->whereZero('has_dcb_entry');
        
        $columns = [
            'id', 'instalment', 'emi', 'instalment_date',
            'interest_component', 'principal_component',
        ];
        
        return $this->_scheduleModel->selectFields($columns, $this->_sql->getWhere(), 'id');
    }
    
    /**
     * reset dcb entries
     * 
     * @return null
     */
    public function resetDcb()
    {
        if (! $this->_hasDcb) {
            return;
        }
        
        $period = $this->catchPeriod();
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_scheduleIds)
                   ->whereOne('has_dcb_entry');
        $where = $this->_sql->getWhere();
        $total = $this->_scheduleModel->selectTotal($where);
        $scheduleIds = $this->_scheduleModel->selectIds($where);
        $reset = [
            'has_dcb_entry' => '0',
            'scheduled_demand_loan_outstanding' => self::ZERO_STRING,
            'scheduled_demand_interest' => self::ZERO_STRING,
            'scheduled_demand_principal' => self::ZERO_STRING,
            'scheduled_demand_principal_outstanding' => self::ZERO_STRING,
            'arrears_demand_penal_interest' => self::ZERO_STRING,
            'arrears_demand_interest_arrears' => self::ZERO_STRING,
            'arrears_demand_principal_arrears' => self::ZERO_STRING,
            'arrears_demand_total' => self::ZERO_STRING,
            'total_demand' => self::ZERO_STRING,
            'collection_penal_interest' => self::ZERO_STRING,
            'collection_interest_arrears' => self::ZERO_STRING,
            'collection_current_interest' => self::ZERO_STRING,
            'collection_principal_arrears' => self::ZERO_STRING,
            'collection_current_principal' => self::ZERO_STRING,
            'collection_prepayment' => self::ZERO_STRING,
            'collection_repaid' => self::ZERO_STRING,
            'catch_amount' => '0',
            'percentage_catch' => self::ZERO_STRING,
            'balance_interest_overdue' => self::ZERO_STRING,
            'balance_principal_overdue' => self::ZERO_STRING,
            'balance_total_overdue' => self::ZERO_STRING,
            'balance_scheduled_principal_outstanding' => self::ZERO_STRING,
            'balance_actual_principal_outstanding' => self::ZERO_STRING,
            'balance_actual_principal_interest_outstanding' => self::ZERO_STRING,
            'ageing_factor' => '0.0',
            'ageing_days' => '0',
            'dcb_created_at' => '0',
        ];
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $scheduleIds);
        $whereDcbSchedules = $this->_sql->getWhere();
        $this->_scheduleModel->update($reset, $whereDcbSchedules, $total);
        
        //$period = $this->_scheduleModel->selectColumns('instalment_date', $whereDcbSchedules);
        
        $this->_sql->clear();
        $this->_sql->whereIn('schedule_id', $scheduleIds);
        $this->_summaryModel->delete($this->_sql->getWhere(), $total);
        
        $update = [
            'closed' => '0',
            'interest_repayment' => self::ZERO_STRING,
            'principal_repayment' => self::ZERO_STRING,
            'paid_tenures' => '0',
            'last_updated' => DateHelper::today(),
        ];
        
        $this->_model->updateById($update, $this->_id);
        
        $loanSummary = [
            'modified_at' => DateHelper::timestamp(),
            'has_dcb' => 0,
            'paid_tenures' => 0,
            'as_on_date' => $this->_loan->loan_date,
        ];
        
        $summaryReset = [
            'scheduled_demand_loan_outstanding', 'collection_penal_interest',
            'collection_interest_arrears', 'collection_current_interest', 
            'collection_principal_arrears', 'collection_current_principal', 
            'collection_prepayment', 'collection_repaid',
            'balance_interest_overdue', 'balance_principal_overdue', 'balance_total_overdue',
            'balance_scheduled_principal_outstanding', 'balance_actual_principal_outstanding',
            'balance_actual_principal_interest_outstanding', 'ageing', 'par',
        ];
        
        foreach ($summaryReset as $field) {
            $loanSummary[$field] = 0;
        }
        
        $this->_loanSummaryModel->updateById($loanSummary, $this->_loanSummary->id);
        
        $pqamModel = new Pqam();
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id);
        $whereLoan = $this->_sql->getWhere();
        if ($pqamModel->selectTotal($whereLoan) > 0) {
            $pqamModel->delete($whereLoan);
        }
        
        if (! empty($period)) {
            $this->_resetCatch($period);
        }
    }
    public function approveLoan($store = null, $fmc_id = null, $branch_id = null, $member= null, $userID = null)
    {
        $transBy = 0;
        $insert = [];
        $loan = $store->loan();
        $now = DateHelper::timestamp();
        $trans = new Transactions();
        $transBy = 0;
        //$update = $modified;
        $notes = "Loan [{$loan->id}, {$loan->loan_amount}] has been approved";
        $this->_saveComment($loan->id, $userID, $notes);
        if($loan->approval_status == 'Pending')
        {
            $update['approval_status'] = 'Approved';
            $update['updated_at'] = DateHelper::timestamp();
            $update['last_updated'] = DateHelper::today();
            $update['approved_on'] = DateHelper::timestamp();
            $this->_model->updateById($update, $loan->id);
            $loanSourceQuery = "select loan_source, code, loan_source_type from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$loan->loan_source_id}";
            $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
            $loan_source_type = $loanSourceType[0]->loan_source_type;
            $loan = $store->loan();
            $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
            $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
            $code = $loanTypeCode[0]->code;
            if($code === 'bulk_loans'){
                $code = $loanTypeCode[0]->code;
                $headData['head_name'] =  $loanTypeCode[0]->loan_type;
                $headData['code'] = "exp_".$code;
                $headData['transType'] = 'Direct Expense';
                 // Payment Entries
                if($loan->transBy > 0){
                    $transBy = $loan->transBy;
                }
                $notes =  "Bulk Loan to ".$member->name;
                $amount = $loan->loan_amount;
                $insert = [
                    'transDate' => $loan->loan_date,
                    'member_id' => $loan->member_id,
                    'amount'  => $amount,
                    'page_no' =>  1,
                    'created_by' => $userID,
                    'particulars' => "Bulk Loan to ".$member->name,
                    'transBy' => $transBy,
                    'created_at' => $now,
                    'updated_at' => $now, 
                    'loan_id' => $loan->id,
                    'type' => 'MF Loan',
                    'loan_source_id' => $loan->loan_source_id
                ];
                $trans->generateVoucher($headData, $insert, $fmc_id);
                $mfHeadData['head_name'] = $loanTypeCode[0]->loan_type;
                $mfHeadData['code'] = $code;           
                $mfHeadData['transType'] = 'Direct Income';
                $particulars = $loanTypeCode[0]->loan_type ." Receipt";
                $insert = [
                    'receipt' => 0,
                    'amount'  => $amount,
                    'transDate' => $loan->loan_date,
                    'member_id' => 0,
                    'remarks' => $particulars,
                    'created_by' => $userID,
                    'transBy' => $transBy,
                    'remarks' => $particulars,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'loan_id' => $loan->id,
                    'non_member' => $loanTypeCode[0]->loan_type,
                ];
                $fmcModel = new Fmc();
                $getFMCIDQuery = "select id, name, code from  " . $fmcModel->getTableQuoted()." where code = '{$member->code}'";
                $getFmcID = $fmcModel->queryRows($getFMCIDQuery);
                if(isset($getFmcID[0]->id)){
                    $loan_fmcid = $getFmcID[0]->id;
                    $trans->generateReceipt($mfHeadData, $insert, $loan_fmcid);
                    $headData['head_name'] =  $loanTypeCode[0]->loan_type;
                    $headData['code'] = "exp_".$code;
                    $headData['transType'] = 'Direct Expense';
                    $trans->getHeadID($headData, $loan_fmcid);
                    $notes = "Bulk Loan transferred to {$getFmcID[0]->name}";
                }
                
                $this->_saveComment($loan->id, $userID, $notes);
            } else {
            if (LoanHelper::SOURCE_TYPE_FMC_LOAN == $loan_source_type) {                
                // Payment Voucher
                $loan = $store->loan();
                $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
                $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
                $code = $loanTypeCode[0]->code;
                $headData['head_name'] =  $loanTypeCode[0]->loan_type;
                $headData['code'] = $code;
                $headData['transType'] = 'Direct Expense';
                 // Payment Entries
                if($loan->transBy > 0){
                    $transBy = $loan->transBy;
                }
                $amount = $loan->loan_amount;
                $insert = [
                    'transDate' => $loan->loan_date,
                    'member_id' => $loan->member_id,
                    'amount'  => $amount,
                    'page_no' =>  1,
                    'created_by' => $userID,
                    'transBy' => $transBy,
                    'created_at' => $now,
                    'updated_at' => $now, 
                    'loan_id' => $loan->id,
                    'type' => 'MF Loan',
                    'loan_source_id' => $loan->loan_source_id
                ];
                $trans->generateVoucher($headData, $insert, $fmc_id);
            } else {
                    //SOURCE_TYPE_OTHER - MF Loans
                if (LoanHelper::SOURCE_TYPE_OTHER == $loan_source_type) {
                    $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                   //Payment Voucher Generation
                    $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$loan->loan_source_id}";
                    $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                    $loan_source = $loanSourceType[0]->loan_source;
                    $headData['head_name'] =  $loan_source;
                    $headData['code'] = $loanSourceType[0]->code."_loan";
                    $headData['transType'] = 'Direct Expense';
                    if($loan->transBy > 0){
                        $transBy = $loan->transBy;
                    }
                    $particulars = $loan_source ." Loan Payment";
                    $insert = [
                        'transDate' => $loan->loan_date,
                        'member_id' => $loan->member_id,
                        'amount'  => $loan->loan_amount,
                        'particulars' => $particulars,
                        'page_no' =>  1,
                        'created_by' => $userID,
                        'transBy' => $transBy,
                        'created_at' => $now,
                        'updated_at' => $now, 
                        'loan_id' => $loan->id,
                        'loan_source_id' => $loan->loan_source_id
                    ];
                    $trans->generateVoucher($headData, $insert, $fmc_id);
                    //FMC Account
                    if($branch_id) {
                        $mfHeadData['head_name'] =  $loan_source;
                        $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                        $mfHeadData['transType'] = 'Direct Income';
                        $particulars = $loan_source ." Loan Receipt";
                        $fmc_id = $branch_id;
                        if($loan->transBy > 0){                
                            $insert = [
                                'transDate' => $loan->loan_date,                   
                                'amount'  => $loan->loan_amount,
                                'member_id' => $loan->member_id,
                                'particulars' => $particulars,
                                'page_no' =>  1,
                                'created_by' => $userID,
                                'transBy' => $loan->transBy,
                                'created_at' => $now,
                                'updated_at' => $now, 
                                'loan_id' => $loan->id,
                            ];
                            $trans->generateCashReceiptForFMC($mfHeadData, $insert, $fmc_id);
                        }
                             //Payment Voucher Generation
                        $headData['head_name'] =  $loan_source;
                        $headData['code'] = $loanSourceType[0]->code."_loan";
                        $headData['transType'] = 'Direct Expense';
                         // Payment Entries
                        if($loan->transBy > 0){
                            $transBy = $loan->transBy;
                        }
                        $particulars = $loan_source ." Loan Payment";
                        $insert = [
                            'transDate' => $loan->loan_date,
                            'member_id' => $loan->member_id,
                            'amount'  => $loan->loan_amount,
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $userID,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $loan->id,
                            'loan_source_id' => $loan->loan_source_id
                        ];
                        $trans->generateVoucher($headData, $insert, $fmc_id);
                        }
                } else {
             
                    //Bank Accounts
                        $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                        $loan = $store->loan();
                       //Receipt Creation
                        $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$loan->loan_source_id}";
                        $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                        $loan_source = $loanSourceType[0]->loan_source;
                        $mfHeadData['head_name'] =  $loan_source. " Loan";
                        $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                        $mfHeadData['transType'] = 'Direct Income';
                        $particulars = $loan_source ." Loan Receipt";
                        if($loan->transBy > 0){                
                            $rpheads = $this->_headModel->checkByColumnByFmc('id', $loan->transBy, $fmc_id);
                            if(isset($rpheads->id)){
                                $mfHeadData['head_name'] =  $rpheads->head_name ." Loan";
                                $mfHeadData['code'] = str_replace(" ", "_", $mfHeadData['head_name']);                
                            }
                            $mfHeadData['transType'] = 'Direct Income';
                            $insert = [
                                'transDate' => $loan->loan_date,
                                'member_id' => $loan->member_id,
                                'amount'  => $loan->loan_amount,
                                'particulars' => $particulars,
                                'page_no' =>  1,
                                'created_by' => ($userID) ?? 9999,
                                'transBy' => $loan->transBy,
                                'created_at' => $now,
                                'updated_at' => $now, 
                                'loan_id' => $loan->id,
                            ];
                            $trans->generateCashReceipt($mfHeadData, $insert, $fmc_id);
                        }
                         //Payment Voucher Generation
                       /* $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
                        $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
                        $code = $loanTypeCode[0]->code;
                        $headData['head_name'] =  $loanTypeCode[0]->loan_type. " Loan";
                        $headData['code'] = $code."_loan";*/
                        $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$loan->loan_source_id}";
                        $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                        $loan_source = $loanSourceType[0]->loan_source;
                        $headData['head_name'] =  $loan_source. " Loan";
                        $headData['code'] = $loanSourceType[0]->code."_loan";
                        $headData['transType'] = 'Direct Expense';
                        if($loan->transBy > 0){
                            $transBy = $loan->transBy;
                        }
                        $particulars = $loan_source ." Loan Payment";
                        $insert = [
                            'transDate' => $loan->loan_date,
                            'member_id' => $loan->member_id,
                            'amount'  => $loan->loan_amount,
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $userID,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $loan->id,
                            'loan_source_id' => $loan->loan_source_id
                        ];
                        $trans->generateVoucher($headData, $insert, $fmc_id);
                        //FMC Account
                        if($branch_id) {
                            $mfHeadData['head_name'] =  $loan_source. " Loan";
                            $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                            $mfHeadData['transType'] = 'Direct Income';
                            $particulars = $loan_source ." Loan Receipt";
                            $fmc_id = $branch_id;
                            if($loan->transBy > 0){                
                                $insert = [
                                    'transDate' => $loan->loan_date,                   
                                    'amount'  => $loan->loan_amount,
                                    'member_id' => $loan->member_id,
                                    'particulars' => $particulars,
                                    'page_no' =>  1,
                                    'created_by' => $userID,
                                    'transBy' => $loan->transBy,
                                    'created_at' => $now,
                                    'updated_at' => $now, 
                                    'loan_id' => $loan->id,
                                ];
                                $trans->generateCashReceiptForFMC($mfHeadData, $insert, $fmc_id);
                            }
                             //Payment Voucher Generation
                         $headData['head_name'] =  $loan_source. " Loan";
                         $headData['code'] = $loanSourceType[0]->code."_loan";
                         $headData['transType'] = 'Direct Expense';
                         // Payment Entries
                        if($loan->transBy > 0){
                            $transBy = $loan->transBy;
                        }
                        $particulars = $loan_source ." Loan";
                        $insert = [
                            'transDate' => $loan->loan_date,
                            'member_id' => $loan->member_id,
                            'amount'  => $loan->loan_amount,
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $userID,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $loan->id,
                            'loan_source_id' => $loan->loan_source_id
                        ];
                        $trans->generateVoucher($headData, $insert, $fmc_id);
                        }
                    }
                }
            }
        }
            return true;       
    }
    /**
     * delete loan
     * 
     * @return null
     */
    public function deleteLoan()
    {
        if (! $this->_hasDcb) {
            $this->_model->deleteById($this->_id);
            
            return;
        }
        
        $period = $this->catchPeriod();
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_scheduleIds)
                   ->whereOne('has_dcb_entry');
        
        $commentModel = new LoanComment();
        $commentModel->delete($this->_whereLoan);
        $timelineModel = new LoanTimeline();
        $timelineModel->delete($this->_whereLoan);
        $this->_model->deleteById($this->_id);
        
        if (! empty($period)) {
            $this->_resetCatch($period);
        }
    }
    
    /**
     * get timeline
     * 
     * @return boolean|array
     */
    public function timeline()
    {
        $timelineModel = new LoanTimeline();
        if ($timelineModel->selectTotal($this->_whereLoan) > 0) {
            return $timelineModel->select($this->_whereLoan, 'id DESC');
        }
        
        return false;
    }
    
    /**
     * get notes
     * 
     * @return boolean|array
     */
    public function notes()
    {
        $commentModel = new LoanComment();
        if ($commentModel->selectTotal($this->_whereLoan) > 0) {
            return $commentModel->select($this->_whereLoan, 'id DESC');
        }
        
        return false;
    }
    
    /**
     * get dcb demand for current entry
     * 
     * @return array
     */
    public function dcbDemandOld()
    {
        if (! $this->_dataEntry) {
            return;
        }
        
        $demand = [];
        $demand[DcbColumn::EMI] = $this->_entry->emi;
        $demand[DcbColumn::INTEREST] = self::ZERO_STRING;
        $demand[DcbColumn::PRINCIPAL] = self::ZERO_STRING;
        $demand[DcbColumn::PENAL_INTEREST_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::INTEREST_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::TOTAL_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = self::ZERO_STRING;
        $demand[DcbColumn::SCHEDULED_PRINCIPAL_OUTSTANDING] = $this->_entry->opening_principal;
        
        if (1 == $this->_entry->first_instalment || empty($this->_previous)) {
            $demand[DcbColumn::LOAN_OUTSTANDING] = $this->_entry->opening_principal;
            $demand[DcbColumn::INTEREST] = $this->_entry->interest_component;
            $demand[DcbColumn::PRINCIPAL] = $this->_entry->principal_component;
            $demand[DcbColumn::PRINCIPAL_OUTSTANDING] = LoanHelper::amount($demand[DcbColumn::LOAN_OUTSTANDING] - $this->_entry->principal_component);
            $demand[DcbColumn::TOTAL_DEMAND] = $demand[DcbColumn::INTEREST] + $demand[DcbColumn::PRINCIPAL];
            $demand[30] = $demand[DcbColumn::LOAN_OUTSTANDING] + $demand[DcbColumn::INTEREST];
            $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = $demand[DcbColumn::LOAN_OUTSTANDING];
            $today = DateHelper::today();
            if ($today == $this->_entry->instalment_date) {
                $demand[31] = $demand[30];
            } else {
                $principalOutstanding = $demand[DcbColumn::LOAN_OUTSTANDING] + $demand[DcbColumn::PRINCIPAL];
                $demand[31] = $principalOutstanding;
                $difference = DateHelper::getDateTime($this->_entry->instalment_date)->diff(DateHelper::getDateTime($today));
                $days = $difference->days;
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC && $demand[DcbColumn::TOTAL_ARREARS_DEMAND] > self::ZERO_NUMERIC) {
                    $dailyPenalRate2 = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $today);
                    $demand[31] += LoanHelper::amount($demand[DcbColumn::TOTAL_ARREARS_DEMAND] * $dailyPenalRate2 * $days);
                }
                
                if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
                    $dailyRate3 = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $today);
                    $demand[31] += LoanHelper::amount($principalOutstanding * $dailyRate3 * $days);
                }
            }
        } else {
            $demand[DcbColumn::SCHEDULED_PRINCIPAL_OUTSTANDING] = $this->_previous->balance_actual_principal_outstanding;
            $demand[DcbColumn::LOAN_OUTSTANDING] = $this->_previous->balance_actual_principal_outstanding;
            if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
                $dailyRate = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $this->_entry->instalment_date);
                $demand[DcbColumn::INTEREST] = LoanHelper::amount($demand[DcbColumn::LOAN_OUTSTANDING] * $dailyRate * $this->_entry->emi_days);
                if ($this->_entry->emi > $demand[DcbColumn::INTEREST]) {
                    $demand[DcbColumn::PRINCIPAL] = $this->_entry->emi - $demand[DcbColumn::INTEREST];
                } else {
                    $demand[DcbColumn::PRINCIPAL] = self::ZERO_STRING;
                }
            } else {
                $demand[DcbColumn::PRINCIPAL] = $this->_entry->emi;
            }
            
            if ($demand[DcbColumn::LOAN_OUTSTANDING] > $demand[DcbColumn::PRINCIPAL]) {
                $demand[DcbColumn::PRINCIPAL_OUTSTANDING] = LoanHelper::amount($demand[DcbColumn::LOAN_OUTSTANDING] - $demand[DcbColumn::PRINCIPAL]);
            } else {
                $demand[DcbColumn::PRINCIPAL_OUTSTANDING] = self::ZERO_NUMERIC;
            }
            if ($this->_previous->balance_total_overdue > self::ZERO_NUMERIC) {
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC) {
                    $dailyPenalRate = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $this->_entry->instalment_date);
                    $demand[DcbColumn::PENAL_INTEREST_DEMAND] = LoanHelper::amount($this->_previous->balance_total_overdue * $dailyPenalRate * $this->_entry->emi_days);
                }
                
                if ($this->_previous->balance_interest_overdue > self::ZERO_NUMERIC) {
                    $demand[DcbColumn::INTEREST_ARREARS_DEMAND] = $this->_previous->balance_interest_overdue;
                }
                if ($this->_previous->balance_principal_overdue > self::ZERO_NUMERIC) {
                    $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND] = $this->_previous->balance_principal_overdue;
                }
            }
            
            $demand[DcbColumn::TOTAL_ARREARS_DEMAND] = LoanHelper::sum([$demand[DcbColumn::PENAL_INTEREST_DEMAND], $demand[DcbColumn::INTEREST_ARREARS_DEMAND], $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND]]);
            $demand[DcbColumn::TOTAL_DEMAND] = LoanHelper::sum([$this->_entry->emi, $demand[DcbColumn::TOTAL_ARREARS_DEMAND]]);
            $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = $this->_previous->balance_actual_principal_interest_outstanding;
            
            $demand[30] = $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] 
                        + $demand[DcbColumn::PENAL_INTEREST_DEMAND]
                        + $demand[DcbColumn::INTEREST];
            
            $today = DateHelper::today();
            if ($today == $this->_entry->instalment_date) {
                $demand[31] = $demand[30];
            } else {
                $principalOutstanding = $demand[DcbColumn::LOAN_OUTSTANDING] + $demand[DcbColumn::PRINCIPAL];
                $demand[31] = $principalOutstanding;
                $difference = DateHelper::getDateTime($this->_entry->instalment_date)->diff(DateHelper::getDateTime($today));
                $days = $difference->days;
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC && $demand[DcbColumn::TOTAL_ARREARS_DEMAND] > self::ZERO_NUMERIC) {
                    $dailyPenalRate2 = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $today);
                    $demand[31] += LoanHelper::amount($demand[DcbColumn::TOTAL_ARREARS_DEMAND] * $dailyPenalRate2 * $days);
                }
                
                if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
                    $dailyRate3 = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $today);
                    $demand[31] += LoanHelper::amount($principalOutstanding * $dailyRate3 * $days);
                }
            }
        }
        
        return $demand;
    }
    
    /**
     * get projected demand
     * 
     * @param float $amount
     * @param float $interestRate
     * @param string $date
     * @return int
     */
    public function projectedDemand($amount, $interestRate, $date)
    {
        $last = $this->_schedule[$this->_summary->schedule_id];
        $startDate = $last->instalment_date;
        if ($date == $startDate || $interestRate < 0.01) {
            return intval($amount);
        }

        $from = new \DateTime($last->instalment_date);
        $end = new \DateTime($date);
        $diff = $from->diff($end);
        $days = $diff->days;
        if ($days < 1) {
            return intval($amount);
        }

        $dailyRate = LoanHelper::dailyInterestRateByDate($interestRate, $date);
        $interestAmount = $amount * $dailyRate * $days;
        
        return intval($amount + $interestAmount);
    }
    
    /**
     * clear pqam
     * 
     * @param string $date
     */
    public function clearPqam($date)
    {
        $pqamModel = new Pqam();
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id)
                   ->whereGreater('pqam_date', $date);
        $where = $this->_sql->getWhere();
        if ($pqamModel->selectTotal($where) > 0) {
            $pqamModel->delete($where);
        }
    }
    
    /**
     * get closing demand
     * 
     * @return boolean
     */
    public function closingDemand()
    {
        if ($this->closed()) {
            return;
        }
        
        if (empty($this->_previous)) {
            return false;
        }
        
        $demand = [
            'outstanding_date' => $this->_previous->instalment_date,
            'max_date' => DateHelper::today(),
            'outstanding' => $this->_previous->balance_actual_principal_interest_outstanding,
            'overdue' => 0,
            'interest' => 0,
            'penal_interest' => 0,
            'opening_pricipal' => $this->_previous->balance_actual_principal_outstanding,
        ];
        
        if ($this->_previous->balance_total_overdue > self::ZERO_NUMERIC) {
            $demand['overdue'] = $this->_previous->balance_total_overdue;
        }
        
        $demand['total'] = $demand['outstanding'];
        
        $date = DateHelper::getDateTime($this->_previous->instalment_date);
        $date->modify('+1 day');
        $demand['min_date'] = $date->format(DateHelper::MYSQL_DATE);
        
        if ($this->_dataEntry) {
            $demand['max_date'] = $this->_entry->instalment_date;
        }
        
        $demand['closing_date'] = $demand['max_date'];
        
        foreach ($this->_schedule as $item) {
            if (0 == $item->has_dcb_entry) {
                $demand['payment_date'] = $item->instalment_date;
                break;
            }
        }
        
        $difference = DateHelper::getDateTime($demand['outstanding_date'])->diff(DateHelper::getDateTime($demand['closing_date']));
        $days = $difference->days;
        if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC && $demand['overdue'] > self::ZERO_NUMERIC) {
            $dailyPenalRate = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $demand['closing_date']);
            $demand['penal_interest'] = LoanHelper::amount($demand['overdue'] * $dailyPenalRate * $days);
            $demand['total'] += $demand['penal_interest'];
        }

        if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
            $dailyRate = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $demand['closing_date']);
            $demand['interest'] = LoanHelper::amount($demand['outstanding'] * $dailyRate * $days);
            $demand['total'] += $demand['interest'];
        }
        
        return $demand;
    }
    
    /**
     * check inactive status
     * 
     * @return boolean
     */
    public function inactive()
    {
        if ($this->_loan->closed > 0 && LoanHelper::STATUS_INACTIVE == $this->_loan->loan_status_id) {
            $inactiveModel = new Inactive();
            if ($inactiveModel->checkByColumnId('loan_id', $this->_id)) {
                return $inactiveModel->selectByIdColumn('loan_id', $this->_id);
            }
        }
        
        return null;
    }
    
    /**
     * check dormant status
     * 
     * @return boolean
     */
    public function dormant()
    {
        if ($this->_loan->closed > 0) {
            return false;
        }
        
        if (LoanHelper::STATUS_INACTIVE == $this->_loan->loan_status_id) {
            return false;
        }
        
        if (! $this->_hasDcb) {
            return false;
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id)
                   ->whereOne('has_dcb_entry')
                   ->whereEqual('collection_repaid', self::ZERO_STRING);
        $repaid = $this->_scheduleModel->selectTotal($this->_sql->getWhere());
        if (0 == $repaid) {
            return false;
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id)
                   ->whereOne('has_dcb_entry')
                   ->whereGreater('collection_repaid', self::ZERO_STRING);
        $where = $this->_sql->getWhere();
        if (0 == $this->_scheduleModel->selectTotal($where)) {
            return $repaid >= LoanHelper::INACTIVE_NON_REPAYMENT;
        }
        
        $last = $this->_scheduleModel->selectRow($where, 'instalment DESC');
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id)
                   ->whereOne('has_dcb_entry')
                   ->whereEqual('collection_repaid', self::ZERO_STRING)
                   ->whereGreater('instalment', $last->instalment);
        
        return $this->_scheduleModel->selectTotal($this->_sql->getWhere()) >= LoanHelper::INACTIVE_NON_REPAYMENT;
    }
    
    /**
     * get inactive options
     * 
     * @return array
     */
    public function inactiveOptions()
    {
        $options = [
            'completed' => $this->_loanSummary->paid_tenures,
            'paid' => 0,
            'unpaid' => 0,
            'successive' => 0,
            'last_payment' => null,
            'instalments' => [],
        ];
        
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_id)
                   ->whereOne('has_dcb_entry')
                   ->whereGreater('collection_repaid', self::ZERO_STRING);
        $options['paid'] = $this->_scheduleModel->selectTotal($this->_sql->getWhere());
        $options['unpaid'] = $options['completed'] - $options['paid'];
        if ($options['paid'] > 0) {
            $options['last_payment'] = $this->_scheduleModel->selectRow($this->_sql->getWhere(), 'instalment DESC');
            $this->_sql->clear();
            $this->_sql->whereId('loan_id', $this->_id)
                       ->whereOne('has_dcb_entry')
                       ->whereGreater('instalment', $options['last_payment']->instalment);
            $options['successive'] = $this->_scheduleModel->selectTotal($this->_sql->getWhere());
            $this->_sql->clear();
            $this->_sql->whereId('loan_id', $this->_id)
                       ->whereOne('has_dcb_entry')
                       ->whereGreater('instalment', $options['last_payment']->instalment, true);
            $list = $this->_scheduleModel->select($this->_sql->getWhere(), 'instalment');
        } else {
            $options['successive'] = $options['completed'];
            $scheduleIds = array_keys($this->_schedule);
            $index = max(0, LoanHelper::INACTIVE_NON_REPAYMENT - 1);
            $last = $this->_schedule[$scheduleIds[$index]];
            $this->_sql->clear();
            $this->_sql->whereId('loan_id', $this->_id)
                       ->whereOne('has_dcb_entry')
                       ->whereGreater('instalment', $last->instalment, true);
            $list = $this->_scheduleModel->select($this->_sql->getWhere(), 'instalment');
        }
        
        foreach ($list as $item) {
            $options['instalments'][$item->id] = $item->instalment . ', ' . DateHelper::datepicker($item->instalment_date);
        }
        
        return $options;
    }
    
    /**
     * get dcb demand for current entry
     * 
     * @return array
     */
    public function dcbDemand()
    {
        if (! $this->_dataEntry) {
            return;
        }
        
        $demand = [];
        $demand[DcbColumn::EMI] = $this->_entry->emi;
        $demand[DcbColumn::INTEREST] = $this->_entry->interest_component;
        $demand[DcbColumn::PRINCIPAL] = $this->_entry->principal_component;
        $demand[DcbColumn::PENAL_INTEREST_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::INTEREST_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::TOTAL_ARREARS_DEMAND] = self::ZERO_STRING;
        $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = self::ZERO_STRING;
        $demand[DcbColumn::SCHEDULED_PRINCIPAL_OUTSTANDING] = $this->_entry->opening_principal;
        $demand[DcbColumn::LOAN_OUTSTANDING] = $this->_entry->opening_principal;
        $demand[DcbColumn::PRINCIPAL_OUTSTANDING] = $this->_entry->closing_principal;
        $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = $demand[DcbColumn::LOAN_OUTSTANDING];
        
        if (1 == $this->_entry->first_instalment || empty($this->_previous)) {
            $demand[DcbColumn::TOTAL_DEMAND] = $demand[DcbColumn::INTEREST] + $demand[DcbColumn::PRINCIPAL];
            $demand[30] = $demand[DcbColumn::LOAN_OUTSTANDING] + $demand[DcbColumn::INTEREST];
            $today = DateHelper::today();
            if ($today == $this->_entry->instalment_date) {
                $demand[31] = $demand[30];
            } else {
                $principalOutstanding = $demand[DcbColumn::LOAN_OUTSTANDING] + $demand[DcbColumn::PRINCIPAL];
                $demand[31] = $principalOutstanding;
                $difference = DateHelper::getDateTime($this->_entry->instalment_date)->diff(DateHelper::getDateTime($today));
                $days = $difference->days;
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC) {
                    $dailyPenalRate2 = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $today);
                    $demand[31] += LoanHelper::amount($demand[DcbColumn::TOTAL_ARREARS_DEMAND] * $dailyPenalRate2 * $days);
                }
                
                if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
                    $dailyRate3 = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $today);
                    $demand[31] += LoanHelper::amount($principalOutstanding * $dailyRate3 * $days);
                }
            }
        } else {
            if ($this->_previous->balance_total_overdue != self::ZERO_NUMERIC) {
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC) {
                    $dailyPenalRate = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $this->_entry->instalment_date);
                    $demand[DcbColumn::PENAL_INTEREST_DEMAND] = LoanHelper::amount($this->_previous->balance_total_overdue * $dailyPenalRate * $this->_entry->emi_days);
                }
                
                if ($this->_previous->balance_interest_overdue != self::ZERO_NUMERIC) {
                    $demand[DcbColumn::INTEREST_ARREARS_DEMAND] = $this->_previous->balance_interest_overdue;
                }
                if ($this->_previous->balance_principal_overdue != self::ZERO_NUMERIC) {
                    $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND] = $this->_previous->balance_principal_overdue;
                }
            }
            
            $demand[DcbColumn::TOTAL_ARREARS_DEMAND] = LoanHelper::sum([$demand[DcbColumn::PENAL_INTEREST_DEMAND], $demand[DcbColumn::INTEREST_ARREARS_DEMAND], $demand[DcbColumn::PRINCIPAL_ARREARS_DEMAND]]);
            $demand[DcbColumn::TOTAL_DEMAND] = LoanHelper::sum([$this->_entry->emi, $demand[DcbColumn::TOTAL_ARREARS_DEMAND]]);
            $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] = $this->_previous->balance_actual_principal_interest_outstanding;
            
            $demand[30] = $demand[DcbColumn::ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING] 
                        + $demand[DcbColumn::INTEREST];
            if ($demand[DcbColumn::PENAL_INTEREST_DEMAND] > self::ZERO_NUMERIC) {
                $demand[30] += $demand[DcbColumn::PENAL_INTEREST_DEMAND];
            }
            
            $today = DateHelper::today();
            if ($today == $this->_entry->instalment_date) {
                $demand[31] = $demand[30];
            } else {
                $principalOutstanding = $this->_previous->balance_actual_principal_outstanding;
                $demand[31] = $principalOutstanding;
                $difference = DateHelper::getDateTime($this->_entry->instalment_date)->diff(DateHelper::getDateTime($today));
                $days = $difference->days;
                if ($this->_loan->penal_interest_rate > self::ZERO_NUMERIC && $this->_previous->balance_total_overdue > self::ZERO_NUMERIC) {
                    $dailyPenalRate2 = LoanHelper::dailyInterestRateByDate($this->_loan->penal_interest_rate, $today);
                    $demand[31] += LoanHelper::amount($this->_previous->balance_total_overdue * $dailyPenalRate2 * $days);
                }
                
                if ($this->_loan->interest_rate > self::ZERO_NUMERIC) {
                    $dailyRate3 = LoanHelper::dailyInterestRateByDate($this->_loan->interest_rate, $today);
                    $demand[31] += LoanHelper::amount($principalOutstanding * $dailyRate3 * $days);
                }
            }
        }
        
        return $demand;
    }
    
    /**
     * get loan repayment period total catch
     * 
     * @return int
     */
    public function loanCatch()
    {
        $catchLogger = new MemberCatchLogger();
        
        return $catchLogger->loanCatch($this->_id);
    }
    
    /**
     * get schedule wise catch
     * 
     * @return array
     */
    public function scheduleCatch()
    {
        $catchLogger = new MemberCatchLogger();
        
        return $catchLogger->scheduleCatch($this->_id);
    }
    
    /**
     * reset catch
     * 
     * @param array $period
     */
    private function _resetCatch($period)
    {
        $catchLogger = new MemberCatchLogger($this->_member->id);
        $catchLogger->resetPeriod($period);
    }
    
     private function _saveComment($loanId, $administratorId, $notes = null)
    {
        $logger = new CommentLogger($administratorId);
        $logger->id($loanId)
               ->save($notes);
    }

}    
