<?php
/**
 * Branch UserTime View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class UserTime extends ViewHelper
{
    /**
     * get user time
     * 
     * @param string $time
     * @return string
     */
    public function userTime($time = null)
    {
        $format = 'd/m/Y h:i:s A';
        
        return DateHelper::format($format, $time);
    }
    
}
