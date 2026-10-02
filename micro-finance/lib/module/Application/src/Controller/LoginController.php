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

use Model\{Administrator, Login};


class LoginController extends ApplicationController
{
    /**
     *
     * @var \Model\User 
     */
    private $_model;
    
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
        $this->_model = new Administrator();
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
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $user = $form->getUser();
                $this->session->set($user->id);
                $loginModel = new Login();
                $loginModel->insert(['login_id' => $user->id, 'login_time' => $form->now(true)]);
                $this->loginCacheRedirect();
                $this->redirect();
            }
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
