<?php
/**
 * Backend Index Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Form\LoginForm;
use Backend\Utility\Dashboard;

use Shared\Utility\DeductionHelper;

use Web\View\Support\LookupStore;
use Shared\Constant\ManagerType;

class IndexController extends BackendController
{
    var $shoCeoMenuItems = false;
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
        if (! $this->session->isLogged()) {
            $this->_showLogin();
            
            return $this->getViewModel();
        }
        if(ManagerType::CEO == $this->_userType){
            $this->shoCeoMenuItems = true;
        }
        $this->registerPairs();
        
        $dashboard = new Dashboard();
        $dashboard->run();
        $this->view->setVariable('dashboard', $dashboard);
        $this->view->setVariable('shoCeoMenuItems', $this->shoCeoMenuItems);
      //  LookupStore::register('deductionType', DeductionHelper::deductionTypes());
     //   LookupStore::register('deductionGroup', DeductionHelper::deductionGroups());
     //   LookupStore::register('deductionTypeToGroup', DeductionHelper::deductionTypeGroups());
        
        
        $this->layout->setLayout('index');
        
        return $this->getViewModel();
    }
    
    /**
     * error action
     * 
     * @return \Web\View\ViewModel
     */
    public function errorAction()
    {
        if ($this->session->isLogged()) {
            $this->layout->setLayout('index');
        } else {
            $this->layout->setLayout('auth');
        }
        $this->setHeadTitle('Error');
        
        return $this->getViewModel();
    }
    
    /**
     * show login on home url
     */
    private function _showLogin()
    {
        $form = new LoginForm();
        $form->captchaEnabled(true);
        $this->setHeadTitle('Login');
        $this->registerForm($form);
        $this->layout->setLayout('auth');
        $this->layout->setScript('login/index');
        $this->view->setVariable('captchaEnabled', true);
        $this->view->setVariable('showForgotPassword', false);
    }
    
}
