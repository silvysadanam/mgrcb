<?php
/**
 * Amount Timestamp helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class Timestamp extends ViewHelper
{
   /**
    * get timestamp
    * 
    * @param string $time
    * @return string
    */
    public function timestamp($time)
    {
        return DateHelper::timestamp($time);
    }

}
