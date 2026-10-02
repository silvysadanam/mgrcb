<?php
/**
 * Web Mvc Service Manager
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Mvc\EventManager;
use Web\Mvc\View;
use Web\View\ViewModel;
use Web\Core\Registry;


class ServiceManager
{
    /**
     *
     * @var \Web\Mvc\View 
     */
    private $_view;
    
    /**
     *
     * @var \Web\Mvc\EventManager 
     */
    private $_event;
    
    public function __construct()
    {
        $this->_event = new EventManager();
        $this->_view = View::getInstance();
    }
    
    /**
     * run route
     * 
     * @param array $route
     */
    public function run($route)
    {
        $moduleClass = BS . Module::getModuleNamespace() . BS . 'Module';
        if (class_exists($moduleClass)) {
            $moduleConfig = new $moduleClass();
            Registry::set('module', $moduleConfig->getConfig());
        }
        $this->_view->loadTemplateHelper();
        $this->_view->setLayout('index');
        $this->_view->setScript($route['template']);
        $dispatched = false;
        $controller = BS . Module::getModuleNamespace() . BS
                    . $this->_getControllerClass($route['controller']);
        if (class_exists($controller)) {
            $service = new $controller();
            if (method_exists($service, 'onBootstrap')) {
                $service->onBootstrap($this->_event);
            }
            if (method_exists($service, 'onDispatch')) {
                $service->onDispatch($this->_event);
            }
            $this->_event->trigger('bootstrap');
            $action = $this->_getActionName($route['action']);
            if (method_exists($service, $action)) {
                $dispatched = true;
                $viewModel = $service->{$action}();
                if ($viewModel instanceof ViewModel) {
                    if ($viewModel->hasResponse()) {
                        $this->_view->sendResponse($viewModel);
                    }
                }
            } else {
                $viewModel = $service->indexAction();
            }
        }
        if (! $dispatched) {
            Registry::set('errorPage', true);
            $this->_view->setScript('index/error');
            $controller = BS . Module::getModuleNamespace() . BS
                    . $this->_getControllerClass('index');
            $service = new $controller();
            $viewModel = $service->errorAction();
        }
        if ($viewModel instanceof ViewModel) {
            $this->_view->layout($viewModel);
        } else {
            $this->_view->layout(new ViewModel());
        }
        if ($dispatched) {
            $this->_event->trigger('dispatch');
        }
        
        $this->_event->shutdown();
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
