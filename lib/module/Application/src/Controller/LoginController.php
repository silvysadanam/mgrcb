<?php
/** 
 * Application Login Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Form\LoginForm;

class LoginController extends ApplicationController
{  
    /**
     *
     * @var boolean 
     */
    private $_captchaEnabled = true;
    
    public function __construct()
    {
        parent::__construct();
        if ($this->session->isLogged()) {
            $this->redirect();
        }
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new LoginForm();
        $form->captchaEnabled($this->_captchaEnabled);
        if ($form->isPost()) {
            $form->addError('Invalid request');
        }
        
        $this->layout->setLayout('auth');
        $this->setHeadTitle('Login');
        $this->registerForm($form);
        $this->view->setVariable('captchaEnabled', $this->_captchaEnabled);
        $this->view->setVariable('showForgotPassword', false);
        
        $this->plugin('breadcrumb')->add('Login');
        
        return $this->getViewModel();
    }

}
