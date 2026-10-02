<?php
/**
 * Branch Logout Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;


class LogoutController extends BranchController
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
