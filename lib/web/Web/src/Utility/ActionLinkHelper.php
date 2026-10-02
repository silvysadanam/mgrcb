<?php
/**
 * Web Action Link Helper Utility
 * 
 * @category Web
 * @package Utility
 */

namespace Web\Utility;

use Web\Core\Registry;
use Web\Core\Server;


class ActionLinkHelper
{
    const INDEX = 'index';
    const CANCEL = 'cancel';
    const REDIRECT = 'redirect';
    const RELOAD = 'reload';
    const VIEW = 'view';
    const CHANGE = 'change';
    const REMOVE = 'remove';
    const STATUS = 'status';
    
    /**
     *
     * @var array 
     */
    private static $_actions = [];
    
    /**
     *
     * @var string 
     */
    private static $_baseUrl = '/';
    
    /**
     *
     * @var array 
     */
    private static $_patterns = [];
    
    /**
     *
     * @var string 
     */
    private static $_queryString = '';

    /**
     * load links
     */
    public static function run()
    {
        $uri = Server::requestUri();
        if (false !== strpos($uri, '?')) {
            if (1 == preg_match('#[^/]\?#', $uri)) {
                $uri = str_replace('?', '\?', $uri);
            }
        }
        $route = Registry::get('route');
        $baseUrl = Registry::get('baseUrl');     
        if ('' != $route['module']) {
            $pattern = $route['module'] . DS . $route['controller'] . DS . '%s';
            $formatPattern = $route['module'] . DS . $route['controller'] . DS . '%s[ID]';
        } else {
            $pattern = $route['controller'] . DS . '%s';
            $formatPattern = $route['controller'] . DS . '%s[ID]';
        }
        $redirect = $route['controller'] . DS . '%s';
        $reload = $route['controller'] . DS . $route['action'];
        $params = Registry::get('params');
        if (! empty($params)) {
            if (isset($params['id'])) {
                $reload .= sprintf('/id/%d', $params['id']);
                unset($params['id']);
            }
            if (! empty($params)) {
                $extra = '';
                foreach ($params as $key => $value) {
                    $extra .= sprintf('/%s/%s', $key, rawurlencode($value));
                }
                $pattern .= $extra;
                $formatPattern .= $extra;
                $redirect .= $extra;
                $reload .= $extra;
            }
        }
        $pattern = rtrim($pattern, '/');
        $formatPattern = rtrim($formatPattern, '/');
        $redirect = rtrim($redirect, '/');
        $reload = rtrim($reload, '/');
        $queryString = Server::queryString();
        if (! empty($queryString)) {
            self::$_queryString = '/?' . $queryString;
            $reload .= '/?' . $queryString;
            
        }
        if (empty($params)) {
            self::$_actions[self::REDIRECT] = sprintf($redirect, 'index');
        } else {
            self::$_actions[self::REDIRECT] = sprintf($redirect, 'index');
        }
        self::$_actions[self::CANCEL] = $baseUrl . sprintf($pattern, 'index');
        self::$_actions[self::RELOAD] = $reload;
        self::$_patterns = [
            self::CANCEL => $pattern,
            'format' => $formatPattern,
            self::REDIRECT => $redirect,
        ];
        self::$_baseUrl = $baseUrl;
        switch ($route['action']) {
            case self::INDEX:
                self::$_actions[self::VIEW] = $baseUrl . str_replace('[ID]', '/id/%d', sprintf($formatPattern, 'view'));
                self::$_actions[self::CHANGE] = $baseUrl . str_replace('[ID]', '/id/%d', sprintf($formatPattern, 'change'));
                self::$_actions[self::REMOVE] = $baseUrl . str_replace('[ID]', '/id/%d', sprintf($formatPattern, 'remove'));
                self::$_actions[self::STATUS] = $baseUrl . str_replace('[ID]', '/id/%d', sprintf($formatPattern, 'status'));
                self::$_actions[self::CANCEL] = sprintf($pattern, 'index');
                self::$_actions[self::REDIRECT] = sprintf($pattern, 'index');
                break;
            
            case self::VIEW:
            case self::CHANGE:
            case self::REMOVE:
            case self::STATUS:
                self::$_actions[self::CANCEL] = $baseUrl . sprintf($pattern, 'index');
                break;
            
            default:
                break;
        }
    }
    
    /**
     * set cancel action
     * 
     * @param string $action
     */
    public static function setCancelAction($action)
    {
        self::$_actions[self::REDIRECT] = sprintf(self::$_patterns['redirect'], $action);
        self::$_actions[self::CANCEL] = self::$_baseUrl . sprintf(self::$_patterns['cancel'], $action);
        self::$_actions[$action] = self::$_actions[self::CANCEL];
    }
    
    /**
     * add format for action
     * 
     * @param string $action
     */
    public static function addFormatAction($action)
    {
        self::$_actions[$action] = self::$_baseUrl . str_replace('[ID]', '/id/%d', sprintf(self::$_patterns['format'], $action));
    }
    
    /**
     * add action
     * 
     * @param string $action
     * @param string $url
     */
    public static function addAction($action, $url)
    {
        self::$_actions[$action] = $url;
    }
    
    /**
     * get cancel link
     * 
     * @return string
     */
    public static function getCancelLink()
    {
        return self::$_actions[self::CANCEL] . htmlspecialchars(self::$_queryString);
    }
    
    /**
     * get redirect link
     * 
     * @return string
     */
    public static function getRedirectLink()
    {
        return self::$_actions[self::REDIRECT] . self::$_queryString;
    }
    
    /**
     * get reload link
     * 
     * @return string
     */
    public static function getReloadLink()
    {
        return self::$_actions[self::RELOAD];
    }

    /**
     * get action link
     * 
     * @param string $action
     * @param int $id
     * @return string
     */
    public static function getActionLink($action, $id = null)
    {
        if (isset(self::$_actions[$action])) {
            if (self::CANCEL == $action) {
                return self::getCancelLink();
            }
            if (self::REDIRECT == $action) {
                return self::getRedirectLink();
            }
            if (self::RELOAD == $action) {
                return self::getReloadLink();
            }
            if (null !== $id) {
                return sprintf(self::$_actions[$action], $id) . htmlspecialchars(self::$_queryString);
            }
            return self::$_actions[$action];
        }
        
        return self::getCancelLink();
    }
    
}
