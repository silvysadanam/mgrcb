<?php
/**
 * Currency View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;


class Currency extends ViewHelper
{
   /**
    * get formatted amount
    * 
    * @param string $amount
    * @param int $decimals
    * @return string
    */
    public function currency($amount, $decimals = 2)
    {
        $separated = explode('.', $amount);
        
        $integer = (int) $separated[0];
        if (strlen($integer) < 6) {
            return number_format($amount, $decimals, '.', ',');
        }
        
        if (1 == count($separated)) {
            $separated[1] = '00';
        }
        
        $parts = [];
        $last = substr($integer, -3);
        $next = substr($integer, 0, strlen($integer) - 3);
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
        
        if (0 == $decimals) {
            return implode(',', $parts);
        }

        return implode(',', $parts) . '.' . $separated[1];
    }

}
