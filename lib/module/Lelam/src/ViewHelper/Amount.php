<?php
/**
 * Amount View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;


class Amount extends ViewHelper
{
   /**
    * get formatted amount
    * 
    * @param string $amount
    * @param boolean $suffix 
    * @return string
    */
    public function amount($amount, $suffix = true)
    {
        $formatted = number_format($amount, 0, '.', ',');
        if ($suffix) {
            $formatted  .= ' AED';
        }
        
        return $formatted;
    }

}
