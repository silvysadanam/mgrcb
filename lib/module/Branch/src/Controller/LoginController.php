<?php
/** 
 * Branch Login Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Form\LoginForm;

use Model\Administrator;
use Model\AdministratorLogin;

use Web\Utility\DateHelper;


class LoginController extends BranchController
{
    /**
     *
     * @var \Model\Administrator
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
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $username = $form->getValue('credential10');
                $userId = $this->_model->selectIdByColumn('username', $username);
                $this->session->set($userId);
                $login = [
                    'login_id' => $userId,
                    'login_time' => DateHelper::timestamp(),
                ];
                $loginModel = new AdministratorLogin();
                $loginModel->insert($login);
                $this->redirect();
            }
        }
        $this->setHeadTitle('Login');
        $this->registerForm($form);
        $this->layout->setLayout('auth');
        $this->view->setVariable('captchaEnabled', $this->_captchaEnabled);
        $this->view->setVariable('showForgotPassword', false);
        
        return $this->getViewModel();
    }

}
