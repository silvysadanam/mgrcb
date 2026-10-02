<?php
/**
 * DeductionMonth View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class DeductionMonth extends ViewHelper
{
   /**
    * get formatted amount
    * 
    * @param string $date
    * @return string
    */
    public function deductionMonth($date)
    {
        return DateHelper::format('F Y', $date);
    }

}
