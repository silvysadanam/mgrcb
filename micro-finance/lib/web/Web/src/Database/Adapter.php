<?php
/**
 * Database Adapter
 * 
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use Web\Exception;


class Adapter
{
    const DEFAULT_ADAPTER = 'Default';
    const ADAPTER = 'Adapter';
    
    private static $_default = null;
    
    /**
     * load adapter
     * 
     * @param mixed $adapter
     * @return object
     * @throws Exception
     */
    public static function load($adapter = null)
    {
        if (null !== $adapter) {
            if (is_object($adapter)) {
                return $adapter;
            }
            if (class_exists($adapter)) {
                return $adapter::getAdapter();
            }
        }
        $class = self::_getAdapterClass($adapter);
        if (class_exists($class)) {
            return $class::getAdapter();
        }
        
        throw new Exception('Adapter - "' . $adapter . ' : ' . $class . '" not found');
    }
    
    /**
     * set default adapter
     * 
     * @param string $name
     */
    public static function setDefaultAdapter($name)
    {
        self::$_default = $name;
    }
    
    
    /**
     * get adapter class name
     * 
     * @param string $name
     * @return string
     */
    private static function _getAdapterClass($name)
    {
        if (null === $name) {
            if (null !== self::$_default) {
                $name = self::$_default;
            } else {
                $name = self::DEFAULT_ADAPTER;
            }
        }
        if (false === strpos($name, self::ADAPTER)) {
            $name .= self::ADAPTER;
        }
        
        return __NAMESPACE__ . BS . self::ADAPTER . BS . ucfirst($name);
    }
    
}
