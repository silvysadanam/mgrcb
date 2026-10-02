<?php
/**
 * Shared Malayalam Utility
 *
 * @category   Shared
 * @package    Shared\Utility
 */

namespace Shared\Utility;

class Malayalam
{
    
    private static $_store = [];

    /**
     * get malayalam translation
     * 
     * @param string $string
     * @return string
     */
    public static function get($string)
    {
        $key = self::_key($string);
        if (isset(self::$_store[$key])) {
            return self::$_store[$key];
        }
        
        $translated = self::_find($key);
        
        if ('' == $translated) {
            self::$_store[$key] = $string;
            return $string;
        }
        
        self::$_store[$key] = $translated;
        
        return $translated;
    }
    
    private static function _key($title)
    {
        $key = trim(strtolower($title));
        
        return preg_replace('/\s+/', '_', $key);
    }
    
    private function _find($key)
    {
        switch ($key) {
            case '';
                $translated = '';
                break;
            
            default:
                $translated = '';
                break;
        }
        
        return $translated;
    }
    
}
