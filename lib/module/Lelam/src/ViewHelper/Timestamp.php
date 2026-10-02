<?php
/**
 * Timestamp View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class Timestamp extends ViewHelper
{
    /**
     * get timestamp
     * 
     * @param string|int $time
     * @return string
     */
    public function timestamp($time = null)
    {
        return DateHelper::timestamp($time);
    }
    
}
