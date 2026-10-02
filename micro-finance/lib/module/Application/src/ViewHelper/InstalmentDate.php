<?php
/**
 * InstalmentDate View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class InstalmentDate extends ViewHelper
{
    /**
     * get instalment date
     * 
     * @param string $date
     * @return string
     */
    public function instalmentDate($date)
    {
        return DateHelper::format('j M y', $date);
    }
    
}
