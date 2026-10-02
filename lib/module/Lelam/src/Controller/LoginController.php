<?php
/** 
 * Lelam Login Controller
 * 
 * @category Lelam
 * @package  Lelam\Controller
 */

namespace Lelam\Controller;

use Lelam\LelamController;
use Lelam\Form\LoginForm;

use Model\Member;
use Model\MemberLogin;

use Web\Utility\DateHelper;


class LoginController extends LelamController
{
    /**
     *
     * @var \Model\Member
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
        $this->_model = new Member();
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
                $userId = $form->userId();
                $this->session->set($userId);
                $login = [
                    'member_id' => $userId,
                    'login_time' => DateHelper::timestamp(),
                ];
                $loginModel = new MemberLogin();
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
