<?php
/**
 * Amount View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Amount extends ViewHelper
{
   /**
    * get formatted amount
    * 
    * @param string $amount
    * @param int $decimals
    * @param boolean $suffix 
    * @return string
    */
    public function amount($amount, $decimals = 2, $suffix = false)
    {
        $formatted = number_format($amount, $decimals, '.', '');
        if ($suffix) {
            $formatted  .= ' INR';
        }
        
        return $formatted;
    }

}
