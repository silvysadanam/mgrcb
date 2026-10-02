<?php
/**
 * Web Mvc Module
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Core\Registry;


class Module
{
    const VIEW = 'view';
    
    /**
     *
     * @var array 
     */
    private static $_modules = [];
    
    /**
     *
     * @var string 
     */
    private static $_current = '';
    
    /**
     *
     * @var boolean 
     */
    private static $_default = true;
    
    /**
     *
     * @var string 
     */
    private static $_namespace = '';
    
    /**
     *
     * @var string 
     */
    private static $_defaultRoute = '';
    
    /**
     *
     * @var string 
     */
    private static $_viewPath = '';

    /**
     * initialize
     */
    public static function init()
    {
        $modules = Registry::get('modules');
        if (! empty($modules)) {
            foreach ($modules as $namespace => $route) {
                self::$_modules[$namespace] = $route;
                if ('' == $route) {
                    self::$_namespace = $namespace;
                    self::$_current = $route;
                    self::$_defaultRoute = $route;
                    Registry::set('module', $route);
                }
            }
        }
    }
    
    /**
     * check module name
     * 
     * @param string $name
     * @return boolean
     */
    public static function isModuleName($name)
    {
        return in_array($name, self::$_modules);
    }
    
    /**
     * set module by name
     * 
     * @param string $name
     */
    public static function setModuleByName($name)
    {
        if (self::isModuleName($name)) {
            self::$_current = $name;
            self::$_namespace = array_search($name, self::$_modules);
            if ($name != self::$_defaultRoute) {
                self::$_default = false;
            }
        }
    }
    
    /**
     * get module name
     * 
     * @return string
     */
    public static function getModuleName()
    {
        return self::$_current;
    }
    
    /**
     * get module namespace
     * 
     * @return string
     */
    public static function getModuleNamespace()
    {
        return self::$_namespace;
    }
    
    /**
     * check if default module
     * 
     * @return boolean
     */
    public static function isDefault()
    {
        return self::$_default;
    }
    
    /**
     * check if module
     * 
     * @return boolean
     */
    public static function isModule()
    {
        return ! self::$_default;
    }

    /**
     * check module namespace
     * 
     * @param string $namespace
     * @return boolean
     */
    public static function isModuleNamespace($namespace)
    {
        return isset(self::$_modules[$namespace]);
    }

    /**
     * get module name by namespace
     * 
     * @param string $namespace
     * @return string
     */
    public static function getModuleByNamespace($namespace)
    {
        if (self::isModuleNamespace($namespace)) {
            return self::$_modules[$namespace];
        }
        
        return null;
    }
    
    /**
     * get view path
     * 
     * @return string
     */
    public static function getViewPath()
    {
        if ('' == self::$_viewPath) {
            $view = self::VIEW;
            $module = Registry::get('module');
            if (! empty($module)) {
                if (isset($module['view'])) {
                    $view = trim($module['view'], DS);
                }
            }
            self::$_viewPath = MODULE_DIR . DS . self::$_namespace . DS . $view;
        }
        
        return self::$_viewPath;
    }
    
    /**
     * get view path by namespace
     * 
     * @return string|boolean
     */
    public static function getViewPathByNamespace($namespace)
    {
        if (self::isModuleNamespace($namespace)) {
            return MODULE_DIR . DS . $namespace . DS . self::VIEW;
        }
        
        return false;
    }
    
    /**
     * get view path by module name
     * 
     * @return string|boolean
     */
    public static function getViewPathByModuleName($name)
    {
        if (self::isModuleName($name)) {
            $namespace = array_search($name, self::$_modules);
            return self::getViewPathByNamespace($namespace);
        }
        
        return false;
    }
    
}
