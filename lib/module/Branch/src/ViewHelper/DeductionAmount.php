<?php
/**
 * DeductionAmount View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;


class DeductionAmount extends ViewHelper
{
   /**
    * get formatted amount
    * 
    * @param string $amount
    * @return string
    */
    public function deductionAmount($amount)
    {
        if (0 == $amount) {
            return '';
        }
        
        return $amount;
    }

}
