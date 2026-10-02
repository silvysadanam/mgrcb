<?php
/**
 * Application LoanConfig Constant
 * 
 * @category Application
 * @package  Application\Constant
 */

namespace Application\Constant;


class LoanConfig
{
    const MIN_AMOUNT = 1000;
    const MAX_AMOUNT = 9999999;
    
    const MIN_TENURE = 6;
    const MAX_TENURE = 120;
    
    const MIN_INTEREST_RATE = 0;
    const MAX_INTEREST_RATE = 25;
    
    const DAYS_IN_YEAR = 365;
    const DAYS_IN_LEAP_YEAR = 366;
    
    const EMI_DEVIATION = 500;
    
    public function __construct() { }
    
}
