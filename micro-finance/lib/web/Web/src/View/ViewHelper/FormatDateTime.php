<?php
/**
 * Format DateTime View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class FormatDateTime extends ViewHelper
{
    /**
     * format date/time
     * 
     * @param string $format
     * @param int|string $dateTime
     * @return string
     */
    public function formatDateTime($format, $dateTime = null)
    {
        return DateHelper::format($format, $dateTime);
    }
    
}
