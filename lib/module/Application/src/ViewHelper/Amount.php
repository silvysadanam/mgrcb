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
    * @param boolean $suffix 
    * @return string
    */
    public function amount($amount, $suffix = false)
    {
        $formatted = number_format($amount, 0, '.', ',');
        if ($suffix) {
            $formatted  .= ' INR';
        }
        
        return $formatted;
    }

}
