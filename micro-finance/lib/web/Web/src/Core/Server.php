<?php
/**
 * Web Core Server
 * 
 * _SERVER variables
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;


class Server
{
    const HTTP_HOST = 'HTTP_HOST';
    const REQUEST_METHOD = 'REQUEST_METHOD';
    const QUERY_STRING = 'QUERY_STRING';
    const HTTP_REFERER = 'HTTP_REFERER';
    const HTTP_USER_AGENT = 'HTTP_USER_AGENT';
    const HTTPS = 'HTTPS';
    const REMOTE_ADDR = 'REMOTE_ADDR';
    const SCRIPT_FILENAME = 'SCRIPT_FILENAME';
    const SCRIPT_NAME = 'SCRIPT_NAME';
    const REQUEST_URI = 'REQUEST_URI';
    const PATH_INFO = 'PATH_INFO';
    const HTTP_X_REQUESTED_WITH = 'HTTP_X_REQUESTED_WITH';
    
    /**
     * get server variable
     * 
     * @param string $key
     * @param mixed $default
     * @return string
     */
	public static function get($key, $default = null)
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        return $default;
    }

    /**
     * check if server variable is available
     * 
     * @param string $key
     * @return boolean 
     */
    public static function is($key)
    {
        return isset($_SERVER[$key]);
    }
    
    /**
     * get host name
     * 
     * @return string
     */
    public static function host()
    {
        return self::get(self::HTTP_HOST);
    }
    
    /**
     * get request method
     * 
     * @return string
     */
    public static function requestMethod()
    {
        return self::get(self::REQUEST_METHOD);
    }
    
    /**
     * get query string
     * 
     * @return string
     */
    public static function queryString()
    {
        return self::get(self::QUERY_STRING);
    }
    
    /**
     * get referer
     * 
     * @return string
     */
    public static function referer()
    {
        return self::get(self::HTTP_REFERER);
    }
    
    /**
     * get user agent
     * 
     * @return string
     */
    public static function userAgent()
    {
        return self::get(self::HTTP_USER_AGENT);
    }
    
    /**
     * get https
     * 
     * @return string
     */
    public static function https()
    {
        return self::get(self::HTTPS);
    }
    
    /**
     * get remote address
     * 
     * @return string
     */
    public static function remoteAddress()
    {
        return self::get(self::REMOTE_ADDR);
    }
    
    /**
     * get script filename
     * 
     * @return string
     */
    public static function scriptFilename()
    {
        return self::get(self::SCRIPT_FILENAME);
    }
    
    /**
     * get script name
     * 
     * @return string
     */
    public static function scriptName()
    {
        return self::get(self::SCRIPT_NAME);
    }
    
    /**
     * get request uri
     * 
     * @return string
     */
    public static function requestUri()
    {
        return self::get(self::REQUEST_URI);
    }
    
    /**
     * get path info
     * 
     * @return string
     */
    public static function pathInfo()
    {
        return self::get(self::PATH_INFO);
    }
    
    /**
     * check request is XMLHttpRequest
     * @return type
     */
    public static function xmlHttpRequest()
    {
        return 'XMLHttpRequest' == self::xRequestedWith();
    }
    
    /**
     * get HTTP_X_REQUESTED_WITH
     * 
     * @return string
     */
    public static function xRequestedWith()
    {
        return self::get(self::HTTP_X_REQUESTED_WITH);
    }
    
    /**
     * get current protocol
     * 
     * @return string
     */
    public static function protocol()
    {
        $protocol = 'http';
        if (self::is(self::HTTPS)) {
            if ('on' == self::https()) {
                $protocol .= 's';
            }
        }
        
        return $protocol;
    }
    
}
