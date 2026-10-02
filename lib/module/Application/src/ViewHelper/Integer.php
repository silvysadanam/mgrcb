<?php
/**
 * Integer View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Integer extends ViewHelper
{
   /**
    * get formatted integer
    * 
    * @param string $number
    * @param boolean $blankForZero
    * @param string $blank
    * @param boolean $format
    * 
    * @return string
    */
    public function integer($number, $blankForZero = true, $blank = '--', $format = false)
    {
        if (intval($number) < 1) {
            if ($blankForZero) {
                return $blank;
            }
            
            return '0';
        }
        
        if ($format) {
            return $this->_number($number);
        }
        
        return intval($number);
    }
    
    /**
     * format integer part
     * 
     * @param string $number
     * @param string $separator
     * @return string
     */
    private function _number($number, $separator = ',')
    {
        $integer = (int) $number;
        if ('' == $separator) {
            return $integer;
        }
        if (strlen($number) < 4) {
            return $integer;
        }
        $parts = [];
        $last = substr($number, -3);
        $next = substr($number, 0, strlen($number) - 3);
        $length = strlen($next);
        if ($length > 2) {
            $reversed = strrev($next);
            $ordered = str_split($reversed, 2);
            $list = array_reverse($ordered);
            foreach ($list as $item) {
                if (strlen($item) > 1) {
                    $parts[] = strrev($item);
                } else {
                    $parts[] = $item;
                }
            }
        } else {
            $parts[] = $next;
        }
        $parts[] = $last;

        return implode($separator, $parts);
    }
    
}
