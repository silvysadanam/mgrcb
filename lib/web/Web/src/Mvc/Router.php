<?php
/**
 * Web Mvc Router
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Core\Server;
use Web\Core\Registry;
use Web\Mvc\Module;


class Router
{
    const INDEX = 'index';
    const SLASH = '/';
    const ACTION = 'action';
    const CONTROLLER = 'controller';
    const MODULE = 'module';
    const TEMPLATE = 'template';
    const PARAMS = 'params';
    const SLUG = 'slug';
    
    /**
     * loaded status
     * 
     * @var boolean 
     */
    private static $_loaded = false;
    
    /**
     * route
     * 
     * @var array 
     */
    private static $_route;
    
    /**
     * custom routes
     * 
     * @var array 
     */
    private static $_custom = [];
    
    /**
     *
     * @var boolean 
     */
    private static $_web = true;

    /**
     * initialize router
     * 
     * @return boolean
     */
    public static function init($web = true)
    {
        if (self::$_loaded) {
            return true;
        }
        self::$_web = $web;
        self::$_route = [
            self::MODULE => '',
            self::CONTROLLER => self::INDEX,
            self::ACTION => self::INDEX,
            self::TEMPLATE => self::INDEX . self::SLASH . self::INDEX,
        ];
        if (true === self::$_web) {
            $namedRoutes = Registry::get('routes');
            if (! empty($namedRoutes)) {
                foreach ($namedRoutes as $item) {
                    self::add($item['url'], $item['type'], $item['params']);
                }
            }
        }
    }
    
    /**
     * add custom route
     * 
     * @param string $url
     * @param string $type literal or pattern
     * @param array $params
     */
    public static function add($url, $type = 'literal', $params = [])
    {
        $route = self::$_route;
        $route['url'] = $url;
        $route['type'] = $type;
        if (isset($params[self::MODULE])) {
            $route[self::MODULE] = $params[self::MODULE];
        }
        if (isset($params[self::CONTROLLER])) {
            $route[self::CONTROLLER] = $params[self::CONTROLLER];
        }
        if (isset($params[self::ACTION])) {
            $route[self::ACTION] = $params[self::ACTION];
        }
        if (isset($params[self::TEMPLATE])) {
            $route[self::TEMPLATE] = $params[self::TEMPLATE];
        } else {
            $route[self::TEMPLATE] = self::_combine($route[self::CONTROLLER], $route[self::ACTION]);
        }
        self::$_custom[] = $route;
    }

    /**
     * load route for web
     * 
     * @return array
     */
    public static function web()
    {
        self::$_loaded = true;
        $params = [];
        $actionUri = self::_getActionUri();
        if ('' != $actionUri) {
            Registry::set(self::SLUG, $actionUri);
            if (! self::_checkCustom($actionUri)) {
                $parts = explode(self::SLASH, $actionUri);
                if (self::_validateModuleName($parts[0])) {
                    if (Module::isModuleName($parts[0])) {
                        Module::setModuleByName($parts[0]);
                        self::$_route[self::MODULE] = $parts[0];
                        array_shift($parts);
                    }
                }
                if (! empty($parts)) {
                    if (self::_validateRouteName($parts[0])) {
                        self::$_route[self::CONTROLLER] = $parts[0];
                        self::$_route[self::TEMPLATE] = self::_combine($parts[0], self::INDEX);
                        if (isset($parts[1])) {
                            if (self::_validateRouteName($parts[1])) {
                                self::$_route[self::ACTION] = $parts[1];
                                self::$_route[self::TEMPLATE] = self::_combine(self::$_route[self::CONTROLLER], self::$_route[self::ACTION]);
                                if (count($parts) > 3) {
                                    $params = self::_extractParams($parts);
                                } elseif (3 == count($parts)) {
                                    Registry::set(self::SLUG, $parts[2]);
                                }
                            } else {
                                Registry::set(self::SLUG, $parts[1]);
                            }
                        }
                    } else {
                        Registry::set(self::SLUG, $parts[0]);
                    }
                }
            }
        }
        Registry::set(self::PARAMS, $params);        
        self::_loadFilters();
        Registry::set('route', self::$_route);
        
        return self::$_route;
    }

    /**
     * get route
     * 
     * @return array 
     */
    public static function route()
    {
        return self::$_route;
    }

    /**
     * get action name
     * 
     * @return string 
     */
    public static function action()
    {
        return self::$_route[self::ACTION];
    }

    /**
     * get controller name
     * 
     * @return string 
     */
    public static function controller()
    {
        return self::$_route[self::CONTROLLER];
    }

    /**
     * get template
     * 
     * @return string 
     */
    public static function template()
    {
        return self::$_route[self::TEMPLATE];
    }
    
    /**
     * get CLI route
     * 
     * @param array $argv
     * @return array
     */
    public static function cli($argv)
    {
        self::$_loaded = true;
        array_shift($argv);
        $params = [];
        if (! empty($argv)) {
            if ('' != $argv[0]) {
                if (1 == preg_match('#^[a-z]([a-z0-9/-])*[a-z0-9]$#', $argv[0])) {
                    if (false !== strpos($argv[0], DS)) {
                        $parts = explode(DS, trim($argv[0], DS));
                        self::$_route[self::CONTROLLER] = $parts[0];
                        self::$_route[self::ACTION] = $parts[1];
                        if (count($parts) > 3) {
                            $params = self::_extractParams($parts);
                        }
                    } else {
                        self::$_route[self::CONTROLLER] = $argv[0];
                        if (! empty($argv[1])) {
                            if (1 == preg_match('#^[a-z][a-z-]+$#', $argv[1])) {
                                self::$_route[self::ACTION] = $argv[1];
                                if (count($argv) > 3) {
                                    $params = self::_extractParams($argv);
                                }
                            }
                        }
                    }
                }
            }
        }
        Registry::set(self::PARAMS, $params);
        Registry::set('route', self::$_route);
        
        return self::$_route;
    }

    
    /**
     * combine params with /
     * 
     * @return string
     */
    private static function _combine()
    {
        return implode(self::SLASH, func_get_args());
    }
    
    /**
     * get action uri for routing
     * 
     * @return string
     */
    private static function _getActionUri()
    {
        $uri = substr(Server::requestUri(), strlen(Registry::get('baseUrl')));
        if (! empty($uri)) {
            $scriptName = basename(Server::scriptName());
            if (0 === strpos($uri, $scriptName)) {
                $uri = substr($uri, strlen($scriptName));
            }
            return trim(preg_replace('#/+#', '/', $uri), '/');
        }
        
        return '';
    }
    
    /**
     * check for custom route
     * 
     * @param string $actionUri
     * @return boolean
     */
    private static function _checkCustom($actionUri)
    {
        $found = false;
        if (! empty(self::$_custom)) {
            foreach (self::$_custom as $route) {
                if ('literal' == $route['type']) {
                    if ($actionUri == $route['url']) {
                        self::$_route = $route;
                        $found = true;
                        break;
                    }
                } elseif (1 == preg_match('#' . $route['url'] . '#', $actionUri)) {
                    self::$_route = $route;
                    $found = true;
                    break;
                }
            }
        }
        
        return $found;
    }

    /**
     * validate route name
     * 
     * @param string $value
     * @return boolean 
     */
    private static function _validateRouteName($value)
    {
        $valid = false;
        if (1 == preg_match('/^[a-z][a-z0-9-]+$/', $value)) {
            $valid = false === strpos($value, '--');
        }
        
        return $valid;
    }
    
    /**
     * validate parameter name
     * 
     * @param string $value
     * @return boolean 
     */
    private static function _validateParamName($value)
    {
        return 1 == preg_match('/^[a-z][a-z]+$/', $value);
    }
    
    /**
     * validate module name
     * 
     * @param string $value
     * @return boolean 
     */
    private static function _validateModuleName($value)
    {
        return 1 == preg_match('/^[a-z][a-z-]+$/', $value);
    }
    
    /**
     * extract additional params
     * 
     * @param array $list
     * @return array
     */
    private static function _extractParams($list)
    {
        $params = [];
        $paramParts = array_slice($list, 2);
        Registry::set(self::SLUG, implode('/', $paramParts));
        $paramCount = intval(count($paramParts) / 2);
        for ($count = 0; $count < $paramCount; $count++) {
            $index = 2 * $count;
            $valueIndex = $index + 1;
            $paramName = $paramParts[$index];
            if (self::_validateParamName($paramName)) {
                if (! isset($params[$paramName])) {
                    $params[$paramName] = rawurldecode($paramParts[$valueIndex]);
                }
            }
        }
        
        return $params;
    }
    
    /**
     * load query string filters
     */
    private static function _loadFilters()
    {
        $filters = [];
        $queryString = Server::queryString();
        if ('' != $queryString) {
            parse_str($queryString, $temp);
            $get = array_filter($temp);
            if (! empty($get)) {
                foreach ($get as $key => $value) {
                    if (! is_numeric($key) && is_string($value)) {
                        if ('' != trim($value)) {
                            $filters[$key] = trim($value);
                        }
                    }
                }
            }
        }
        Registry::set('filters', $filters);
    }

}
