<?php
/**
 * Shared DeductionConfig Constant
 * 
 * @category Shared
 * @package  Shared\Constant
 */

namespace Shared\Constant;


class DeductionConfig
{
    const WITHDRAWAL_FMC = 1;
    const WITHDRAWAL_MEMBER = 2;
    const WITHDRAWAL_RESTRICTED = 3;
    const START_DATE = '01/04/2022';
    const START_DATE_MYSQL = '2022-04-01';
    
    public function __construct() { }
    
}
