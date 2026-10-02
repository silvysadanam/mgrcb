<?php
/**
 * AmountWords View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;

use Shared\Utility\AmountToWords;


class AmountWords extends ViewHelper
{
   /**
    * get amount in words
    * 
    * @param string $amount
    * @return string
    */
    public function amountWords($amount)
    {
        $words = new AmountToWords(true);
        
        return $words->convert($amount);
    }

}
