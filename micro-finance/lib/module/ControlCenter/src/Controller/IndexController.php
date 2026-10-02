<?php
/**
 * ControlCenter Index Controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use ControlCenter\ControlCenterController;


class IndexController extends ControlCenterController
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
        $this->authenticationRequired();
        $this->out('Welcome');
        $this->finish();
        
        return $this->getViewModel();
    }
    
    /**
     * error action
     * 
     * @return \Web\View\ViewModel
     */
    public function errorAction()
    {
        return $this->getViewModel();
    }
    
    /**
     * logout action
     * 
     * @return \Web\View\ViewModel
     */
    public function logoutAction()
    {
        $this->endSession();
        
        return $this->getViewModel();
    }
    
    /**
     * path action
     * 
     * @return \Web\View\ViewModel
     */
    public function pathAction()
    {
        $this->authenticationRequired();
        $this->out(__DIR__);
        
        return $this->getViewModel();
    }
    
    /**
     * info action
     * 
     * @return \Web\View\ViewModel
     */
    public function infoAction()
    {
        $this->authenticationRequired();
        phpinfo();
        
        return $this->getViewModel();
    }
    
}
