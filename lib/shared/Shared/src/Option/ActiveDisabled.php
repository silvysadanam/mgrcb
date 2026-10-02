<?php
/**
 * Shared Option ActiveDisabled
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;


class ActiveDisabled
{
    /**
     * get options
     * 
     * @param boolean $disabledFirst
     * @return array
     */
    public static function get($disabledFirst = true)
    {
        $options = [];
        if ($disabledFirst) {
            $options[] = 'Disabled';
            $options[] = 'Active';
        } else {
            $options[1] = 'Active';
            $options[0] = 'Disabled';
        }
        
        return $options;
    }
    
}
