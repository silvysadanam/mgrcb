<?php
/**
 * Web Core Session
 *
 * @category   Web
 * @package    Web\Core
 */

namespace Web\Core;


class Session
{
    /**
     * session key
     */
    const SESSION_KEY = '_FWS_APP';
    
    /**
     * session expiry time
     */
    const SESSION_EXPIRY = 3600;
    
    /**
     * session values
     * 
     * @var array 
     */
    private static $_session = [];
    
    /**
     * Session storage path
     * 
     * @var boolean 
     */
    private static $_useApplicationStorage = false;
    
    /**
     * Initialize session
     */
    public static function init()
    {
        if (self::$_useApplicationStorage) {
            session_save_path(STORAGE_DIR . DS . 'session');
        }
        session_set_cookie_params(self::SESSION_EXPIRY, '/');
        session_start();
        $cookieExpire = time() + self::SESSION_EXPIRY;
        setcookie(session_name(), session_id(), $cookieExpire, '/');
        if (isset($_SESSION[self::SESSION_KEY])) {
            self::$_session = unserialize($_SESSION[self::SESSION_KEY]);
        } else {
            $_SESSION[self::SESSION_KEY] = serialize([]);
        }
    }

    /**
     * check session
     * 
     * @param string $key
     * @return boolean 
     */
    public static function is($key)
    {
        return isset(self::$_session[$key]);
    }
    
    /**
     * set session
     * 
     * @param string $key
     * @param mixed $value 
     */
    public static function set($key, $value)
    {
        $data = unserialize($_SESSION[self::SESSION_KEY]);
        $data[$key] = $value;
        self::_write($data);
    }
    
    /**
     * get session value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed 
     */
    public static function get($key, $default = null)
    {
        if (isset(self::$_session[$key])) {
            $value = self::$_session[$key];
        } else {
            $value = $default;
        }
        
        return $value;
    }
    
    /**
     * clear session value
     * 
     * @param string $key 
     */
    public static function clear($key)
    {
        if (isset(self::$_session[$key])) {
            unset(self::$_session[$key]);
            self::_reset();
        }
    }
    
    /**
     * close session
     */
    public static function close()
    {
        $_SESSION[self::SESSION_KEY] = serialize([]);
        self::$_session = [];
    }
    
    /**
     * set login session
     * 
     * @param int $id
     */
    public static function login($id, $type)
    {
        self::set($type, $id);
    }
    
    /**
     * clear login session
     */
    public static function logout($session = null)
    {
        if (null !== $session) {
            self::clear($session);
        } else {
            self::close();
        }
    }
    
    /**
     * get session id
     * 
     * @return string
     */
    public static function id()
    {
        return session_id();
    }
    
    /**
     * increment value in session
     * as a counter
     * 
     * @param string $key
     */
    public static function increment($key)
    {
        $value = self::get($key, 0);
        if (is_numeric($value)) {
            self::set($key, ++$value);
        }
    }
    
    /**
     * write session data
     * 
     * @param array $data
     */
    private static function _write($data)
    {
        self::$_session = $data;
        $_SESSION[self::SESSION_KEY] = serialize($data);
    }
    
    /**
     * Reset session data
     */
    private static function _reset()
    {
        $_SESSION[self::SESSION_KEY] = serialize(self::$_session);
    }
    
}
