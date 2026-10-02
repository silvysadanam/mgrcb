<?php
/**
 * Shared Option EnabledDisabled
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;


class EnabledDisabled
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
            $options[] = 'Enabled';
        } else {
            $options[1] = 'Enabled';
            $options[0] = 'Disabled';
        }
        
        return $options;
    }
    
}
