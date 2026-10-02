<?php
/**
 * Web Core Cookie
 * 
 * _COOKIE variables
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;


class Cookie
{
    const EXPIRY = 604800;
    const PATH = '/';
    const SALT = '1kJh&V9-S5dQz!gv~LxeC3t#!';
    
    /**
     * get cookie variable
     * 
     * @param string $key
     * @param mixed $default
     * @return string
     */
	public static function get($key, $default = null)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
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
        return isset($_COOKIE[$key]);
    }
    
    /**
     * set cookie
     * 
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public static function set($key, $value)
    {
        $expiry = time() + self::EXPIRY;
        
        return setcookie($key, $value, $expiry, self::PATH);
    }
    
    /**
     * clear cookie
     * 
     * @param string $key
     * @return boolean
     */
    public static function clear($key)
    {
        $expiry = time() - self::EXPIRY;
        
        return setcookie($key, '', $expiry, self::PATH);
    }
    
    /**
     * decode cookie value
     * 
     * @param string $cookie
     * @return array
     */
    public static function decode($cookie)
    {
        $result = false;
        if (false !== ($decoded = base64_decode($cookie, true))) {
            $split = explode(' ', $decoded);
            if (2 == count($split)) {
                if (md5($split[0] . self::SALT) == $split[1]) {
                    $result = explode(':', $split[0]);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * encode cookie value
     * 
     * @param int $id
     * @param string $text
     * @return string
     */
    public static function encode($id, $text)
    {
        $code = $id . ':' . $text;
        $encoded = md5($code . self::SALT);
        $code .= ' ' . $encoded;
        
        return base64_encode($code);
    }
    
}
