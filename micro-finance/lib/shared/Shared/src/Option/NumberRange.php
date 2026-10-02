<?php
/**
 * Shared Option NumberRange
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;


class NumberRange
{
    /**
     * get options
     * 
     * @param int $end
     * @param int $start
     * @param boolean $ascending
     * @param int $step
     * @return array
     */
    public static function get($end, $start = 1, $ascending = true, $step = 1)
    {
        $list = range($start, $end, $step);
        if (! $ascending) {
            $list = array_reverse($list);
        }
        
        return array_combine($list, $list);
    }
    
}
