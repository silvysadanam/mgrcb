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
use Application\Utility\{EmiCalculator, LoanHelper};
use Application\Helper\{CommentLogger, TimelineLogger};

use Model\{Schedule, LoanReschedule, LoanSummary};

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
        ];
        $this->addFields($fields);
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
        $this->_generateSchedule($insert);
        $this->_saveComment($loan->id);
        $this->_closeLoan();

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
    private function _closeLoan()
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
        
        $this->_store->clearPqam($refinance['reschedule_date']);
    }
    
}
