<?php
/**
 * Cash View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Cash extends ViewHelper
{
   /**
    * get formatted cash
    * 
    * @param string $number
    * @param boolean $blankForZero
    * @param string $blank
    * 
    * @return string
    */
    public function cash($number, $blankForZero = true, $blank = '')
    {
        if (intval($number) < 1) {
            if ($blankForZero) {
                return $blank;
            }
            
            return '0';
        }

        return intval($number);
    }
    
}
