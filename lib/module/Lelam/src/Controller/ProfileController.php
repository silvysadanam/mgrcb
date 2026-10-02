<?php
/**
 * Lelam Profile Controller
 * 
 * @category Lelam
 * @package  Lelam\Controller
 */

namespace Lelam\Controller;

use Lelam\LelamController;
use Lelam\Constant\Tab;
use Lelam\Form\ProfileForm;

use Model\Member;


class ProfileController extends LelamController
{
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Member();
        $this->currentTab(Tab::PROFILE);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $membership = $this->_branch->membership();
        $this->view->setVariable('membership', $membership);
        $this->_breadcrumb('Membership');
        
        return $this->getViewModel();
    }
    
    /**
     * password action
     * 
     * @return \Web\View\ViewModel
     */
    public function passwordAction()
    {
        $form = new ProfileForm();
        $form->setModel($this->_model);
        $form->setId($this->getUserId());
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->change();
                $this->setFlashMessage('Password changed', 'profile');
            }
        }

        $this->registerForm($form);
        $this->addModuleScript('profile-password');
        $this->_breadcrumb('Change Password');
        
        return $this->getViewModel();
    }
    
    
    /**
     * set page breadcrumb
     */
    private function _breadcrumb($page)
    {
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Profile', $this->view->actionUrl('profile'));
        $breadrumb->add($page);
        
        $this->setHeadTitle('Profile :: ' . $page);
        $this->setPageHeading('Profile');
        $this->setPageSubheading($page);
    }

}
