<?php
/**
 * Application Index Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Form\LoginForm;
use Application\Utility\Dashboard;


class IndexController extends ApplicationController
{
    public function __construct()
    {
        parent::__construct();
        
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        if (! $this->session->isLogged()) {
            $this->_showLogin();
            
            return $this->getViewModel();
        }
        
        $this->layout->setLayout('index');
        
        return $this->getViewModel();
    }
    
    /**
     * error action
     * 
     * @return \Web\View\ViewModel
     */
    public function errorAction()
    {
        $this->setHeadTitle('Error');
        $this->view->setVariable('errorPage', true);
        if (! $this->session->isLogged()) {
            $this->layout->setScript('index/error-login');
        }
        
        return $this->getViewModel();
    }
    
    /**
     * show login on home page
     */
    private function _showLogin()
    {
        $form = new LoginForm();
        $form->captchaEnabled(true);
        $this->setHeadTitle('Login');
        $this->registerForm($form);
        $this->layout->setLayout('auth');
        $this->layout->setScript('login/index');
        $this->view->setVariable('captchaEnabled', true);
        $this->view->setVariable('showForgotPassword', false);
    }
    
}
