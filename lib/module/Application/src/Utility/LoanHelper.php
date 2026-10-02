<?php
/**
 * Application LoanHelper Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Web\Utility\DateHelper;
use Application\Constant\{LoanConfig, ManagerType};



class LoanHelper
{
    const AGEING_ONTIME = 1;
    const AGEING_1_30 = 2;
    const AGEING_31_60 = 3;
    const AGEING_61_90 = 4;
    const AGEING_91_120 = 5;
    const AGEING_121_150 = 6;
    const AGEING_151_180 = 7;
    const AGEING_181_365 = 8;
    const AGEING_365 = 9;
    
    const STATUS_WRITTEN_OFF = 1;
    const STATUS_REFINANCED = 2;
    const STATUS_RESCHEDULED = 3;
    const STATUS_DELINQUENT = 4;
    const STATUS_PRECLOSED = 5;
    const STATUS_INACTIVE = 6;
    
    const FILTER_STATUS_ACTIVE = 1;
    const FILTER_STATUS_PRE_CLOSED = 2;
    const FILTER_STATUS_CLOSED = 3;
    const FILTER_STATUS_DELINQUENT = 4;
    const FILTER_STATUS_WRITTEN_OFF = 5;
    const FILTER_STATUS_REFINANCED = 6;
    const FILTER_STATUS_RESCHEDULED = 7;
    const FILTER_STATUS_INACTIVE = 8;
    
    const REPORTING_DATE_ID = 'Ymd';
    
    const AGEING_MAX_ID = 9;
    
    const INACTIVE_NON_REPAYMENT = 2;
    
    const SOURCE_TYPE_OTHER = 0;
    const SOURCE_TYPE_FMC_LOAN = 1;
    const SOURCE_TYPE_BANK = 2;
    
    const LOAN_SOURCE_FMC = 4;    
    const LOAN_SOURCE_MF = 7;
    const MF_ACCOUNT_ID = 8; 
    
    /**
     * get interest rate as decimal
     * 
     * @param int $rate
     * @return float
     */
    public static function interestRate($rate)
    {
        return $rate * 0.01;
    }
    
    /**
     * get daily interest rate
     * 
     * @param int $rate
     * @param boolean $leapYear
     * @return float
     */
    public static function dailyInterestRate($rate, $leapYear = false)
    {
        $days = LoanConfig::DAYS_IN_YEAR;
        if ($leapYear) {
            ++$days;
        }
        
        return ($rate * 0.01) / $days;
    }
    
    /**
     * get daily interest rate by date
     * 
     * @param int $rate
     * @param string $date
     * @return float
     */
    public static function dailyInterestRateByDate($rate, $date)
    {
        $leapYear = 1 == DateHelper::format('L', $date);
        
        return self::dailyInterestRate($rate, $leapYear);
    }
    
    /**
     * calculate emi
     * 
     * @param int $principal
     * @param int $annualInterestRate
     * @param int $tenure
     * @param boolean $integer
     * @return int|float
     */
    public static function emi($principal, $annualInterestRate, $tenure, $integer = true)
    {
        if ($annualInterestRate > 0.00) {
            //P x [R x (1+R)^n]/[{(1+R)^n}-1]
            // R
            $periodicInterest = $annualInterestRate / 12;
            // (1+R)^n
            $factor = (1 + $periodicInterest) ** $tenure;
            $emi = ($principal * $periodicInterest * $factor) / ($factor - 1);

            if ($integer) {
                return ceil($emi);
            }
            
            return self::amount($emi);
        }
        
        if ($integer) {
            return ceil($principal / $tenure);
        }
        
        return self::amount($principal / $tenure);
    }
    
    /**
     * get instalment dates
     * 
     * @param string $startDate
     * @param int $tenure
     * @return array
     */
    public static function dates($startDate, $tenure)
    {
        $list = [];
        $date = DateHelper::getDateTime($startDate);
        $daysInMonth = $date->format('t');
        $startDay = $date->format('j');
        $hasBrokenPeriod = false;
        $instalment = 0;
        if ($daysInMonth != $startDay) {
            $daysRemaining = $daysInMonth - $startDay;
            $date->setDate($date->format('Y'), $date->format('n'), $daysInMonth);
            $list[] = [
                'date' => $date->format(DateHelper::MYSQL_DATE),
                'days' => $daysRemaining,
                'instalment' => 0,
                'broken_period' => 1,
                'first_instalment' => 1,
                'first_emi' => 0,
                'last_instalment' => 0,
            ];
            $hasBrokenPeriod = true;
        }
        
        for ($count = 0; $count < $tenure; $count++) {
            ++$instalment;
            $date->modify('+15 days');
            $days = $date->format('t');
            $date->setDate($date->format('Y'), $date->format('n'), $days);
            $item = [
                'date' => $date->format(DateHelper::MYSQL_DATE),
                'days' => $days,
                'instalment' => $instalment,
                'broken_period' => 0,
                'first_instalment' => 0,
                'first_emi' => 0,
                'last_instalment' => 0,
            ];
            if (0 == $count) {
                if ($hasBrokenPeriod) {
                    $item['first_emi'] = 1;
                } else {
                    $item['first_instalment'] = 1;
                    $item['first_emi'] = 1;
                }
            } elseif ($tenure == $instalment) {
                $item['last_instalment'] = 1;
            }
            $list[] = $item;
        }
        
        return $list;
    }
    
    /**
     * format number to 2 decimal
     * 
     * @param string $value
     * @return float
     */
    public static function amount($value)
    {
        return sprintf('%0.2f', round($value, 2, PHP_ROUND_HALF_UP));
    }
    
    /**
     * sum of array values as amount
     * 
     * @param array $values
     * @return float
     */
    public static function sum($values)
    {
        return self::amount(array_sum($values));
    }
    
    /**
     * get percentage
     * 
     * @param float $value
     * @param float $total
     * @return float
     */
    public static function percentage($value, $total)
    {
        if ($total > 0.00 && $value > 0.00) {
            if ($value <= $total) {
                return self::amount(100 * ($value / $total));
            }
            
            return '100.00';
        }
        
        return '0.00';
    }
    
    /**
     * add two amount values
     * 
     * @param float $value1
     * @param float $value2
     * @return float
     */
    public static function add($value1, $value2)
    {
        return self::amount($value1 + $value2);
    }
    
    /**
     * check if date is leap year
     * 
     * @param string $date
     * @return boolean
     */
    public static function leapYear($date)
    {
        return 1 == DateHelper::format('L', $date);
    }
    
    /**
     * get 0.00 amount
     * 
     * @return string
     */
    public static function zero()
    {
        return '0.00';
    }
    
    /**
     * get reporting date
     * 
     * @return string
     */
    public static function reportingDate()
    {
        $today = DateHelper::getDateTime();
        $today->modify('-1 month');
        $days = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days);
        
        return $today->format(DateHelper::MYSQL_DATE);
    }
    
    /**
     * get reporting date id
     * 
     * @return string
     */
    public static function reportingDateId()
    {
        $today = DateHelper::getDateTime();
        $today->modify('-1 month');
        $days = $today->format('t');
        $today->setDate($today->format('Y'), $today->format('n'), $days);
        
        return $today->format(self::REPORTING_DATE_ID);
    }
    
    /**
     * get reporting date options
     * 
     * @return array
     */
    public static function reportingDateOptions()
    {
        $options = [];
        $dateTime = DateHelper::getDateTime(self::reportingDate());
        $options[$dateTime->format('Ymd')] = $dateTime->format('d F Y');
        for ($count = 0; $count < 13; $count++) {
            $dateTime->modify('-40 days');
            $days = $dateTime->format('t');
            $month = $dateTime->format('n');
            $year = $dateTime->format('Y');
            $dateTime->setDate($year, $month, $days);
            $options[$dateTime->format('Ymd')] = $dateTime->format('d F Y');
        }
        
        return $options;
    }
    
    /**
     * get ageing options
     * 
     * @return array
     */
    public static function ageingOptions()
    {
        $options = [
            self::AGEING_ONTIME => 'On Time',
            self::AGEING_1_30 => '01-30',
            self::AGEING_31_60 => '31-60',
            self::AGEING_61_90 => '61-90',
            self::AGEING_91_120 => '91-120',
            self::AGEING_121_150 => '121-150',
            self::AGEING_151_180 => '151-180',
            self::AGEING_181_365 => '181-365',
            self::AGEING_365 => '>365',
        ];
        
        return $options;
    }
    
    /**
     * get ageing
     * 
     * @param int $value
     * @return array|int
     */
    public static function getAgeing($value)
    {
        switch ($value) {
            case self::AGEING_ONTIME:
                $limits = 0;
                break;
            
            case self::AGEING_1_30:
                $limits = [0, 31];
                break;
            
            case self::AGEING_31_60:
                $limits = [30, 61];
                break;
            
            case self::AGEING_61_90:
                $limits = [60, 91];
                break;
            
            case self::AGEING_91_120:
                $limits = [90, 121];
                break;
            
            case self::AGEING_121_150:
                $limits = [120, 151];
                break;
            
            case self::AGEING_151_180:
                $limits = [150, 181];
                break;
            
            case self::AGEING_181_365:
                $limits = [180, 366];
                break;
            
            default:
                $limits = 365;
                break;
        }
        
        return $limits;
    }
    
    /**
     * get ageing index
     * 
     * @param float $ageing
     * @return int
     */
    public static function getAgeingIndex($ageing)
    {
        $index = self::AGEING_ONTIME;
        for ($count = self::AGEING_1_30; $count <= self::AGEING_365; $count++) {
            $limits = self::getAgeing($count);
            if (is_array($limits)) {
                if ($ageing > $limits[0] && $ageing < $limits[1]) {
                    $index = $count;
                    break;
                }
            } else {
                if (0 == $limits) {
                    if ($ageing == $limits) {
                        $index = $count;
                        break;
                    }
                } else {
                    if ($ageing > $limits) {
                        $index = $count;
                        break;
                    }
                }
            }
        }
        
        return $index;
    }
    
    /**
     * get loan amount options
     * 
     * @return array
     */
    public static function loanAmountOptions()
    {
        $options = [
            1 => '1 Lakh and above',
            2 => '50,000 to 1 Lakh',
            3 => '25,000 to 50,000',
            4 => '10,000 to 25,000',
            5 => 'Below 10,000',
        ];
        
        return $options;
    }
    
    /**
     * get loan amount by key
     * 
     * @param int $value
     * @return int
     */
    public static function getLoanAmount($value)
    {
        switch ($value) {
            case 1:
                $limits = [100000, 1];
                break;
            
            case 2:
                $limits = [50000, 100000];
                break;
            
            case 3:
                $limits = [25000, 50000];
                break;
            
            case 4:
                $limits = [10000, 25000];
                break;
            
            default:
                $limits = [10000, 0];
                break;
        }
        
        return $limits;
    }
    
    /**
     * get total loan amount options
     * 
     * @return array
     */
    public static function totalLoanAmountOptions()
    {
        $options = [
            1 => '>= 8 Lakhs',
            2 => '5 - 8 Lakhs',
            3 => '3 - 5 Lakhs',
            4 => '1 - 3 Lakhs',
            5 => 'Below 1 Lakh',
        ];
         
        return $options;
    }
    
    /**
     * get total loan amount by key
     * 
     * @param int $value
     * @return int
     */
    public static function getTotalLoanAmount($value)
    {
        switch ($value) {
            case 1:
                $limits = [800000, 1];
                break;
            
            case 2:
                $limits = [500000, 800000];
                break;
            
            case 3:
                $limits = [300000, 800000];
                break;
            
            case 4:
                $limits = [100000, 300000];
                break;
            
            default:
                $limits = [100000, 0];
                break;
        }
        
        return $limits;
    }
    
    /**
     * get LLR options
     * 
     * @return array
     */
    public static function getLlrOptions()
    {
        $options = [
            self::AGEING_ONTIME => 0,
            self::AGEING_1_30 => 10,
            self::AGEING_31_60 => 15,
            self::AGEING_61_90 => 20,
            self::AGEING_91_120 => 25,
            self::AGEING_121_150 => 30,
            self::AGEING_151_180 => 50,
            self::AGEING_181_365 => 75,
            self::AGEING_365 => 100,
        ];
        
        return $options;
    }
    
    /**
     * get ageing list
     * 
     * @return array
     */
    public static function ageingList()
    {
        $options = [
            self::AGEING_ONTIME => 'On Time',
            self::AGEING_1_30 => '1 to 30 days',
            self::AGEING_31_60 => '31-60 days',
            self::AGEING_61_90 => '61-90 days',
            self::AGEING_91_120 => '91-120 days',
            self::AGEING_121_150 => '121-150 days',
            self::AGEING_151_180 => '151-180 days',
            self::AGEING_181_365 => '181-365 days',
            self::AGEING_365 => '>365 days',
        ];
        
        return $options;
    }
    
    /**
     * get status filter options
     * 
     * @return array
     */
    public static function statusFilterOptions()
    {
        $options = [
            self::FILTER_STATUS_ACTIVE => 'Active',
            self::FILTER_STATUS_PRE_CLOSED => 'Pre-closed',
            self::FILTER_STATUS_CLOSED => 'Closed at end of tenure',
            self::FILTER_STATUS_DELINQUENT => 'Tenure ended with OD',
            self::FILTER_STATUS_WRITTEN_OFF => 'Written off',
            self::FILTER_STATUS_REFINANCED => 'Refinanced & Rescheduled',
            self::FILTER_STATUS_RESCHEDULED => 'Rescheduled',
            self::FILTER_STATUS_INACTIVE => 'Inactive',
        ];
        
        return $options;
    }
    
    /**
     * get previous day
     * 
     * @param string $date
     * @return string
     */
    public static function previousDay($date)
    {
        $dateTime = DateHelper::getDateTime($date);
        $dateTime->modify('-1 day');
        
        return $dateTime->format(DateHelper::MYSQL_DATE);
    }
    
    /**
     * get overdue dates
     * 
     * @param string $from
     * @param string $to
     * @return array
     */
    public static function overdueDates($from, $to)
    {
        $toDate = DateHelper::getDateTime($to . ' 12:00:00');
        $toTimestamp = $toDate->getTimestamp();
        $list = [];
        $date = DateHelper::getDateTime($from);
        while ($date->getTimestamp() <= $toTimestamp) {
            $date->modify('+15 days');
            $days = $date->format('t');
            $date->setDate($date->format('Y'), $date->format('n'), $days);
            $date->setTime(1, 0, 0);
            if ($date->getTimestamp() > $toTimestamp) {
                break;
            }
            
            $list[] = ['date' => $date->format(DateHelper::MYSQL_DATE), 'days' => $days];
        }
        
        return $list;
    }
    
    /**
     * get source types
     * 
     * @return array
     */
    public static function sourceTypes()
    {
        return [
            self::SOURCE_TYPE_OTHER => 'Other',
            self::SOURCE_TYPE_FMC_LOAN => 'FMC Loan',
            self::SOURCE_TYPE_BANK => 'Bank',
        ];
    }
    
    /**
     * get source types
     * 
     * @return array
     */
    public static function branchLoanSources()
    {
        $model = new \Model\LoanSource();
        $sql = $model->getSql();
        $sql->whereId('loan_source_type', self::SOURCE_TYPE_FMC_LOAN);
        
        return $model->selectPairs(['id', 'loan_source'], $sql->getWhere());
    }
    
    /**
     * get manager type(s)
     * 
     * @param int|null $key
     * @return string
     */
    public static function managerType($key = null)
    {
        $types = [
            ManagerType::ADMIN => 'Administrator',
            ManagerType::BRANCH => 'FMC Manager',
            ManagerType::CEO => 'CEO',
            ManagerType::FINANCE => 'Finance Manager',
            ManagerType::AUDITOR => 'Auditor',
        ];
        
        if (null !== $key) {
            if (isset($types[$key])) {
                return $types[$key];
            }
        }
        
        return $types;
    }
    
    public static function getLoanNumber($code, $lastNumber, $loanDate)
    {
        $date = DateHelper::fromFormat($loanDate, DateHelper::DATEPICKER);
        $year = $date->format('Y');
        
        $account = $code . '-';
        $next = $lastNumber + 1;
        if ($next < 100) {
            $account .= str_pad(strval($next), 3, '0', STR_PAD_LEFT);
        } else {
            $account .= strval($next);
        }
        
        $account .= '/' . $date->format('y');
        
        $number = [
            'loan_year' => $year,
            'loan_number' => $next,
            'loan_account' => $account,
        ];
        
        return $number;
        
    }
    
}
