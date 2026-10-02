<?php
/**
 * Percentage View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

use Web\View\ViewHelper;


class Percentage extends ViewHelper
{
    /**
     * get percentage
     * 
     * @param int $number
     * @param int $total
     * @return string
     */
    public function percentage($number, $total, $decimals = 1, $appendSymbol = true)
    {
        $percentage = 0;
        if ($number > 0 && $total > 0) {
            $percentage = 100 * ($number / $total);
        }
        
        $output = number_format($percentage, $decimals, '.', '');
        if ($appendSymbol) {
            $output .= '%';
        }
        
        return $output;
    }
    
}
