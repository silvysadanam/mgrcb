<?php
/**
 * Web Mvc RestRequest Handler
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Core\Request;


class RestRequest
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const VALID = 'valid';
    
    /**
     *
     * @var \stdClass 
     */
    private $_request;
    
    /**
     * 
     * @var string
     */
    private $_body;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_request = new \stdClass();
        if (Request::isPost()) {
            $this->_body = trim(file_get_contents('php://input'));
            if (! empty($this->_body)) {
                if (null !== ($json = json_decode($this->_body, false))) {
                    if (JSON_ERROR_NONE === json_last_error()) {
                        $this->_request = $json;
                    }
                }
            }
        }
    }
    
    /**
     * check request parameter
     * 
     * @param string $key
     * @return boolean
     */
    public function is($key)
    {
        return isset($this->_request->{$key});
    }
    
    /**
     * get parameter by name
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->_request->{$key})) {
            return $this->_request->{$key};
        }
    }
    
    /**
     * get body
     * 
     * @return string
     */
    public function body()
    {
        return $this->_body;
    }
    
    /**
     * get full request data
     * 
     * @return \stdClass
     */
    public function json()
    {
        return $this->_request;
    }
    
    /**
     * check is valid request
     * 
     * @return boolean
     */
    public function valid()
    {
        return ! empty($this->_request);
    }
    
}
