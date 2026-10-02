<?php
/**
 * Application ModifyDcb EditDcb Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Constant\LoanConfig;
use Application\Helper\CommentLogger;
use Application\Utility\{EmiCalculator, LoanHelper};

use Model\{Schedule, LoanSummary};

use Web\Form\Form;
use Web\Utility\DateHelper;


class EditDcbForm extends Form
{
    /**
     * 
     * @var int
     */
    private $_administratorId;
    
    /**
     * 
     * @var array
     */
    private $_formValues;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'loan_type_id',
            'loan_source_id',
            'account_number',
            'note',
            'loan_date',
            'loan_amount',
            'interest_rate',
            'penal_interest_rate',
            'tenure',
            'emi',
        ];
        $this->addFields($fields);
        $this->trackChanges();
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administratorId = $id;
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
     * change loan
     * 
     * @return int
     */
    public function change()
    {
        $modified = $this->getModified();
        if (empty($modified)) {
            return  false;
        }
        
        $this->_saveComment();
        if (isset($modified['note'])) {
            unset($modified['note']);
        }
        
        if (empty($modified)) {
            return  false;
        }
        
        $reschedule = false;
        
        $rescheduleFields = [
            'loan_date', 'loan_amount', 'interest_rate', 'tenure', 'emi',
        ];
        
        foreach ($rescheduleFields as $field) {
            if (isset($modified[$field])) {
                $reschedule = true;
                break;
            }
        }
        
        $update = [];
        $direct = ['loan_type_id', 'loan_source_id', 'account_number', 'emi', 'tenure'];
        $decimals = ['loan_amount', 'interest_rate', 'penal_interest_rate'];
        
        foreach ($direct as $field2) {
            if (isset($modified[$field2])) {
                $update[$field2] = $modified[$field2];
            }
        }
        
        foreach ($decimals as $field3) {
            if (isset($modified[$field3])) {
                $update[$field3] = $this->_toDecimal($modified[$field3]);
            }
        }
        
        if (isset($modified['loan_date'])) {
            $update['loan_date'] = DateHelper::fromDatepicker($this->_formValues['loan_date']);
        }
        
        if ($reschedule) {
            $rate = LoanHelper::interestRate($this->_formValues['interest_rate']);
            $exactEmi = LoanHelper::emi($this->_formValues['loan_amount'], $rate, $this->_formValues['tenure'], false);
            $update['exact_emi'] = $exactEmi;
            $update['calculated_emi'] = intval($exactEmi);
        }
        
        if (empty($update)) {
            return false;
        }
        
        $update['updated_at'] = DateHelper::timestamp();
        $update['last_updated'] = DateHelper::today();
        
        $this->save($update);
        
        if ($reschedule) {
            $this->_reschedule();
        }
        
        return true;
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
    
    private function _saveComment()
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administratorId);
        $logger->id($this->getId())
               ->save($this->_formValues['note']);
    }
    
    private function _reschedule()
    {
        $loan = $this->getModel()->find($this->getId());
        
        $calculator = new EmiCalculator();
        $calculator->amount($loan->loan_amount)
                   ->interest($loan->interest_rate)
                   ->loanDate($loan->loan_date)
                   ->tenure($loan->tenure)
                   ->emi($loan->emi);
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

        $this->save($update);
        $this->_saveShedule($schedule['schedule']);
        $this->_saveSummary($loan, count($schedule['schedule']));
    }
    
    /**
     * save schedule
     * 
     * @param array $instalments
     */
    private function _saveShedule($instalments)
    {
        $id = $this->getId();
        $model = new Schedule();
        $model->delete(['loan_id' => $id]);
        $default = [
            'loan_id' => $id,
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
    private function _saveSummary($loan, $intalments)
    {
        $summaryModel = new LoanSummary();
        $update = [
            'instalments' => $intalments,
            'scheduled_demand_loan_outstanding' => $loan->loan_amount,
            'as_on_date' => $loan->loan_date,
            'modified_at' => DateHelper::timestamp(),
        ];
        $summaryModel->update($update, ['loan_id' => $loan->id]);
    }
    
}
