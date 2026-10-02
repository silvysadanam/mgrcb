<?php
/**
 * Application DateIndex View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class DateIndex extends ViewHelper
{
    /**
     * get date index number
     * 
     * @param string $date
     * @return string
     */
    public function dateIndex($date)
    {
        return DateHelper::format('Ymd', $date);
    }
    
}
