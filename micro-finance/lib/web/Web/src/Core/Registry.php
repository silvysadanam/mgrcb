<?php
/**
 * Web Core Registry
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;


class Registry
{
    /**
     * registry configs
     * 
     * @var array 
     */
    private static $_configs = [];
    
    /**
     * set config(s)
     * 
     * @param string|array $name
     * @param mixed $value 
     */
    public static function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                self::$_configs[$key] = $value;
            }
        } else {
            self::$_configs[$name] = $value;
        }
    }
    
    /**
     * get config value
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed 
     */
    public static function get($name, $default = null)
    {
        if (isset(self::$_configs[$name])) {
            $value = self::$_configs[$name];
        } else {
            $value = $default;
        }
        return $value;
    }
    
    /**
     * get application configs
     * 
     * @return array
     */
    public static function application($key = null)
    {
        if (null === $key) {
            return self::get('application');
        }
        $application = self::get('application');
        if (is_array($key)) {
            if (isset($application[$key[0]])) {
                if (isset($key[1])) {
                    if (isset($application[$key[0]][$key[1]])) {
                        return $application[$key[0]][$key[1]];
                    }
                }
                return $application[$key[0]];
            }
        } elseif (isset($application[$key])) {
            return $application[$key];
        }
        
        return null;
    }
    
    /**
     * get database config
     * 
     * @return array|boolean
     */
    public static function database()
    {
        $application = self::get('application');
        if (! empty($application)) {
            if (isset($application['database'])) {
                return $application['database'];
            }
        }
        
        return false;
    }
    
    /**
     * get module config
     * 
     * @return array|boolean
     */
    public static function module($key)
    {
        $module = self::get('module');
        if (! empty($module)) {
            if (isset($module[$key])) {
                return $module[$key];
            }
        }
        
        return null;
    }
    
    /**
     * clear config
     * 
     * @param string $name
     * @return boolean 
     */
    public static function clear($name)
    {
        if (isset(self::$_configs[$name])) {
            unset(self::$_configs[$name]);
        }
        
        return true;
    }
    
    /**
     * get config dynamically
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed 
     */
    public static function __callStatic($name, $arguments)
    {
        $value = false;
        if (1 == preg_match('/^get[A-Z][A-Za-z]+/', $name)) {
            $name = substr($name, 3);
        }
        if (isset(self::$_configs[$name])) {
            $value = self::$_configs[$name];
        } else {
            $name = lcfirst($name);
            if (isset(self::$_configs[$name])) {
                $value =  self::$_configs[$name];
            } else {
                $name = ucfirst($name);
                if (isset(self::$_configs[$name])) {
                    $value = self::$_configs[$name];
                }
            }
        }
        if (is_array($value)) {
            if (! empty($arguments)) {
                if (isset($value[$arguments[0]])) {
                    $value = $value[$arguments[0]];
                }
            }
        }
        
        return $value;
    }
    
}
