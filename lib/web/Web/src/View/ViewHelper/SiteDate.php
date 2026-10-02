<?php
/**
 * Site Date View helper
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\ViewHelper
 */

namespace Web\View\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class SiteDate extends ViewHelper
{
    /**
     * format site date
     * 
     * @param int|string $dateTime
     * @return string
     */
    public function siteDate($dateTime = null)
    {
        return DateHelper::format(DateHelper::DATEPICKER, $dateTime);
    }
    
}
