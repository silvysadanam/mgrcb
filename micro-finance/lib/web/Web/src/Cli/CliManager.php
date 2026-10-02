<?php
/**
 * Cli manager
 * 
 * @category Web
 * @package Web\Cli
 */

namespace Web\Cli;


class CliManager
{
    const FALL_BACK_ACTION = 0;
    const CLI_NAMESPACE = 'Cli';
    const MIGRATION_NAMESPACE = 'Migration';
    
    /**
     *
     * @var string 
     */
    private $_namespace;
    
    public function __construct($namespace = null)
    {
        $this->_namespace = self::CLI_NAMESPACE;
        if (null !== $namespace) {
            $this->_namespace = $namespace;
        }
    }
    
    /**
     * run cli request
     * 
     * @param array $route
     */
    public function run($route)
    {
        $dispatched = false;
        $controller = $this->_getCliNamespace() . BS
                    . $this->_getControllerClass($route['controller']);
        if (class_exists($controller)) {   
            $service = new $controller();
            $action = $this->_getActionName($route['action']);
            if (method_exists($service, $action)) {
                $service->{$action}();
                $dispatched = true;
            } elseif (1 == self::FALL_BACK_ACTION) {
                $service->indexAction();
                $dispatched = true;
            }
        }
        if (! $dispatched) {
            echo 'Could not resolve route "' . $route['controller'] . DS . $route['action'] . '"'; 
        }
        
        exit;
    }
    
    /**
     * get cli namespace for controller
     * 
     * @return string
     */
    private function _getCliNamespace()
    {
        return BS . trim($this->_namespace, BS);
    }
    
    /**
     * get controller class name
     * 
     * @param styring $controller
     * @return string
     */
    private function _getControllerClass($controller)
    {
        $class = $controller;
        if (false !== strpos($controller, '-')) {
            preg_match_all('/-[a-z]/i', $controller, $m);
            $search = array_unique($m[0]);
            $replace = [];
            foreach ($search as $item) {
                $replace[] = ucfirst(str_replace('-', '', $item));
            }
            $class = str_replace($search, $replace, $class);
        }
        
        return 'Controller' . BS . ucfirst($class) . 'Controller';
    }
    
    /**
     * get action name
     * 
     * @param string $action
     * @return string
     */
    private function _getActionName($action)
    {
        if (false !== strpos($action, '-')) {
            preg_match_all('/-[a-z]/i', $action, $m);
            $search = array_unique($m[0]);
            $replace = [];
            foreach ($search as $item) {
                $replace[] = ucfirst(str_replace('-', '', $item));
            }
            $action = str_replace($search, $replace, $action);
        }
        
        return $action . 'Action';
    }
    
}
