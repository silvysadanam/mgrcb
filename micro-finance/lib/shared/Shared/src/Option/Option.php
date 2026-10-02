<?php
/**
 * Shared Option Option
 * 
 * @category Shared
 * @package  Shared\Option
 */

namespace Shared\Option;

use Shared\Option\{YesNo, EnabledDisabled, ActiveDisabled, Sorting, NumberRange};


class Option
{
    /**
     * get yes or no options
     * 
     * @return array
     */
    public static function yesOrNo()
    {
        return YesNo::get(false);
    }
    
    /**
     * get no or yes options
     * 
     * @return array
     */
    public static function noOrYes()
    {
        return YesNo::get();
    }
    
    /**
     * get yes or no options with selectable option
     * 
     * @return array
     */
    public static function yesOrNoSelectable()
    {
        return YesNo::selectable();
    }
    
    /**
     * get enabled or disabled options
     * 
     * @return array
     */
    public static function enabledOrDisabled()
    {
        return EnabledDisabled::get();
    }
    
    /**
     * get active or disabled options
     * 
     * @return array
     */
    public static function activeOrDisabled()
    {
        return ActiveDisabled::get();
    }
    
    /**
     * get sorting ascending and descending options
     * 
     * @return array
     */
    public static function sorting()
    {
        return Sorting::get();
    }
    
    public static function yearBuilt($start, $end)
    {
        return NumberRange::get($end, $start, false);
    }
    
}
