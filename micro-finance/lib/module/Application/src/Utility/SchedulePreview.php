<?php
/**
 * Application SchedulePreview Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\{EmiCalculator, LoanHelper};

use Web\Utility\DateHelper;


class SchedulePreview
{
    /**
     * 
     * @var \Application\Utility\EmiCalculator
     */
    private $_calculator;
    
    /**
     * 
     * @var string
     */
    private $_html = '';
    
    /**
     * 
     * @var array
     */
    private $_amountColumns;
    
    public function __construct()
    {
        $this->_calculator = new EmiCalculator();
        $this->_amountColumns = [
            'opening_principal', 'interest_component', 'principal_component',
            'emi', 'closing_principal', 'cumulative_interest', 
            'cumulative_principal', 'cumulative_emi',
        ];
    }
    
    /**
     * run
     * 
     * @param array $values
     * @return array
     */
    public function run($values)
    {
        $this->_calculator->amount($values['amount'])
                   ->interest($values['interest'])
                   ->loanDate(DateHelper::fromDatepicker($values['loan_date']))
                   ->tenure($values['tenure'])
                   ->emi($values['emi']);
        $schedule = $this->_calculator->run();

        foreach ($schedule['schedule'] as $item) {
            $this->_add($item);
        }
        
        $brokenPeriod = ['No', 'Yes'];

        $loan = [
            'loan_date' => $values['loan_date'],
            'principal' => LoanHelper::amount($values['amount']),
            'tenure' => $values['tenure'],
            'interest' => LoanHelper::amount($values['interest']),
            'emi' => LoanHelper::amount($schedule['emi']),
            'broken_period' => $brokenPeriod[$schedule['has_broken_period']],
        ];

        $total = [
            'interest' => $schedule['total_interest'],
            'principal' => $schedule['total_principal'],
            'emi' => $schedule['total_emi'],
        ];
        
        return [
            'html' => $this->_html,
            'loan' => $loan,
            'total' => $total,
        ];
    }
    
    
    /**
     * add schedule item
     * 
     * @param array $item
     */
    private function _add($item)
    {
        $this->_html .= '<tr>';
        $this->_instalmentNumber($item['instalment']);
        $this->_date($item['instalment_date']);
        
        foreach ($this->_amountColumns as $column) {
            $this->_amount($item[$column]);
        }
        
        $this->_html .= '</tr>';
    }
    
    /**
     * add instalment number
     * 
     * @param int $number
     * @return string
     */
    private function _instalmentNumber($number)
    {
        $this->_column($number);
    }
    
    /**
     * add date
     * 
     * @param string $date
     */
    private function _date($date)
    {
        $this->_column(DateHelper::datepicker($date));
    }
    
    /**
     * add amount
     * 
     * @param string $amount
     */
    private function _amount($amount)
    {
       $this->_column(LoanHelper::amount($amount));
    }
    
    /**
     * add column to html
     * 
     * @param string $value
     */
    private function _column($value)
    {
        $this->_html .= '<td>' . $value . '</td>';
    }
    
}
