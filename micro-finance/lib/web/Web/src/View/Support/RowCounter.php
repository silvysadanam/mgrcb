<?php
/**
 * Web View Support RowCounter
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\Support
 */

namespace Web\View\Support;


class RowCounter
{
    /**
     *
     * @var int 
     */
    private static $_offset = 0;
    
    /**
     * get counter
     * 
     * @return int
     */
    public static function get()
    {
        ++self::$_offset;
        return self::$_offset;
    }
    
    /**
     * set offset
     * 
     * @param int $offset
     */
    public static function set($offset)
    {
        self::$_offset = (int) $offset;
    }
    
    /**
     * reset counter
     */
    public static function reset()
    {
        self::$_offset = 0;
    }
            
}
