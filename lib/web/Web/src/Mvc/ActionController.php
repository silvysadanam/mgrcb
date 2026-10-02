<?php
/**
 * Web Mvc Action Controller
 *
 * @category   Web
 * @package    Web\Mvc
 */

namespace Web\Mvc;

use Web\Exception;
use Web\Mvc\View;
use Web\View\ViewModel;
use Web\Core\Registry;
use Web\Core\Plugin;
use Web\Utility\Paging;
use Web\Mvc\AjaxResponse;
use Web\Core\Request;
use Web\Database\Adapter;
use Web\Mvc\SessionManager;


class ActionController
{
    /**
     *
     * @var \Web\View\ViewModel
     */
    protected $view;
    
    /**
     *
     * @var \Web\Mvc\View
     */
    protected $layout;
    
    /**
     * 
     * @var \Web\Form\Form
     */
    protected $form;
    
    /**
     *
     * @var \Web\Mvc\SessionManager 
     */
    protected $session;
    
    /**
     *
     * @var \Web\Core\Plugin
     */
    protected $plugins;
    
    /**
     *
     * @var array 
     */
    private $_extraLayoutVariables = [];

    /**
     * Constructor
     */
    public function __construct($sessionId = null, $table = null, $defaultAdapter = null)
    {
        if (null !== $defaultAdapter) {
            $this->setDefaultAdapter($defaultAdapter);
        }
        $this->session = new SessionManager($sessionId, $table);
        $this->layout = View::getInstance();
        $this->plugins = new Plugin();
        $this->view = new ViewModel();
    }
    
    /**
     * get module config
     * 
     * @param string $name
     * @return mixed
     */
    protected function config($name)
    {
        return Registry::module($name);
    }
    
    /**
     * get plugin
     * 
     * @param string $name
     * @return mixed
     */
    protected function plugin($name)
    {
        return $this->plugins->get($name);
    }
    
    /**
     * register form for current request
     * 
     * @param Core\Form $form
     * @throws Exception
     */
    protected function registerForm($form)
    {
        $this->form = $form;
    }

    /**
     * view model for returning from action
     * 
     * @return \Web\View\ViewModel
     */
    public function getViewModel()
    {
        $layoutVariables = [
            'headMeta' => $this->plugin('head')->get(),
            'javascripts' => $this->plugin('script')->getAll(),
            'stylesheets' => $this->plugin(Plugin::STYLE)->get(),
            'flashMessage' => $this->plugin('flash')->get(),
            'currentTab' => $this->plugin('tab')->get(),
            'currentSubTab' => $this->plugin('tab')->getSubTab(),
            'session' => $this->session,
            'themeUrl' => $this->plugin('theme')->getUrl(),
            'breadcrumb' => $this->plugin('breadcrumb')->build(),
            'pageHeading' => $this->plugin('breadcrumb')->getHeading(),
            'pageSubheading' => $this->plugin('breadcrumb')->getSubheading(),
            'year' => \Web\Utility\DateHelper::year(),
            'siteName' => $this->registry('siteName'),
            'project' => $this->registry('project'),
            'siteCode' => $this->registry('siteCode'),
        ];
        $csrf = new \Web\Form\Csrf();
        $layoutVariables['csrfToken'] = $csrf->getToken();
        
        if (! empty($this->_extraLayoutVariables)) {
            foreach ($this->_extraLayoutVariables as $name => $value) {
                if (! isset($layoutVariables[$name])) {
                    $layoutVariables[$name] = $value;
                }
            }
        }
        
        $this->view->setVariable('layoutVariables', $layoutVariables);
        
        if (null !== $this->form) {
            $this->view->setVariable('form', $this->form);
        }
        
        return $this->view;
    }

    /**
     * get registry value
     * 
     * @param string $key
     * @return mixed 
     */
    protected function registry($key)
    {
        return Registry::get($key);
    }
    
    /**
     * get site url
     * 
     * @param string $url
     * @return string 
     */
    protected function url($url = '')
    {
        return $this->registry('baseUrl') .  trim($url, '/');
    }
    
    /**
     * go to home
     */
    protected function goToHome()
    {
        $this->plugin('redirect')->action();
    }

    /**
     * Login required check
     * 
     * @param boolean $cacheRequestUrl
     */
    protected function loginRequired($cacheRequestUrl = false)
    {
        if (! $this->session->isLogged()) {
            if ($this->isAjaxRequest()) {
                $data = ['reload' => true, 'redirect' => true];
                header('Content-Type: application/json');
                echo json_encode($data);
                exit;
            }
            
            if ($cacheRequestUrl) {
                \Web\Core\Session::set('LoginRedirect', \Web\Core\Server::requestUri());
            }
            $this->goToLogin();
        }
    }
    
    /**
     * redirect to login
     */
    protected function goToLogin()
    {
        if ($this->registry('ajax')) {
            die('Session expired!! Please reload the page to login.');
        }
        $this->plugin('redirect')->action('login');
    }
   
    /**
     * redirect
     * 
     * @param string $url 
     */
    protected function redirect($url = '')
    {
        $this->plugin('redirect')->action($url);
    }
    
    /**
     * redirect to base url
     * 
     * @param string $url 
     */
    protected function redirectBase($url = '')
    {
        $this->plugin('redirect')->go($url);
    }
    
    /**
     * login cache redirect
     */
    protected function loginCacheRedirect()
    {
        if (\Web\Core\Session::is('LoginRedirect')) {
            $redirect = \Web\Core\Session::get('LoginRedirect');
            \Web\Core\Session::clear('LoginRedirect');
            header('Location: ' . $redirect);
            exit;
        }
    }
        
    /**
     * set head meta title
     * 
     * @param string $title 
     */
    protected function setHeadTitle($title)
    {
        $this->plugin('head')->setTitle($title);
        $this->plugin('breadcrumb')->setHeading($title);
    }

    /**
     * add script file
     * 
     * @param string $script
     */
    protected function addScript($script, $type = null)
    {
        $this->plugin('script')->add($script, $type);
    }
    
    /**
     * add module script file
     * 
     * @param string $script
     */
    protected function addModuleScript($script)
    {
        $this->plugin('script')->addModuleBody($script);
    }
    
    /**
     * set message
     * 
     * @param string $message
     * @param string $actionUrl
     */
    protected function setFlashMessage($message, $url = '', $type = 'success')
    {
        $this->plugin('flash')->set($message, $type);
        if ('' != $url) {
            $this->plugin('redirect')->action($url);
        }
    }   

    /**
     * set error message
     * 
     * @param string $message
     * @param string $url
     */
    protected function setErrorFlashMessage($message, $url = '')
    {
        $this->plugin('flash')->set($message, 'error');
        if ('' != $url) {
            $this->plugin('redirect')->action($url);
        }
    }
    
    /**
     * set current tab 
     * 
     * @param int $tab
     */
    protected function currentTab($tab)
    {
        $this->plugin('tab')->set($tab);
    }
    
    /**
     * set current sub tab 
     * 
     * @param string $tab
     */
    protected function currentSubTab($tab)
    {
        $this->plugin('tab')->setSubTab($tab);
    }
    
    /**
     * add view block
     * 
     * @param string $name
     */
    protected function addBlock($name)
    {
        $this->layout->addBlock($name);
    }

    /**
     * Generates Paging
     * 
     * @param int $total 
     * @return StdClass 
     */
    protected function paging($total = 0, $limit = null, $generateLinks = true)
    {
        $paging = new Paging();
        $result = $paging->run($total, $limit, $generateLinks);
        $this->view->setVariable('paging', $result->html);
        $this->view->setVariable('pagingInfo', $result->info);
        $this->view->counter($result->sqlOffset);
        
        return $result;
    }
    
    /**
     * get url param
     * 
     * @param string $name
     * @param boolean $numeric
     * @return string|int
     */
    protected function getParam($name = 'id', $numeric = false)
    {
        $value = '';
        $params = $this->registry('params');
        if (isset($params[$name])) {
            $value = $params[$name];
        }
        if ($numeric) {
            return (int) $value;
        }
        
        return $value;
    }
    
     /**
     * get url id param
     * 
     * @param string $name
     * @return int
     */
    protected function getParamId($name = 'id')
    {
        return $this->getParam($name, true);
    }
    
    /**
     * get slug
     * 
     * @param boolean $first
     * @return string
     */
    protected function getSlug($first = true)
    {
        $slug = $this->registry('slug');
        if (! empty($slug)) {
            if ($first) {
                $parts = explode('/', $slug);
                $slug = $parts[0];
            }
        }
        
        return $slug;
    }
    
    /**
     * set layout theme
     * 
     * @param string $theme
     */
    protected function setTheme($theme)
    {
        $this->plugin('theme')->set($theme);
        $this->plugin('script')->setTheme($theme);
        $this->plugin('style')->setTheme($theme);
    }
    
    /**
     * set page heading
     * 
     * @param string $heading
     */
    protected function setPageHeading($heading)
    {
        $this->plugin('breadcrumb')->setHeading($heading);
    }
    
    /**
     * set page subheading
     * 
     * @param string $heading
     */
    protected function setPageSubheading($heading)
    {
        $this->plugin('breadcrumb')->setSubheading($heading);
    }
    
    /**
     * set flash message section title
     * 
     * @param string $title
     */
    protected function setFlashMessageTitle($title)
    {
        $this->plugin('message')->setTitle($title);
    }
    
    /**
     * set flash message by title and action result
     * 
     * @param string $action
     */
    protected function setActionFlashMessage($action)
    {
        switch ($action) {
            case 'create':
                $this->setFlashMessage($this->plugin('message')->created());
                break;
            
             case 'change':
                $this->setFlashMessage($this->plugin('message')->changed());
                break;
            
            case 'remove':
                $this->setFlashMessage($this->plugin('message')->removed());
                break;
            
            case 'nochange':
                $this->setFlashMessage($this->plugin('message')->notChanged());
                break;
            
            default:
                break;
        }
    }
    
    /**
     * get AJAX response handler
     * 
     * @param string $responseType
     * @return AjaxResponse
     */
    protected function getAjaxResponse($responseType = 'json')
    {
        return new AjaxResponse($this->view, $responseType);
    }
    
    /**
     * check if AJAX request
     * 
     * @param boolean $post check for POST request
     * @return boolean
     */
    protected function isAjaxRequest($post = true)
    {
        if (Request::isAjax()) {
            if ($post) {
                return Request::isPost();
            }
            return Request::isGet();
        }
        
        return false;
    }
    
    /**
     * set default adapter
     * 
     * @param string $name
     */
    protected function setDefaultAdapter($name)
    {
        Adapter::setDefaultAdapter($name);
    }
    
    /**
     * check if running in production environment
     * 
     * @return boolean
     */
    protected function isProduction()
    {
        return 'production' == $this->registry('environment');
    }
    
    /**
     * add extra layout variable
     * 
     * @param string $name
     * @param mixed $value
     */
    protected function addLayoutVariable($name, $value)
    {
        $this->_extraLayoutVariables[$name] = $value;
    }
    
}
