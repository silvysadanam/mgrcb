<?php
/**
 * Lakhs View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Lakhs extends ViewHelper
{
   /**
    * get amount formatted as lakhs
    * 
    * @param string $number
    * @param int $decimals
    * @param boolean $strict
    * @return string
    */
    public function lakhs($number, $decimals = 2, $strict = false)
    {
        if ($number > 0) {
            if ($number < 500) {
                if ($strict) {
                    return number_format($number / 100000, $decimals, '.', '');
                }
                
                return 0;
            }
            
            return number_format($number / 100000, $decimals, '.', '');
        }
        
        if ($strict) {
            return number_format($number / 100000, $decimals, '.', '');
        }
        
        return 0;
    }
    
}
