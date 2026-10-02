<?php
/**
 * Branch Index Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Form\LoginForm;


class IndexController extends BranchController
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
        if ($this->session->isLogged()) {
            $this->layout->setLayout('index');
        } else {
            $this->layout->setLayout('auth');
        }
        $this->setHeadTitle('Error');
        
        return $this->getViewModel();
    }
    
    /**
     * show login on home url
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
