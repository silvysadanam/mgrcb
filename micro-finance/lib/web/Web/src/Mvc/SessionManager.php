<?php
/**
 * Web Mvc Session Manager
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Core\Session;
use Web\Core\Server;
use Web\Database\TableGateway;
use Web\Core\Registry;
use Web\Core\Cookie;


class SessionManager
{
    const KEEP_LOGGED_DEFAULT = 0;
    const COOKIE_NAME = 'KeepLogged1';
    const COOKIE_NAME_BACKEND = 'KeepLogged2';
    const COOKIE_EXPIRY = 604800;
    const KEEP_LOGGED_SESSION = 'KeepLogged';
    const COOKIE_SALT = '1kJhQ4ddOvATNLH3TMxAjmV9-StvFnL5dQz!gv~LxeC3t#!xtq$';
    const SESSION_ID_FRONT = 'default';
    const SESSION_ID_BACKEND = 'backend';
    
    /**
     *
     * @var array 
     */
    private $_session;
    
    /**
     *
     * @var array 
     */
    private $_activeValidators;
    
    /**
     * constructor method
     */
    public function __construct($sessionId = null, $table = null)
    {
        $this->_session = [
            'logged' => false,
            'id' => 0,
            'user' => null,
            'session' => $sessionId,
            'table' => $table,
        ];
        $this->_activeValidators = [
            'default' => [
                'active' => 1,
            ],
            'backend' => [
                'active' => 1,
            ],
        ];
        if (! Session::is(self::KEEP_LOGGED_SESSION)) {
            $this->_checkKeepLogged();
        }
        if (null !== $sessionId && null !== $table) {
            if (Session::is($sessionId)) {
                 $this->_load($table);
            }
        }
    }
    
    /**
     * check keep logged
     * 
     * @param string $table
     */
    private  function _checkKeepLogged()
    {
        Session::set(self::KEEP_LOGGED_SESSION, 1);
        $detected = false;
        if (Cookie::is(self::COOKIE_NAME)) {
            $cookie1 = Cookie::get(self::COOKIE_NAME);
            $decoded1 = Cookie::decode($cookie1);
            if (isset($decoded1[0])) {
                Session::login((int) $decoded1[0], self::SESSION_ID_FRONT);
                $detected = true;
            }
        }
        
        if (Cookie::is(self::COOKIE_NAME_BACKEND)) {
            $cookie2 = Cookie::get(self::COOKIE_NAME_BACKEND);
            $decoded2 = Cookie::decode($cookie2);
            if (isset($decoded2[0])) {
                Session::login((int) $decoded2[0], self::SESSION_ID_BACKEND);
                $detected = true;
            }
        }
        
        if ($detected) {
            header('Location: ' . Server::requestUri());
            exit;
        }
    }
    
    /**
     * set user id session
     * 
     * @param int $id
     */
    public function set($id)
    {
        Session::login($id, $this->_session['session']);
    }

    /**
     * clear login session
     */
    public function logout()
    {
        Session::clear($this->_session['session']);
        if (self::SESSION_ID_FRONT == $this->_session['session']) {
            $this->clearKeepLogged();
        } else {
            $this->clearKeepLoggedBackend();
        }
    }
    
    /**
     * get ID
     * 
     * @return int
     */
    public function getId()
    {
        return $this->_session['id'];
    }
    
    /**
     * get session id
     * 
     * @return int
     */
    public function getSessionId()
    {
        return $this->_session['session'];
    }
    
    /**
     * check logged status 
     * 
     * @return boolean 
     */
    public function isLogged()
    {
        return $this->_session['logged'];
    }

    /**
     * get user meta
     * 
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return $this->_get($key);
    }
    
    /**
     * get user record
     * 
     * @return \stdClass
     */
    public function user()
    {
        return $this->_session['user'];
    }
    
    /**
     * set keep logged in cookie
     * 
     * @param int $id
     * @param string $email
     */
    public function keepLogged($id, $email)
    {
        $cookie = Cookie::encode($id, $email);
        Cookie::set(self::COOKIE_NAME, $cookie);
    }
    
    /**
     * clear keep logged cookie
     */
    public function clearKeepLogged()
    {
        Cookie::clear(self::COOKIE_NAME);
    }
    
    /**
     * set keep logged in cookie for backend
     * 
     * @param int $id
     * @param string $email
     */
    public function keepLoggedBackend($id, $email)
    {
        $cookie = Cookie::encode($id, $email);
        Cookie::set(self::COOKIE_NAME_BACKEND, $cookie);
    }
    
    /**
     * clear keep logged backend cookie
     */
    public function clearKeepLoggedBackend()
    {
        Cookie::clear(self::COOKIE_NAME_BACKEND);
    }
    
    /**
     * get value by key 
     * 
     * @param string $key
     * @return boolean|mixed
     */
    private function _get($key)
    {
        if (null !== $this->_session['user']) {
            if (isset($this->_session['user']->{$key})) {
                return $this->_session['user']->{$key};
            }
        }
        
        return false;
    }
    
    /**
     * load user details
     * 
     * @param string $table
     * @param array $meta
     */
    private function _load($table)
    {
        $id = Session::get($this->_session['session']);
        if ($id > 0) {
            $tableGateway = new TableGateway($table);
            $valid = $tableGateway->hasId($id);
            if ($valid) {
                $user = $tableGateway->selectById($id);
                if (isset($this->_activeValidators[$this->_session['session']])) {
                    foreach ($this->_activeValidators[$this->_session['session']] as $key => $value) {
                        if (isset($user->{$key})) {
                            $valid = $value == $user->{$key};
                            if (! $valid) {
                                break;
                            }
                        }
                    }
                }
                if ($valid) {
                    $this->_session['id'] = $id;
                    $this->_session['user'] = $user;
                    $this->_session['logged'] = true;
                }
            } 
            if (! $valid) {
                if (self::SESSION_ID_FRONT == $this->_session['session']) {
                    $this->_clearCookie();
                }
                Session::close();
                if (Server::xmlHttpRequest()) {
                    echo 'expired';
                } else {
                    header('Location: ' . Registry::get('baseUrl'));
                }
                exit;
            }
        }
    }
    
    /**
     * clear cookie
     */
    private function _clearCookie()
    {
        setcookie(self::COOKIE_NAME, '', time() - self::COOKIE_EXPIRY, '/');
    }

}
