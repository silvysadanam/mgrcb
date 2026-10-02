<?php
/**
 * Lelam Datepicker View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class Datepicker extends ViewHelper
{
    /**
     * get date picker formatted date
     * 
     * @param string $date
     * @return string
     */
    public function datepicker($date = '')
    {
        if (! empty($date)) {
            return DateHelper::datepicker($date);
        }
        
        return '';
    }
    
}
