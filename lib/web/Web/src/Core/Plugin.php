<?php
/**
 * Web Core Plugin
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;

use Web\Mvc\Module;


class Plugin
{
    const FLASH = 'flash';
    const HEAD = 'head';
    const SCRIPT = 'script';
    const STYLE = 'style';
    const TAB = 'tab';
    const REDIRECT = 'redirect';
    const PAGING = 'paging';
    const BREADCRUMB = 'breadcrumb';
    const DEBUG = 'debug';
    const THEME = 'theme';
    const MESSAGE = 'message';
    
    /**
     *
     * @var array 
     */
    private $_plugins;

    public function __construct($custom = [])
    {
        $this->_plugins = [];
        $default = [
            self::FLASH,
            self::TAB,
            self::REDIRECT,
            self::HEAD,
            self::SCRIPT,
            self::STYLE,
            self::BREADCRUMB,
            self::DEBUG,
            self::THEME,
            self::MESSAGE,
        ];
        if (! empty($custom)) {
            $default = array_unique(array_merge($default, $custom));
        }
        if (! empty($default)) {
            $this->register($default);
        }
    }
    
    /**
     * get plugin
     * 
     * @param string $name
     * @return \Application\Plugin\*
     */
    public function get($name)
    {
        if (isset($this->_plugins[$name])) {
            return $this->_plugins[$name];
        }
        
        return false;
    }
    
    /**
     * register plugin
     * 
     * @param string $name
     * @return $this
     */
    public function register($name)
    {
        if (is_array($name)) {
            foreach ($name as $item) {
                $this->_register($item);
            }
        } else {
            $this->_register($name);
        }
        
        return $this;
    }
    
    /**
     * discard plugin
     * 
     * @param string $name
     * @return $this
     */
    public function discard($name)
    {
        if (is_array($name)) {
            foreach ($name as $item) {
                $this->_unregister($item);
            }
        } else {
            $this->_unregister($name);
        }
        
        return $this;
    }
    
    /**
     * check if plugin is registered
     * 
     * @param string $name
     * @return boolean
     */
    public function isRegistered($name)
    {
        return isset($this->_plugins[$name]);
    }
    
    /**
     * register plugin
     * 
     * @param string $name
     */
    private function _register($name)
    {
        if (! isset($this->_plugins[$name])) {
            if (false !== ($class = $this->_findPlugin($name))) {
                $this->_plugins[$name] = new $class;
            }
        }
    }
    
    /**
     * unregister plugin
     * 
     * @param string $name
     */
    private function _unregister($name)
    {
        if (isset($this->_plugins[$name])) {
            unset($this->_plugins[$name]);
        }
    }
    
    /**
     * find plugin 
     * 
     * @param string $name
     * @return boolean|string
     */
    private function _findPlugin($name)
    {
        $pluginName = ucfirst($name);
        $pluginClass = BS . Module::getModuleNamespace() . BS . 'Plugin' . BS . $pluginName;
        if (class_exists($pluginClass)) {
            return $pluginClass;
        } else {
            $pluginClass = BS . 'Web' . BS . 'Plugin' . BS . $pluginName;
            if (class_exists($pluginClass)) {
                return $pluginClass;
            }
        }
        
        return false;
    }
    
}
