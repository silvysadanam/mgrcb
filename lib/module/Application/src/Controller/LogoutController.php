<?php
/**
 * Application Logout Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;


class LogoutController extends ApplicationController
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
        if ($this->session->isLogged()) {
            $this->session->logout();
            $this->redirect('login');
        }
        $this->redirect();
    }

}
