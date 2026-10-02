<?php
/**
 * Application EmiCalculator Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\LoanHelper;


class EmiCalculator
{
    /**
     * 
     * @var int
     */
    private $_amount;
    
    /**
     * 
     * @var string
     */
    private $_loanDate;
    
    /**
     * 
     * @var float
     */
    private $_interestRate;
    
    /**
     * 
     * @var int
     */
    private $_tenure;
    
    /**
     * 
     * @var float
     */
    private $_dailyInterestRate;
    
    /**
     * 
     * @var float
     */
    private $_dailyInterestRateLeapYear;
    
    /**
     * 
     * @var int
     */
    private $_emi;
    
    /**
     * 
     * @var array
     */
    private $_default;
    
    public function __construct()
    {
        
    }
    
    /**
     * set amount
     * 
     * @param int $amount
     * @return $this
     */
    public function amount($amount)
    {
        $this->_amount = $amount;
        return $this;
    }
    
    /**
     * set loan date
     * 
     * @param string $date
     * @return $this
     */
    public function loanDate($date)
    {
        $this->_loanDate = $date;
        return $this;
    }
    
    /**
     * set interest rate
     * 
     * @param int $interest
     * @return $this
     */
    public function interest($interest)
    {
        $this->_interestRate = $interest;
        return $this;
    }
    
    /**
     * set tenure
     * 
     * @param int $tenure
     * @return $this
     */
    public function tenure($tenure)
    {
        $this->_tenure = $tenure;
        return $this;
    }
    
    /**
     * set EMI
     * 
     * @param int $emi
     * @return $this
     */
    public function emi($emi)
    {
        $this->_emi = $emi;
        return $this;
    }
    
    /**
     * run
     * 
     * @return array
     */
    public function run()
    {
        $this->_loadDefaults();
        $autoFill = [
            'instalment', 'broken_period', 'first_instalment', 'first_emi', 
            'last_instalment',
        ];
        $hasBrokenPeriod = 0;
        if ($this->_interestRate > 0.00) {
            $dates = LoanHelper::dates($this->_loanDate, $this->_tenure);
            $instalments = [];
            $principal = $this->_amount;
            $interestTotal = 0.00;
            $cumulativePrincipal = 0.00;
            $cumulativeEmi = 0.00;
            $startDate = $dates[0]['date'];
            foreach ($dates as $item) {
                $schedule = $this->_default;
                foreach ($autoFill as $field) {
                    $schedule[$field] = $item[$field];
                }
                $schedule['instalment_date'] = $item['date'];
                $schedule['emi_days'] = $item['days'];
                $schedule['opening_principal'] = $principal;
                $schedule['interest_component'] = LoanHelper::amount($this->_getInterestComponent($principal, $item['date'], $item['days']));
                if (1 == $item['broken_period']) {
                    $hasBrokenPeriod = 1;
                    $schedule['emi'] = '0.00';
                    $schedule['opening_principal'] = $principal;
                    $schedule['principal_component'] = '0.00';
                    $schedule['closing_principal'] = $principal;
                } elseif (1 == $item['last_instalment']) {
                    $schedule['closing_principal'] = '0.00';
                    $schedule['principal_component'] = $principal;
                    $schedule['emi'] = LoanHelper::amount($schedule['interest_component'] + $schedule['principal_component']);
                } else {
                    $schedule['principal_component'] = LoanHelper::amount($this->_emi - $schedule['interest_component']);
                    $schedule['closing_principal'] = LoanHelper::amount($principal - $schedule['principal_component']);
                    $principal = LoanHelper::amount($principal - $schedule['principal_component']);
                }

                $interestTotal = LoanHelper::amount($interestTotal + $schedule['interest_component']);
                $cumulativePrincipal = LoanHelper::amount($cumulativePrincipal + $schedule['principal_component']);
                $cumulativeEmi = LoanHelper::amount($cumulativeEmi + $schedule['emi']);
                $schedule['cumulative_interest'] = $interestTotal;
                $schedule['cumulative_principal'] = $cumulativePrincipal;
                $schedule['cumulative_emi'] = $cumulativeEmi;
                $instalments[] = $schedule;
            }
            
            $end = end($dates);

            return [
                'emi' => $this->_emi,
                'schedule' => $instalments,
                'total_interest' => $interestTotal,
                'end_date' => $end['date'],
                'total_repayment' => $this->_amount + $interestTotal,
                'total_principal' => $cumulativePrincipal,
                'total_emi' => $cumulativeEmi,
                'start_date' => $startDate,
                'has_broken_period' => $hasBrokenPeriod,
            ];
        }
        
        return $this->_noInterestSchedule();
    }
    
    
    /**
     * generate no interest schedule
     * 
     * @return array
     */
    private function _noInterestSchedule()
    {
        $autoFill = [
            'instalment', 'broken_period', 'first_instalment', 'first_emi', 
            'last_instalment',
        ];
        $hasBrokenPeriod = 0;
        $dates = LoanHelper::dates($this->_loanDate, $this->_tenure);
        $instalments = [];
        $principal = $this->_amount;
        $cumulativePrincipal = 0.00;
        $cumulativeEmi = 0.00;
        $startDate = $dates[0]['date'];
        foreach ($dates as $item) {
            $schedule = $this->_default;
            foreach ($autoFill as $field) {
                $schedule[$field] = $item[$field];
            }
            $schedule['instalment_date'] = $item['date'];
            $schedule['emi_days'] = $item['days'];
            $schedule['opening_principal'] = $principal;
            if (1 == $item['broken_period']) {
                $hasBrokenPeriod = 1;
                $schedule['principal_component'] = '0.00';
                $schedule['emi'] = '0.00';
                $schedule['closing_principal'] = $principal;
                $schedule['first_instalment'] = '1';
                $schedule['broken_period'] = '1';
            } elseif (1 == $item['last_instalment']) {
                $schedule['last_instalment'] = '1';
                $schedule['closing_principal'] = '0.00';
                if ($this->_emi > $principal) {
                    $schedule['emi'] = LoanHelper::amount($principal);
                    $schedule['principal_component'] = LoanHelper::amount($schedule['emi']);
                } else {
                    $schedule['principal_component'] = LoanHelper::amount($this->_emi);
                }
            } else {
                $schedule['principal_component'] = LoanHelper::amount($this->_emi);
                $schedule['closing_principal'] = LoanHelper::amount($principal - $schedule['principal_component']);
                $principal = LoanHelper::amount($principal - $schedule['principal_component']);
            }

            $cumulativePrincipal = LoanHelper::amount($cumulativePrincipal + $schedule['principal_component']);
            $cumulativeEmi = LoanHelper::amount($cumulativeEmi + $schedule['emi']);
            $schedule['cumulative_principal'] = $cumulativePrincipal;
            $schedule['cumulative_emi'] = $cumulativeEmi;
            $instalments[] = $schedule;
        }

        return [
            'emi' => $this->_emi,
            'schedule' => $instalments,
            'total_interest' => '0.00',
            'end_date' => $schedule['instalment_date'],
            'total_repayment' => $this->_amount,
            'total_principal' => $cumulativePrincipal,
            'total_emi' => $cumulativeEmi,
            'start_date' => $startDate,
            'has_broken_period' => $hasBrokenPeriod,
        ];
    }
    
    /**
     * load default values
     */
    private function _loadDefaults()
    {
        if (null === $this->_emi) {
            $rate = LoanHelper::interestRate($this->_interestRate);
            $this->_emi = LoanHelper::emi($this->_amount, $rate, $this->_tenure);
        }
        
        $this->_dailyInterestRate = LoanHelper::dailyInterestRate($this->_interestRate);
        $this->_dailyInterestRateLeapYear = LoanHelper::dailyInterestRate($this->_interestRate, true);
        $this->_default = [
            'instalment' => 0,
            'emi' => $this->_emi,
            'instalment_date' => '',
            'interest_component' => '0.00',
            'principal_component' => '0.00',
            'opening_principal' => '0.00',
            'closing_principal' => '0.00',
            'emi_days' => 30,
            'cumulative_interest' => '0.00',
            'cumulative_principal' => '0.00',
            'cumulative_emi' => '0.00',
            'first_instalment' => 0,
            'last_instalment' => 0,
            'broken_period' => 0,
            'first_emi' => 0,
        ];
    }
    
    /**
     * get interest component for instalment
     * 
     * @param float $principalOutstanding
     * @param string $date
     * @param int $days
     * @return float
     */
    private function _getInterestComponent($principalOutstanding, $date, $days)
    {
        if (LoanHelper::leapYear($date)) {
            $rate = $this->_dailyInterestRateLeapYear;
        } else {
            $rate = $this->_dailyInterestRate;
        }
        
        return $principalOutstanding * $rate * $days;
    }
    
}
