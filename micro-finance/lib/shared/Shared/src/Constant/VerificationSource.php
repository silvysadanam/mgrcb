<?php
/**
 * Shared Constant VerificationSource
 * 
 * @category Shared
 * @package  Shared\Constant
 */

namespace Shared\Constant;


class VerificationSource
{
    const ADMIN_PASSWORD_RECOVERY = 1;
    const USER_PASSWORD_RECOVERY = 2;
    const USER_EMAIL_VERIFICATION = 3;
    const USER_MOBILER_NUMBER_VERIFICATION = 4;
    
    
    public function __construct() { }
    
}
