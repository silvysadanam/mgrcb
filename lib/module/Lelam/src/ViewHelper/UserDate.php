<?php
/**
 * UserDate View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class UserDate extends ViewHelper
{
    /**
     * get user date
     * 
     * @param string|int $time
     * @param string $format
     * @return string
     */
    public function userDate($time = null, $format = 'd/m/Y')
    {
        return DateHelper::format($format, $time);
    }
    
}
