<?php
/**
 * $_REQUEST handler
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;

use Web\Core\Server;


class Request
{
    const POST = 'POST';
    const GET = 'GET';
    
    /**
     * check if POST method request
     * 
     * @return boolean
     */
    public static function isPost()
    {
        return self::POST == Server::requestMethod();
    }
    
    /**
     * check if GET method request
     * 
     * @return boolean
     */
    public static function isGet()
    {
        return self::GET == Server::requestMethod();
    }
    
    /**
     * get $_REQUEST param
     * 
     * @param string $name
     * @return string
     */
    public static function get($name)
    {
        if (self::is($name)) {
            return $_REQUEST[$name];
        }
        
        return null;
    }
    
    /**
     * checke $_REQUEST param
     * 
     * @param string $name
     * @return boolean
     */
    public static function is($name)
    {
        return isset($_REQUEST[$name]);
    }
    
    /**
     * check if ajax request 
     * 
     * @return boolean
     */
    public static function isAjax()
    {
        return Server::xmlHttpRequest();
    }
    
}
