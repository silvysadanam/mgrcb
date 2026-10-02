<?php
/**
 * Shared Option Month
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;

use Web\Utility\DateHelper;


class Month
{
    /**
     * get options
     * 
     * @param string $format
     * @return array
     */
    public static function get($format = 'F')
    {
        $options = [];
        $date = DateHelper::getDateTime();
        $date->setDate(DateHelper::year(), 1, 15);
        for ($count = 1; $count < 13; $count++) {
            $options[$count] = $date->format($format);
            $date->modify('+1 month');
        }
        
        return $options;
    }
    
}
