<?php
/**
 * Decimal View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Decimal extends ViewHelper
{
   /**
    * get formatted decimal
    * 
    * @param string $number
    * @param int $decimals
    * @param boolean $formatInteger
    * @param boolean $international
    * @return string
    */
    public function decimal($number, $decimals = 2, $formatInteger = true, $international = true)
    {
        if ($decimals < 1) {
            if ($formatInteger) {
                if ($international) {
                    return number_format($number, 0, '', ',');
                }
                return $this->_number($number);
            }
            return (int) $number;
        }
        if ($formatInteger && $international) {
            return number_format($number, $decimals, '.', ',');
        }
        $float = number_format($number, $decimals, '.', '');
        if (! $formatInteger) {
            return $float;
        }
        if ($international) {
            return number_format($number, 0, '.', ',');
        }
        $parts = explode('.', $float);
        $integerPart = $this->_number($parts[0]);
        
        return $integerPart . '.' . $parts[1];
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
