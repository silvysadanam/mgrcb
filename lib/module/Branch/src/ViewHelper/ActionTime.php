<?php
/**
 * Branch ActionTime View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class ActionTime extends ViewHelper
{
    /**
     * get action time
     * 
     * @param string $time
     * @return string
     */
    public function actionTime($time = null)
    {
        $format = 'd/m/Y H:i';
        
        return DateHelper::format($format, $time);
    }
    
}
