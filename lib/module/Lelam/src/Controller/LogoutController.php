<?php
/**
 * Lelam Logout Controller
 * 
 * @category Lelam
 * @package  Lelam\Controller
 */

namespace Lelam\Controller;

use Lelam\LelamController;


class LogoutController extends LelamController
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
