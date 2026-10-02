<?php
/**
 * Backend Datepicker View helper
 * 
 * @category   Backend
 * @package    Backend\ViewHelper
 */

namespace Backend\ViewHelper;

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
