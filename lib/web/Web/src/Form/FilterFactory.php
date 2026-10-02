<?php
/**
 * Form filter Factory
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Exception;


class FilterFactory
{
    /**
     * load filter class
     * 
     * @param string $name
     * @return \Web\Form\class
     * @throws Exception
     */
    public static function load($name)
    {
        $class = self::_getClass($name);
        if (class_exists($class)) {
            return new $class;
        }
        throw new Exception('"' . $class . '" not found');
    }
    
    /**
     * check filter class
     * 
     * @param string $name
     * @return boolean
     */
    public static function is($name)
    {
        return class_exists(self::_getClass($name));
    }
    
    /**
     * get filter class name
     * 
     * @param string $name
     * @return string
     */
    private static function _getClass($name)
    {
        return __NAMESPACE__ . BS . 'Filter' . BS . ucfirst($name);
    }
    
}
