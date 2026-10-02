<?php
/**
 * Number View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Number extends ViewHelper
{
    /**
     * get formatted number
     * 
     * @param string $number
     * @param string $separator
     * @param boolean $international
     * @return string
     */
    public function number($number, $separator = ',', $international = true)
    {
        $integer = (int) $number;
        if ('' == $separator) {
            return $integer;
        }
        if (strlen($number) < 4) {
            return $integer;
        }
        if ($international) {
            return number_format($number, 0, '', ',');
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
