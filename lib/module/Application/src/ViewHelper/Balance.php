<?php
/**
 * Balance View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Balance extends ViewHelper
{
    /**
     * get balance amount
     * 
     * @param int $price
     * @param int $advance
     * @param boolean $blankForZero
     * @param string $blank
     * @return int|string
     */
    public function balance($price, $advance = 0, $blankForZero = true, $blank = '')
    {
        $balance = $price - $advance;
        if ($balance > 0) {
            return $balance;
        }

        if ($blankForZero) {
            return $blank;
        }
            
        return '0';
    }
    
}
