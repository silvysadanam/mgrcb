<?php
/**
 * Web Mvc AjaxResponse Handler
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Mvc\View;
use Web\View\ViewModel;


class AjaxResponse
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const VALID = 'valid';
    const REDIRECT = 'redirect';
    
    /**
     *
     * @var int 
     */
    private $_responseType;
    
    /**
     *
     * @var string|array 
     */
    private $_data;
    
    /**
     *
     * @var \Web\View\ViewModel 
     */
    private $_view;
    
    /**
     * Constructor
     */
    public function __construct(ViewModel $viewModel, $responseType = 'json')
    {
        $this->_view = $viewModel;
        if ('json' == $responseType) {
            $this->_responseType = View::RENDERER_JSON;
            $this->_data = [
                self::SUCCESS => true,
                self::VALID => 0,
                self::MESSAGE => '',
            ];
        } else {
            $this->_data = '';
            $this->_responseType = View::RENDERER_HTML;
        }
        $this->_view->setRenderer($this->_responseType);
        $this->_setResponse();
    }
    
    /**
     * set response data
     * 
     * @param string|array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->_data = $data;
        $this->_setResponse();
        
        return $this;
    }
    
    /**
     * add data
     * 
     * @param string|array $data
     * @param string $name
     * @return $this
     */
    public function addData($data, $name = null)
    {
        if (View::RENDERER_JSON == $this->_responseType && null !== $name) {
            $this->_addJson($name, $data);
        } else {
            $this->_data .= $data;
        }
        $this->_setResponse();
        
        return $this;
    }
    
    /**
     * set success response
     * 
     * @return $this
     */
    public function success()
    {
        if (View::RENDERER_JSON == $this->_responseType) {
            $this->_addJson(self::SUCCESS, true);
        }
        
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
        $this->_addJson(self::MESSAGE, $message);
        
        return $this;
    }
    
    /**
     * set response valid
     * 
     * @return $this
     */
    public function valid()
    {
        $this->_addJson(self::VALID, 1);
        
        return $this;
    }
    
    /**
     * set redirect on response
     * 
     * @return $this
     */
    public function redirect()
    {
        $this->_addJson(self::REDIRECT, 1);
        
        return $this;
    }
    
    
    /**
     * add json parameter
     * 
     * @param string $name
     * @param mixed $value
     */
    private function _addJson($name, $value)
    {
        $this->_data[$name] = $value;
        $this->_setResponse();
    }
    
    /**
     * set response to view
     */
    private function _setResponse()
    {
        $this->_view->setResponse($this->_data);
    }
    
}
