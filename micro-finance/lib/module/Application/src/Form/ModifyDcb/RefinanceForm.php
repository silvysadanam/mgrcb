<?php
/**
 * Application ModifyDcb Refinance Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Constant\LoanConfig;
use Application\Utility\{EmiCalculator, LoanHelper, LoanStore};
use Application\Helper\{CommentLogger, TimelineLogger};
use Model\{Schedule, LoanReschedule, LoanSummary, LoanSource, RPTransactionDetails, Member, LoanType, RPTransMember, RPHeads, RPTransMemberDetails};
use Shared\Utility\Transactions;
use Web\Form\Form;
use Web\Utility\DateHelper;


class RefinanceForm extends Form
{    
    /**
     * 
     * @var int
     */
    private $_loanId;
    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var int
     */
    private $_administaratorId;
    private $_loanTypeModel;
    private $_headModel;
    /**
     * 
     * @var \Application\Utility\LoanStore
     */
    private $_store;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'loan_date',
            'overdue_interest_rate',
            'overdue_days',
            'overdue_amount',
            'refinance_amount',
            'loan_amount',
            'loan_source_id',
            'loan_type_id',
            'interest_rate',
            'penal_interest_rate',
            'tenure',
            'emi',
            'note1',
            'note2',
            'account_number',
            'payment_type',
            'bank_id',
        ];
        $this->addFields($fields);
        $this->_loanTypeModel = new LoanType();
        $this->_loanSourceModel = new LoanSource();
        $this->_headModel = new RPHeads();
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administaratorId = $id;
    }
    
    /**
     * set store
     * 
     * @param \Application\Utility\LoanStore $store
     */
    public function setStore($store)
    {
        $this->_store = $store;
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        
        if ($this->validator->isNull($this->_formValues['loan_date'])) {
            $this->error->required('Date');
        }
        
        $this->_validateAmount();
        $this->_validateInterestRate($this->_formValues['interest_rate'], 'Rate of Interest');
        $this->_validateInterestRate($this->_formValues['penal_interest_rate'], 'Penal Interest Rate');
        $this->_validateTenure($this->_formValues['tenure']);
        $this->_validateEmi($this->_formValues['emi']);
        if ($this->noError()) {
            $this->_checkEmiLimits();
        }
        
        return $this->noError();
    }
    
    /**
     * create loan
     * 
     * @return int
     */
    public function create()
    {
        $loan = $this->_store->loan();
        $now = DateHelper::timestamp();
        $transBy = 0;
        $exactEmi = LoanHelper::emi($this->_formValues['loan_amount'], LoanHelper::interestRate($this->_formValues['interest_rate']), $this->_formValues['tenure'], false);
        $insert = [
            'member_id' => $loan->member_id,
            'loan_date' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'loan_amount' => $this->_toDecimal($this->_formValues['loan_amount']),
            'loan_type_id' => $this->_formValues['loan_type_id'],
            'loan_source_id' => $this->_formValues['loan_source_id'],
            'tenure' => $this->_formValues['tenure'],
            'interest_rate' => $this->_toDecimal($this->_formValues['interest_rate']),
            'penal_interest_rate' => $this->_toDecimal($this->_formValues['penal_interest_rate']),
            'emi' => $this->_formValues['emi'],
            'calculated_emi' => intval($exactEmi),
            'exact_emi' => $exactEmi,
            'created_at' => $now,
            'updated_at' => $now,
            'last_updated' => DateHelper::today(),
            'account_number' => $this->_formValues['account_number'],
            'rescheduled_or_refinanced' => 1,
        ];       
                
        $this->_loanId = $this->save($insert);
        //Payment Voucher
        $memberModel = new Member();
        $member = $memberModel->find($loan->member_id);
        $loanSourceQuery = "select loan_source, code, loan_source_type from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$this->_formValues['loan_source_id']}";
        $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
        $loan_source_type = $loanSourceType[0]->loan_source_type;
        $trans = new Transactions();
        if (LoanHelper::SOURCE_TYPE_FMC_LOAN ==  $loan_source_type) {
            $fmc_id = $member->fmc_id;            
        }else {
            $fmc_id = LoanHelper::MF_ACCOUNT_ID;
            $branch_id = $member->fmc_id;
        }
         if (LoanHelper::SOURCE_TYPE_FMC_LOAN == $loan_source_type) {                
            // Payment Voucher
            $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$this->_formValues['loan_type_id']}";
            $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
            $code = $loanTypeCode[0]->code;
            $headData['head_name'] =  $loanTypeCode[0]->loan_type;
            $headData['code'] = $code;
            $headData['transType'] = 'Direct Expense';
             // Payment Entries
            if($this->_formValues['payment_type'] == 'Bank'){
                $transBy =  $this->_formValues['bank_id'];
            }
            $voucherInsert = [
                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                'member_id' => $loan->member_id,
                'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                'transBy' => $transBy,
                'page_no' =>  1,
                'created_by' => $this->_administaratorId,
                'created_at' => $now,
                'type' => 'MF Loan',
                'updated_at' => $now, 
                'loan_id' => $this->_loanId,
            ];
                $trans->generateVoucher($headData, $voucherInsert, $fmc_id);
            } else {
                    //SOURCE_TYPE_OTHER - MF Loans
                if (LoanHelper::SOURCE_TYPE_OTHER == $loan_source_type) {
                    $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                   //Payment Voucher Generation
                    $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$this->_formValues['loan_source_id']}";
                    $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                    $loan_source = $loanSourceType[0]->loan_source;
                    $headData['head_name'] =  $loan_source;
                    $headData['code'] = $loanSourceType[0]->code."_loan";
                    $headData['transType'] = 'Direct Expense';
                    if($this->_formValues['payment_type'] == 'Bank'){
                        $transBy =  $this->_formValues['bank_id'];
                    }
                    $particulars = $loan_source ." Loan Payment";
                    $loan_insert = [
                        'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                        'member_id' => $loan->member_id,
                        'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                        'particulars' => $particulars,
                        'page_no' =>  1,
                        'created_by' => $this->_administaratorId,
                        'transBy' => $transBy,
                        'created_at' => $now,
                        'updated_at' => $now, 
                        'loan_id' => $this->_loanId,
                        'loan_source_id' => $this->_formValues['loan_source_id']
                    ];
                    $trans->generateVoucher($headData, $loan_insert, $fmc_id);
                    //FMC Account
                    if($branch_id) {                        
                        $mfHeadData['head_name'] =  $loan_source;
                        $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                        $mfHeadData['transType'] = 'Direct Income';
                        $particulars = $loan_source ." Loan Receipt";
                        $fmc_id = $branch_id;
                        if($transBy > 0){                   
                            $loan_insert = [
                                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),                   
                                'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                                'member_id' => $loan->member_id,
                                'particulars' => $particulars,
                                'page_no' =>  1,
                                'created_by' => $this->_administaratorId,
                                'transBy' => $transBy,
                                'created_at' => $now,
                                'updated_at' => $now, 
                                'loan_id' => $this->_loanId,
                            ];
                            $trans->generateCashReceipt($mfHeadData, $loan_insert, $fmc_id);
                        }
                        //Payment Voucher Generation
                        $headData['head_name'] =  $loan_source;
                        $headData['code'] = $loanSourceType[0]->code."_loan";
                        $headData['transType'] = 'Direct Expense';
                         // Payment Entries                       
                        $particulars = $loan_source ." Loan Payment";
                        $loan_insert = [
                            'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                            'member_id' => $loan->member_id,
                            'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $this->_administaratorId,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $this->_loanId,
                            'loan_source_id' => $this->_formValues['loan_source_id']
                        ];
                        $trans->generateVoucher($headData, $loan_insert, $fmc_id);
                        }
                } else {
                    //Bank Account
                        $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                        if($this->_formValues['payment_type'] == 'Bank'){
                            $transBy =  $this->_formValues['bank_id'];
                        }
                        //Receipt Creation
                        $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$this->_formValues['loan_source_id']}";
                        $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                        $loan_source = $loanSourceType[0]->loan_source;
                        $mfHeadData['head_name'] =  $loan_source. " Loan";
                        $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                        $mfHeadData['transType'] = 'Direct Income';
                        $particulars = $loan_source ." Loan Receipt";
                        if($transBy > 0){                
                            $rpheads = $this->_headModel->checkByColumnByFmc('id', $transBy, $fmc_id);
                            if(isset($rpheads->id)){
                                $mfHeadData['head_name'] =  $rpheads->head_name ." Loan";
                                $mfHeadData['code'] = str_replace(" ", "_", $mfHeadData['head_name']);                
                            }
                            $mfHeadData['transType'] = 'Direct Income';
                            $loan_insert = [
                                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                                'member_id' => $loan->member_id,
                                'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                                'particulars' => $particulars,
                                'page_no' =>  1,
                                'created_by' => $this->_administaratorId,
                                'transBy' => $transBy,
                                'created_at' => $now,
                                'updated_at' => $now, 
                                'loan_id' => $this->_loanId,
                            ];
                            $trans->generateCashReceipt($mfHeadData, $loan_insert, $fmc_id);
                        }
                        
                         //Payment Voucher Generation
                        //$loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$this->_formValues['loan_source_id']}";
                        //$loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                        //$loan_source = $loanSourceType[0]->loan_source;
                        $headData['head_name'] =  $loan_source. " Loan";
                        $headData['code'] = $loanSourceType[0]->code."_loan";
                        $headData['transType'] = 'Direct Expense';
                        $particulars = $loan_source ." Loan Payment";
                        $loan_insert = [
                            'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                            'member_id' => $loan->member_id,
                            'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $this->_administaratorId,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $this->_loanId,
                            'loan_source_id' => $this->_formValues['loan_source_id']
                        ];
                        $trans->generateVoucher($headData, $loan_insert, $fmc_id);
                        //FMC Account
                        if($branch_id) {
                        $mfHeadData['head_name'] =  $loan_source. " Loan";
                        $mfHeadData['code'] = $loanSourceType[0]->code."_loan";
                        $mfHeadData['transType'] = 'Direct Income';
                        $particulars = $loan_source ." Loan Receipt";
                        $fmc_id = $branch_id;
                        if($transBy > 0){                
                            $rpheads = $this->_headModel->checkByColumnByFmc('id', $transBy, $fmc_id);
                            if(isset($rpheads->id)){
                                $mfHeadData['head_name'] =  $rpheads->head_name ." Loan";
                                $mfHeadData['code'] = str_replace(" ", "_", $mfHeadData['head_name']);                
                            }
                            $mfHeadData['transType'] = 'Direct Income';
                            $loan_insert = [
                                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                                'member_id' => $loan->member_id,
                                'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                                'particulars' => $particulars,
                                'page_no' =>  1,
                                'created_by' => $this->_administaratorId,
                                'transBy' => $transBy,
                                'created_at' => $now,
                                'updated_at' => $now, 
                                'loan_id' => $this->_loanId,
                            ];
                            $trans->generateCashReceipt($mfHeadData, $loan_insert, $branch_id);
                        }
                         //Payment Voucher Generation
                         $headData['head_name'] =  $loan_source. " Loan";
                         $headData['code'] = $loanSourceType[0]->code."_loan";
                         $headData['transType'] = 'Direct Expense';
                         // Payment Entries
                         if($this->_formValues['payment_type'] == 'Bank'){
                            $transBy =  $this->_formValues['bank_id'];
                        }
                        $particulars = $loan_source ." Loan Payment";
                        $loan_insert = [
                            'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                            'member_id' => $loan->member_id,
                            'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $this->_administaratorId,
                            'transBy' => $transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' =>  $this->_loanId,
                            'loan_source_id' =>  $this->_formValues['loan_source_id']
                        ];
                        $trans->generateVoucher($headData, $loan_insert, $fmc_id);
                        }
                    }
                }
        /*$transBy = 0;
        $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$this->_formValues['loan_type_id']}";
        $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
        $code = $loanTypeCode[0]->code;
        $headData['head_name'] =  $loanTypeCode[0]->loan_type;
        $headData['code'] = $code;
        $headData['transType'] = 'Direct Expense';        
        $type = "MF Loan";
        if($this->_formValues['payment_type'] == 'Bank'){
            $transBy =  $this->_formValues['bank_id'];
        }
        $voucherInsert = [
            'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'member_id' => $loan->member_id,
            'amount'  => $this->_toDecimal($this->_formValues['loan_amount']),
            'transBy' => $transBy,
            'created_by' => $this->_administaratorId,
            'created_at' => $now,
            'type' => $type,
            'updated_at' => $now, 
            'loan_id' => $this->_loanId,
        ];
        $trans = new Transactions();
        $trans->generateVoucher($headData, $voucherInsert, $fmc_id);*/
        $this->_generateSchedule($insert);
        $this->_saveComment($loan->id);
        $this->_closeLoan('loan_reschedule');

        return $this->_loanId;
    }
    
    /**
     * generate schedule
     * 
     * @param array $loan
     */
    private function _generateSchedule($loan)
    {
        $calculator = new EmiCalculator();
        $calculator->amount($loan['loan_amount'])
                   ->interest($loan['interest_rate'])
                   ->loanDate($loan['loan_date'])
                   ->tenure($loan['tenure'])
                   ->emi($loan['emi']);
        $schedule = $calculator->run();
        $update = [
            'start_date' => $schedule['start_date'],
            'end_date' => $schedule['end_date'],
            'emi' => $schedule['emi'],
            'total_interest_amount' => $schedule['total_interest'],
            'total_repayment_demand' => $schedule['total_repayment'],
            'total_emi_amount' => $schedule['total_emi'],
            'total_principal_amount' => $schedule['total_principal'],
            'has_broken_period' => $schedule['has_broken_period'],
        ];

        $this->getModel()->updateById($update, $this->_loanId);
        $this->_saveShedule($schedule['schedule']);
        $this->_saveSummary(count($schedule['schedule']));
    }
    
    /**
     * save schedule
     * 
     * @param array $instalments
     */
    private function _saveShedule($instalments)
    {
        $model = new Schedule();
        $default = [
            'loan_id' => $this->_loanId,
            'instalment' => 0,
            'emi' => '0.00',
            'instalment_date' => '',
            'interest_component' => '',
            'principal_component' => '',
            'opening_principal' => '',
            'closing_principal' => '',
            'emi_days' => 0,
            'cumulative_interest' => '',
            'cumulative_principal' => '',
            'cumulative_emi' => '',
            'first_instalment' => 0,
            'last_instalment' => 0,
            'broken_period' => 0,
            'first_emi' => 0,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $insert = $model->getInsertPreparedStatement(array_keys($default));
        foreach ($instalments as $item) {
            $record = $default;
            foreach ($item as $field => $value) {
                $record[$field] = $value;
            }
            $insert->execute(array_values($record));
        }
    }
    
    private function _saveSummary($instalments)
    {
        $summaryModel = new LoanSummary();
        $summary = [
            'loan_id' => $this->_loanId,
            'instalments' => $instalments,
            'scheduled_demand_loan_outstanding' => $this->_formValues['loan_amount'],
            'as_on_date' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'created_at' => DateHelper::timestamp(),
            'modified_at' => DateHelper::timestamp(),
            'refinanced_loan' => 1,
            'refinanced_from' => $this->_store->id(),
            
        ];
        $summaryModel->insert($summary);
    }
    
    /**
     * validate amount
     * 
     * @return null
     */
    private function _validateAmount()
    {
        if ($this->validator->isNull($this->_formValues['loan_amount'])) {
            $this->error->required('Amount');
            return;
        }
        
        if (! $this->validator->isPositiveNumber($this->_formValues['loan_amount'])) {
            $this->error->invalid('Amount');
        }
    }
    
    /**
     * validate tenure
     * 
     * @param string $tenure
     * @return null
     */
    private function _validateTenure()
    {
        if ($this->validator->isNull($this->_formValues['tenure'])) {
            $this->error->required('Tenure');
            return;
        }
        
        if (! $this->validator->isPositiveNumber($this->_formValues['tenure'])) {
            $this->error->invalid('Tenure');
            return;
        }
        
        if ($this->_formValues['tenure'] < LoanConfig::MIN_TENURE || $this->_formValues['tenure'] > LoanConfig::MAX_TENURE) {
            $this->error->add('Tenure exceeds limit');
        }
    }
    
    /**
     * validate interest rate
     * 
     * @param string $rate
     * @param string $title
     * @return null
     */
    private function _validateInterestRate($rate, $title)
    {
        if ($this->validator->isNull($rate)) {
            $this->error->required($title);
            return;
        }
        
        if ('0' == $rate || '0.0' == $rate || '0.00' == $rate) {
            return;
        }
        
        if (! $this->validator->integer($rate)) {
            if (! $this->validator->decimal($rate)) {
                $this->error->invalid($title);
                return;
            }
            
            if (! $this->validator->decimalPoints($rate, 2)) {
                $this->error->invalid($title);
                return;
            }
        }
        
        if ($rate < LoanConfig::MIN_INTEREST_RATE || $rate > LoanConfig::MAX_INTEREST_RATE) {
            $this->error->invalid($title);
        }
    }
    
    /**
     * validate emi
     * 
     * @param int $emi
     * @return null
     */
    private function _validateEmi($emi)
    {
        if ($this->validator->isNull($emi)) {
            $this->error->required('EMI');
            return;
        }
        
        if (! $this->validator->integer($emi)) {
            $this->error->invalid('EMI');
        }
    }
    
    /**
     * check EMI limits
     * 
     * @return null
     */
    private function _checkEmiLimits()
    {
        $annualInterestRate = LoanHelper::interestRate($this->_formValues['interest_rate']);
        $upper = LoanHelper::emi($this->_formValues['loan_amount'], $annualInterestRate, $this->_formValues['tenure'] - 1);
        if ($this->_formValues['emi'] >= $upper) {
            $this->error->add('EMI is too high for selected tenure');
            return;
        }
        
        $lower = LoanHelper::emi($this->_formValues['loan_amount'], $annualInterestRate, $this->_formValues['tenure'] + 1);
        if ($this->_formValues['emi'] <= $lower) {
            $this->error->add('EMI is too low for selected tenure');
            return;
        }
    }
    
    /**
     * format decimal value
     * 
     * @param string $number
     * @return string
     */
    private function _toDecimal($number)
    {
        return sprintf('%0.2f', $number);
    }
    
    /**
     * save comment
     * 
     * @return null
     */
    private function _saveComment($currentLoanId)
    {
        $logger = new CommentLogger($this->_administaratorId);
        if (! empty($this->_formValues['note1'])) {
            $logger->id($currentLoanId)
                   ->save($this->_formValues['note1']);
        }
        
        if (! empty($this->_formValues['note2'])) {
            $logger->id($this->_loanId)
                   ->save($this->_formValues['note2']);
        }
    }
    
    /**
     * close loan
     */
    private function _closeLoan($type = null)
    {
        $currentLoanId = $this->_store->id();
        $now = DateHelper::timestamp();
        $update = [
            'closed' => 1,
            'loan_status_id' => LoanHelper::STATUS_REFINANCED,
            'updated_at' => $now,
            'last_updated' => DateHelper::today(),
        ];
        $this->getModel()->updateById($update, $currentLoanId);
        
        $summary = [
            'refinanced' => 1,
            'refinanced_date' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'refinanced_to' => $this->_loanId,
            'refinance_type' => LoanHelper::STATUS_REFINANCED,
            'modified_at' => $now,
        ];
                
        $summaryModel = new LoanSummary();
        $summaryId = $summaryModel->selectIdByColumn('loan_id', $currentLoanId);
        $summaryModel->updateById($summary, $summaryId);

        $loanSummary = $this->_store->loanSummary();
        
        $rescheduleModel = new LoanReschedule();
        $refinance = [
            'source_loan_id' => $currentLoanId,
            'new_loan_id' => $this->_loanId,
            'reschedule_date' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'administrator_id' => $this->_administaratorId,
            'comments' => $this->_formValues['note1'],
            'created_at' => $now,
            'last_paid_date' => $loanSummary->as_on_date,
            'tenure_ending_date' => $loanSummary->as_on_date,
            'outstanding' => $loanSummary->balance_actual_principal_interest_outstanding,
            'overdue_interest_rate' => $this->_formValues['overdue_interest_rate'],
            'interest_days' => $this->_formValues['overdue_days'],
            'interest_amount' => $this->_formValues['overdue_amount'],
            'refinance_type' => LoanHelper::STATUS_REFINANCED,
            'refinance_amount' => $this->_formValues['refinance_amount'],
            'loan_amount' => $this->_formValues['loan_amount'],
            'interest_rate' => $this->_formValues['interest_rate'],
            'penal_interest_rate' => $this->_formValues['penal_interest_rate'],
            'tenure' => $this->_formValues['tenure'],
            'emi' => $this->_formValues['emi'],
        ];
        
        $rescheduleModel->insert($refinance);
        
        $logger = new TimelineLogger($this->_administaratorId);
        $logger->id($currentLoanId)
               ->save('Refinanced/Rescheduled', 'Loan refinanced & rescheduled to new Loan with DCB ID ' . $this->_loanId);
        
        $logger->id($this->_loanId)
               ->save('DCB Created', 'Loan created by refinancing & rescheduling DCB ID ' . $currentLoanId);
        
        
        //FMC receipt creation
        $transBy = 0;
        $loan = $this->_store->loan();
        $store = new LoanStore($this->_loanId);        
        $fmc = $store->fmc();
        $fmc_id = $fmc->id;
        $now = DateHelper::timestamp();
        if($this->_formValues['payment_type'] == 'Bank'){
            $transBy =  $this->_formValues['bank_id'];
        }
        $trans = new Transactions();
        if($type == 'loan_reschedule'){
        $preclosureAmount = $this->_formValues['loan_amount'] - $this->_formValues['refinance_amount'];
        if (LoanHelper::LOAN_SOURCE_FMC == $this->_formValues['loan_source_id']) {                
            // Cash Receipts
            $mfHeadData['head_name'] =  "DCB Re-Finance Receipt";
            $mfHeadData['code'] = "DCB_re_finance";                
            $mfHeadData['transType'] = 'Direct Income';
            $particulars = $loan_source ." DCB Re-Finance Receipt";
            $insert = [
                'receipt' => 0,
                'amount'  => $preclosureAmount,
                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                'member_id' => $loan->member_id,
                'remarks' => $particulars,
                'created_by' => $this->_administaratorId,
                'transBy' => $transBy,
                'remarks' => "DCB Re-Finance",
                'created_at' => $now,
                'updated_at' => $now,
                'loan_id' => $currentLoanId,
            ];
            $trans->generateReceipt($mfHeadData, $insert, $fmc_id);
            } else {
                $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                //FMC Account
                    $loanSourceQuery = "select loan_source, code from  " . $this->_loanSourceModel->getTableQuoted()." where id = {$this->_formValues['loan_source_id']}";
                    $loanSourceType = $this->_loanSourceModel->queryRows($loanSourceQuery);
                    $loan_source = $loanSourceType[0]->loan_source;
                    $mfHeadData['head_name'] =  $loan_source. " DCB Re-Finance Receipt";
                    $mfHeadData['code'] = $loanSourceType[0]->code."_rf_receipt";
                    $mfHeadData['transType'] = 'Direct Income';
                    $particulars = $loan_source ." DCB Re-Finance Receipt";
                    $insert = [
                        'receipt' => 0,
                        'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),                   
                        'amount'  => $preclosureAmount,
                        'member_id' => $loan->member_id,
                        'remarks' => $particulars,
                        'created_by' => $this->_administaratorId,
                        'transBy' => $transBy,
                        'created_at' => $now,
                        'updated_at' => $now, 
                        'loan_id' => $currentLoanId,
                    ];
                    $trans->generateReceipt($mfHeadData, $insert, $fmc_id);
                } 
        }
        /*
            $mfHeadData['head_name'] =  "DCB Re-Finance";
            $mfHeadData['code'] = "DCB_re_finance";                
            $mfHeadData['transType'] = 'Direct Income';
            $insert = [
                'receipt' => 0,
                'transDate' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
                'member_id' => $loan->member_id,
                'remarks' =>  "DCB Re-Finance",
                'created_by' => $this->_administaratorId,
                'transBy' => $transBy,
                'remarks' => "DCB Re-Finance",
                'created_at' => $now,
                'updated_at' => $now,
                'loan_id' => $currentLoanId,
            ];
            $insert['amount'] = $this->_formValues['loan_amount'] - $this->_formValues['refinance_amount'];
            $trans = new Transactions();
            $trans->generateReceipt($mfHeadData, $insert, $fmc_id);
        */
        
        $this->_store->clearPqam($refinance['reschedule_date']);
    }
    
}
