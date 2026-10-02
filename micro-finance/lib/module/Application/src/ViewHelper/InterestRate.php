<?php
/**
 * InterestRate View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class InterestRate extends ViewHelper
{
   /**
    * get formatted interest rate
    * 
    * @param string $number
    * @param boolean $auto
    * @return string
    */
    public function interestRate($number, $auto = false)
    {
        if (! $auto) {
            return sprintf('%0.2f', $number);
        }
        
        if (0 == preg_match('/[.]/', $number)) {
            return $number;
        }
        
        $parts = explode('.', $number);
        if (1 == preg_match('/^00*$/', $parts[1])) {
            return $parts[0];
        }
        
        $decimal = preg_replace('/0+$/', '', $parts[1]);        
        
        return $parts[0] . '.' . $decimal;
    }
    
}
