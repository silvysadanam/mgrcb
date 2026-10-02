<?php
/**
 * Web View Support LookupStore
 * 
 * @category   Web
 * @package    Web\View
 * @subpackage Web\View\Support
 */

namespace Web\View\Support;


class LookupStore
{
    /**
     *
     * @var array 
     */
    private static $_lookups = [];
    
    /**
     * find lookup value
     * 
     * @return string
     */
    public static function find($name, $id = 0)
    {
        if (isset(self::$_lookups[$name])) {
            if (isset(self::$_lookups[$name][$id])) {
                return self::$_lookups[$name][$id];
            }
        }
        
        return null;
    }
    
    /**
     * register lookup
     * 
     * @param string $name
     * @param array $value
     */
    public static function register($name, $value)
    {
        self::$_lookups[$name] = $value;
    }
    
    /**
     * get lookup by name
     * 
     * @param string $name
     * @return array
     */
    public static function get($name)
    {
        if (isset(self::$_lookups[$name])) {
            return self::$_lookups[$name];
        }
        
        return [];
    }
    
    /**
     * check lookup registered
     * 
     * @param string $name
     * @return boolean
     */
    public static function isRegistered($name)
    {
        return isset(self::$_lookups[$name]);
    }
    
    /**
     * check id in lookup
     * 
     * @param string $name
     * @param int $id
     * @return boolean
     */
    public static function hasId($name, $id)
    {
        if (isset(self::$_lookups[$name])) {
            return isset(self::$_lookups[$name][$id]);
        }
        
        return false;
    }
    
    /**
     * check value in lookup
     * 
     * @param string $name
     * @param string $value
     * @return boolean
     */
    public static function inList($name, $value)
    {
        if (isset(self::$_lookups[$name])) {
            return in_array($value, self::$_lookups[$name]);
        }
        
        return false;
    }
            
}
