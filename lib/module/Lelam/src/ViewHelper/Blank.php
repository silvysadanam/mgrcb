<?php
/**
 * Blank View helper
 * 
 * @category   Lelam
 * @package    Lelam\ViewHelper
 */

namespace Lelam\ViewHelper;

use Web\View\ViewHelper;


class Blank extends ViewHelper
{
   /**
    * get formatted text with blank placeholder
    * 
    * @param string $string
    * @param string $blank
    * @return string
    */
    public function blank($string, $blank = '--')
    {
        if (strlen($string) > 0) {
            return $this->escape($string);
        }
        
        return $blank;
    }
    
}
