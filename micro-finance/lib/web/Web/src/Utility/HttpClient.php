<?php
/**
 * Web HTTP Client Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Exception;


class HttpClient
{
    const GET = 1;
    const POST = 2;
    const JSON = 3;
    
    /**
     *
     * @var string 
     */
    private $_url;
    
    /**
     *
     * @var boolean 
     */
    private $_post = true;
    
    /**
     *
     * @var array|string 
     */
    private $_data;
    
    /**
     *
     * @var string 
     */
    private $_response = null;
    
    /**
     *
     * @var int
     */
    private $_method;
    
    /**
     *
     * @var int 
     */
    private $_timeout = 30;
    
    /**
     *
     * @var boolean 
     */
    private $_getHeaders = false;
    
    /**
     *
     * @var array 
     */
    private $_headers = [];
    
    public function __construct()
    {
        if (! function_exists('curl_init')) {
            throw new Exception('curl extension required');
        }
    }
    
    /**
     * set request timeout
     * 
     * @param int $timeout seconds
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = (int) $timeout;
        
        return $this;
    }
    
    /**
     * set URL
     * 
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        
        return $this;
    }
    
    /**
     * set post data
     * 
     * @param array|string $data
     * @return $this
     */
    public function setData($data)
    {
        $this->_data = $data;
        
        return $this;
    }
    
    /**
     * include headers in response
     * 
     * @param boolean $include
     * @return $this
     */
    public function setGetHeaders($include = true)
    {
        $this->_getHeaders = (bool) $include;
        
        return $this;
    }
    
    /**
     * add request headers
     * 
     * @param array|string $header
     * @return $this
     */
    public function addRequestHeader($header)
    {
        if (is_array($header)) {
            foreach ($header as $item) {
                $this->_headers[] = $item;
            }
        } else {
            $this->_headers[] = $header;
        }
        
        return $this;
    }
    
    /**
     * set request method
     * 
     * @param int|string $method
     * @return $this
     */
    public function setMethod($method)
    {
        if (is_numeric($method)) {
            if (self::POST == $method || self::JSON == $method) {
                $this->_method = $method;
            } else {
                $this->_method = self::GET;
                $this->_post = false;
                $this->_data = null;
            }
        } else {
            switch (strtoupper($method)) {
                case 'POST':
                    $this->_method = self::POST;
                    break;
                
                case 'JSON':
                    $this->_method = self::JSON;
                    break;
                
                default:
                    $this->_method = self::GET;
                    $this->_post = false;
                    $this->_data = null;
                    break;
            }
        }
        
        return $this;
    }
    
    /**
     * send request
     * 
     * @return string
     * @throws Exception
     */
    public function send()
    {
        $this->_validateUrl();
        if ($this->_post) {
            if (empty($this->_data)) {
                throw new Exception('POST data cannot be empty');
            }
        }
        
        return $this->_send();
    }
    
    /**
     * get response
     * 
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }
    
    /**
     * send GET request from url
     * 
     * @param string $url
     * @return string
     */
    public function get($url = null)
    {
        if (null !== $url) {
            $this->_url = $url;
        }
        $this->_method = self::GET;
        $this->_post = false;
        $this->send();
        
        return $this->_response;
    }
    
    /**
     * send POST request with array data
     * 
     * @param string $url
     * @param array $data
     * @return satring
     */
    public function post($url = null, $data = null)
    {
        if (null !== $url) {
            $this->_url = $url;
        }
        if (null !== $data) {
            $this->_data = $data;
        }
        $this->_method = self::POST;
        $this->send();
        
        return $this->_response;
    }
    
    /**
     * send JSON POST request
     * 
     * @param string $url
     * @param array|string $data
     * @return string
     */
    public function json($url = null, $data = null)
    {
        if (null !== $url) {
            $this->_url = $url;
        }
        if (null !== $data) {
            $this->_data = $data;
        }
        $this->_method = self::JSON;
        $this->send();
        
        return $this->_response;
    }
    
    
    /**
     * process request
     * 
     * @return boolean
     */
    private function _send()
    {
        try {
            $ch = curl_init($this->_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, $this->_getHeaders);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
            if ($this->_post) {
                curl_setopt($ch, CURLOPT_POST, true);
                if (self::JSON == $this->_method) {
                    if (! is_string($this->_data)) {
                        $this->_data = json_encode($this->_data, JSON_FORCE_OBJECT);
                    }
                    $this->_headers[] = 'Content-Type: application/json;charset="UTF-8"'; 
                    $this->_headers[] = 'Content-Length:' . strlen($this->_data);
                }
                if (! empty($this->_headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
            }
            $this->_response = curl_exec($ch);
        } catch (Exception $e) {
           echo 'HTTP request failed. ' . $e->getMessage();
        }
        
        return curl_close($ch);
    }
    
    /**
     * validate URL
     * 
     * @return boolean
     * @throws Exception
     */
    private function _validateUrl()
    {
        if (empty($this->_url)) {
            throw new Exception('URL required');
        }
        if (! is_string($this->_url)) {
            throw new Exception('Invalid URL data type');
        }
        if (false === filter_var($this->_url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
        }
        if (0 == preg_match('#^http(s)?://#', $this->_url)) {
            throw new Exception('Invalid URL scheme');
        }
        
        return true;
    }
    
}
