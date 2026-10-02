<?php
/**
 * Shared Option YesNo
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;


class YesNo
{
    /**
     * get options
     * 
     * @param boolean $noFirst
     * @return array
     */
    public static function get($noFirst = true)
    {
        $options = [];
        if ($noFirst) {
            $options[] = 'No';
            $options[] = 'Yes';
        } else {
            $options[1] = 'Yes';
            $options[0] = 'No';
        }
        
        return $options;
    }
    
    /**
     * get options with selectable option
     * 
     * @return array
     */
    public static function selectable()
    {
        $options = [
            '--select--', 'Yes', 'No',
        ];
        
        return $options;
    }
    
}
