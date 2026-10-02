<?php
/**
 * Backend Logout Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;


class LogoutController extends BackendController
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
        }
        
        $this->redirect('');
        
        return $this->getViewModel();
    }

}
