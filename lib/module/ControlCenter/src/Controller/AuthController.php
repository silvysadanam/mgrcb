<?php
/**
 * ControlCenter Auth Controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use ControlCenter\ControlCenterController;

use ControlCenter\Form\AuthForm;


class AuthController extends ControlCenterController
{
    public function __construct()
    {
        parent::__construct();
        if ($this->isAuthenticated()) {
            exit;
        }
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new AuthForm();
        if ($form->isPost()) {
            if ($form->isValid()) {
                $this->authenticated();
                $this->redirect();
            }
        }
        
        $this->registerForm($form);
                
        return $this->getViewModel();
    }

}
