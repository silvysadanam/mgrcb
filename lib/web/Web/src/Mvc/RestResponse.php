<?php
/**
 * Web Mvc RestResponse Handler
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;


class RestResponse
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const VALID = 'valid';
    
    /**
     *
     * @var string|array 
     */
    private $_data;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_data = [
            self::SUCCESS => true,
            self::VALID => 0,
            self::MESSAGE => '',
        ];
    }
    
    /**
     * set response data
     * 
     * @param string|array $data
     * @return $this
     */
    public function set($data)
    {
        $this->_data = $data;
        
        return $this;
    }
    
    /**
     * add data
     * 
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function add($name, $value)
    {
        $this->_add($name, $value);
        
        return $this;
    }
    
    /**
     * set success response
     * 
     * @return $this
     */
    public function success()
    {
        $this->_add(self::SUCCESS, true);
        
        return $this;
    }
    
    /**
     * set response message
     * 
     * @param string $message
     * @return $this
     */
    public function message($message)
    {
        $this->_add(self::MESSAGE, $message);
        
        return $this;
    }
    
    /**
     * set response valid
     * 
     * @return $this
     */
    public function valid()
    {
        $this->_add(self::VALID, 1);
        
        return $this;
    }
    
    /**
     * get response data
     * 
     * @return array
     */
    public function get()
    {
        return $this->_data;
    }
    
    
    /**
     * add json parameter
     * 
     * @param string $name
     * @param mixed $value
     */
    private function _add($name, $value)
    {
        $this->_data[$name] = $value;
    }
    
}
