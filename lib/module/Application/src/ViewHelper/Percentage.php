<?php
/**
 * Percentage View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Percentage extends ViewHelper
{
    /**
     * get percentage
     * 
     * @param int $part
     * @param int $total
     * @param int $decimals
     * @return int|string
     */
    public function percentage($part, $total, $decimals = 0)
    {
        if ($total < 1 || $part < 1) {
            return 0;
        }
        
        $fraction = 100 * ($part / $total);
        if (0 == $decimals) {
            return intval($fraction);
        }
        
        return sprintf('%0.' . $decimals . 'f', $fraction);
    }
    
}
