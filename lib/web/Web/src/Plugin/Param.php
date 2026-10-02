<?php
/**
 * Plugin Params
 *
 * @category   Web
 * @package    Web\Plugin
 */

namespace Web\Plugin;

use Web\Core\Registry;


class Param
{
    const PARAM_KEY = 'params';
    
    /**
     *
     * @var array 
     */
    private $_params;
    
    public function __construct()
    {
        $this->_params = Registry::get(self::PARAM_KEY);
    }

    /**
     * get param
     * 
     * @param string $name
     * @return string
     */
    public function get($name)
    {
        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        }
        
        return null;
    }
    
    /**
     * get all params
     * 
     * @return array
     */
    public function getAll()
    {
        return $this->_params;
    }
    
    /**
     * get param as id
     * 
     * @param string $name
     * @return int
     */
    public function getId($name)
    {
        return (int) $this->get($name);
    }
    
    /**
     * has param
     * 
     * @param string $name
     * @return boolena
     * 
     */
    public function hasParam($name)
    {
        return isset($this->_params[$name]);
    }
    
    /**
     * has param value
     * 
     * @param string $name
     * @return boolean
     */
    public function hasValue($name)
    {
        if (isset($this->_params[$name])) {
            return ! empty($this->_params[$name]);
        }
        
        return false;
    }
    
}
