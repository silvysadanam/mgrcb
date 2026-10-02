<?php
/**
 * Web Cli Router
 *
 * @category   Web
 * @package    Web\Cli
 */

namespace Web\Cli;

use Web\Core\Registry;


class CliRouter
{
    const INDEX = 'index';
    const ACTION = 'action';
    const CONTROLLER = 'controller';
    const PARAMS = 'params';
    
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
     * initialize router
     * 
     * @return boolean
     */
    public static function init()
    {
        if (self::$_loaded) {
            return true;
        }
        self::$_route = [
            self::CONTROLLER => self::INDEX,
            self::ACTION => self::INDEX,
        ];
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
                if (1 == preg_match('#^[a-z]([a-z0-9/-]+)[a-z0-9]$#', $argv[0])) {
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
     * validate parameter name
     * 
     * @param string $value
     * @return boolean 
     */
    private static function _validateParamName($value)
    {
        return 1 == preg_match('/^[a-z][a-z0-9_-]+$/', $value);
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

}
