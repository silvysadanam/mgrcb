<?php
/**
 * InstalmentNumber View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class InstalmentNumber extends ViewHelper
{
    const BLANK_FOR_ZERO = 0;
    
    /**
     * format instalment number\
     * @param int $number
     * @return int|string
     */
    public function instalmentNumber($number)
    {
        if (0 == self::BLANK_FOR_ZERO) {
            return $number;
        }
        
        if ($number > 0) {
            return $number;
        }
        
        return '';
    }
    
}
