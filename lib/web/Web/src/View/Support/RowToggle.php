<?php
/**
 * Web View Support RowToggle
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\Support
 */

namespace Web\View\Support;


class RowToggle
{
    const TOGGLE = ' class="%s"';
    
    /**
     *
     * @var boolean 
     */
    private static $_odd = true;
    
    /**
     * get toggle class
     * 
     * @return int
     */
    public static function get()
    {
        if (self::$_odd) {
           $class = 'odd';
           self::$_odd = false;
        } else {
            $class = 'even';
            self::$_odd = true;
        }
        
        return sprintf(self::TOGGLE, $class);
    }

    /**
     * reset toggle
     */
    public static function reset()
    {
        self::$_odd = true;
    }
            
}
