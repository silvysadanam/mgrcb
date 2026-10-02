<?php
/**
 * Application Dcb Create Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Application\Constant\LoanConfig;
use Application\Utility\{EmiCalculator, LoanHelper};
use Application\Helper\CommentLogger;

use Model\{Schedule, LoanSummary, Member, Fmc, LoanSource, LoanNumber};

use Web\Form\Form;
use Web\Utility\DateHelper;


class DcbCreateForm extends Form
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
    
    private $_accountNumber;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'fmc_id',
            'member_id',
            'member_name',
            'loan_source_id',
            'loan_type_id',
            'loan_date',
            'loan_amount',
            'interest_rate',
            'penal_interest_rate',
            'tenure',
            'emi',
            'note',
            'account_number',
            'payment_type',
            'bank_id',
        ];
        $this->addFields($fields);
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
        if ($this->validator->notSelected($this->_formValues['member_id'])) {
            $this->error->notSelected('Member');
        }
        
        if ($this->validator->notSelected($this->_formValues['loan_source_id'])) {
            $this->error->notSelected('Source');
        }
        
        if ($this->validator->notSelected($this->_formValues['loan_type_id'])) {
            $this->error->notSelected('Type');
        }
        if($this->_formValues['payment_type'] == 'Bank') {
            if ($this->validator->notSelected($this->_formValues['bank_id'])) {
                $this->error->notSelected('Bank');
             }
        }

        if ($this->validator->isNull($this->_formValues['loan_date'])) {
            $this->error->required('Date');
        } else {
            $date = DateHelper::fromFormat($this->_formValues['loan_date'], DateHelper::DATEPICKER);
            if (false === $date) {
                $this->error->invalid('Date');
            } else {
                if (DateHelper::isFutureDate($date->format(DateHelper::MYSQL_DATE))) {
                    $this->error->invalid('Date');
                } elseif ($date->format('Y') < 2000) {
                    $this->error->invalid('Date');
                }
            }
        }
        
        $this->_validateAmount();

        $this->_validateInterestRate($this->_formValues['interest_rate'], 'Rate of Interest');
        $this->_validateInterestRate($this->_formValues['penal_interest_rate'], 'Penal Interest Rate');
        $this->_validateTenure($this->_formValues['tenure']);
        $this->_validateEmi($this->_formValues['emi']);
        if ($this->noError()) {
            $this->_checkEmiLimits();
        }
        
        if ($this->noError()) {
            $sql = $this->getModel()->getSql();
            $sql->whereId('member_id', $this->_formValues['member_id'])
                ->whereId('loan_type_id', $this->_formValues['loan_type_id'])
                ->whereId('loan_source_id', $this->_formValues['loan_source_id'])
                ->whereEqual('loan_amount', $this->_formValues['loan_amount'])
                ->whereEqual('loan_date', DateHelper::fromDatepicker($this->_formValues['loan_date']));
            if ($this->getModel()->selectTotal($sql->getWhere()) > 0) {
                $this->error->exists('DCB');
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create loan
     * 
     * @return int
     */
    public function create($administratorId)
    {
        $now = DateHelper::timestamp();
        $rate = LoanHelper::interestRate($this->_formValues['interest_rate']);
        $exactEmi = LoanHelper::emi($this->_formValues['loan_amount'], $rate, $this->_formValues['tenure'], false);
        $this->_accountNumber = $this->_formValues['account_number'];
        $transBy =0;
        $paymentType = $this->_formValues['payment_type'];
        if($paymentType === 'Bank'){
            $transBy = $this->_formValues['bank_id'];
        }
        $insert = [
            'member_id' => $this->_formValues['member_id'],
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
            'account_number' => $this->_accountNumber,
            'created_by' => $administratorId,
            'approval_status' => 'Pending',
            'transBy' => $transBy
            
        ];
        
        $this->_loanId = $this->save($insert);
        
        $this->_generateSchedule($insert);
        $this->_saveComment($administratorId);
        
        if (LoanHelper::LOAN_SOURCE_MF == $this->_formValues['loan_source_id']) {
            $this->_generateAccountNumber();
        } else {
            $fmcLoans = LoanHelper::branchLoanSources();
            if (isset($fmcLoans[$insert['loan_source_id']])) {
                $memberModel = new Member();
                $fmcId = $memberModel->selectColumnById('fmc_id', $insert['member_id']);
                $fmcModel = new Fmc();
                $loanNumber = $fmcModel->selectColumnById('loan_number', $fmcId);
                ++$loanNumber;
                $fmcModel->updateById(['loan_number' => $loanNumber], $fmcId);
            }
        }

        return $this->_loanId;
    }
    
    public function getAccountNumber()
    {
        return $this->_accountNumber;
    }
    
    
    private function _generateAccountNumber()
    {
        $loanSourceModel = new LoanSource();
        $loanNumberModel = new LoanNumber();
        $sourceId = $this->_formValues['loan_source_id'];
        $code = $loanSourceModel->selectColumnById('loan_source', $sourceId);
        $lastNumber = 0;
        $sql = $loanSourceModel->getSql();
        $sql->whereId('loan_source_id', $sourceId);
        $where = $sql->getWhere();
        if ($loanNumberModel->selectTotal($where) > 0) {
            $lastNumber = $loanNumberModel->selectMax('loan_number', $where);
        }
        
        $loanNumber = LoanHelper::getLoanNumber($code, $lastNumber, $this->_formValues['loan_date']);
        $this->_accountNumber = $loanNumber['loan_account'];
        $update = [
            'account_number' => $this->_accountNumber,
        ];
        $this->getModel()->updateById($update, $this->_loanId);
        
        $loanNumber['fmc_id'] = $this->_formValues['fmc_id'];
        $loanNumber['loan_source_id'] = $sourceId;
        $loanNumber['loan_id'] = $this->_loanId;
        
        $loanNumberModel->insert($loanNumber);
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
    
    /**
     * save loan summary
     * 
     * @param int $intalments
     */
    private function _saveSummary($intalments)
    {
        $summaryModel = new LoanSummary();
        $insert = [
            'loan_id' => $this->_loanId,
            'instalments' => $intalments,
            'scheduled_demand_loan_outstanding' => $this->_formValues['loan_amount'],
            'as_on_date' => DateHelper::fromDatepicker($this->_formValues['loan_date']),
            'created_at' => DateHelper::timestamp(),
            'modified_at' => DateHelper::timestamp(),
        ];
        $summaryModel->insert($insert);
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
    
    private function _saveComment($administratorId)
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($administratorId);
        $logger->id($this->_loanId)
               ->save($this->_formValues['note']);
    }
    
}
