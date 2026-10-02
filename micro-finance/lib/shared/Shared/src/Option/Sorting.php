<?php
/**
 * Shared Option Sorting
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;


class Sorting
{
    /**
     * get options
     * 
     * @param boolean $useShortForm
     * @return array
     */
    public static function get($useShortForm = false)
    {
        if ($useShortForm) {
            return ['Asc', 'Desc'];
        }
        
        return ['Ascending', 'Descending'];
    }
    
}
