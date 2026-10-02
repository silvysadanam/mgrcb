<?php
/**
 * Web Rest Controller
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Mvc\View;
use Web\View\ViewModel;
use Web\Core\Registry;
use Web\Mvc\RestResponse;
use Web\Core\Request;
use Web\Database\Adapter;
use Web\Mvc\RestRequest;


class RestController
{
    /**
     *
     * @var \Web\View\ViewModel
     */
    protected $view;
    
    /**
     *
     * @var \Web\Mvc\View
     */
    protected $layout;
    
    /**
     * 
     * @var \Web\Mvc\RestResponse
     */
    protected $response;
    
    /**
     * 
     * @var \Web\Mvc\RestRequest
     */
    protected $request;
    
    /**
     * Constructor
     */
    public function __construct($defaultAdapter = null)
    {
        if (null !== $defaultAdapter) {
            $this->setDefaultAdapter($defaultAdapter);
        }
        $this->layout = View::getInstance();
        $this->view = new ViewModel(View::RENDERER_JSON);
        $this->response = new RestResponse();
        $this->request = new RestRequest();
    }
    
    /**
     * get module config
     * 
     * @param string $name
     * @return mixed
     */
    protected function config($name)
    {
        return Registry::module($name);
    }

    /**
     * view model for returning from action
     * 
     * @return \Web\View\ViewModel
     */
    public function getViewModel()
    {
        $this->view->setResponse($this->response->get());
        
        return $this->view;
    }

    /**
     * get registry value
     * 
     * @param string $key
     * @return mixed 
     */
    protected function registry($key)
    {
        return Registry::get($key);
    }
    
    /**
     * get site url
     * 
     * @param string $url
     * @return string 
     */
    protected function url($url = '')
    {
        return $this->registry('baseUrl') .  trim($url, '/');
    }
    
    /**
     * get url param
     * 
     * @param string $name
     * @param boolean $numeric
     * @return string|int
     */
    protected function getParam($name = 'id', $numeric = false)
    {
        $value = '';
        $params = $this->registry('params');
        if (isset($params[$name])) {
            $value = $params[$name];
        }
        if ($numeric) {
            return (int) $value;
        }
        
        return $value;
    }
    
    /**
     * get REST response handler
     * 
     * @return \Web\Mvc\RestResponse
     */
    protected function getResponse()
    {
        return $this->response;
    }
    
    /**
     * get REST request handler
     * 
     * @return \Web\Mvc\RestRequest
     */
    protected function getRequest()
    {
        return $this->request;
    }
    
    /**
     * set default adapter
     * 
     * @param string $name
     */
    protected function setDefaultAdapter($name)
    {
        Adapter::setDefaultAdapter($name);
    }
    
    /**
     * check if running in production environment
     * 
     * @return boolean
     */
    protected function isProduction()
    {
        return 'production' == $this->registry('environment');
    }
    
}
