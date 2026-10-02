<?php
/**
 * Backend Profile Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Form\ProfileForm;

use Model\Administrator;


class ProfileController extends BackendController
{
    /**
     *
     * @var \Model\Administrator 
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Administrator();
        $this->currentTab(Tab::PROFILE);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new ProfileForm();
        $form->setModel($this->_model);
        $form->setId($this->getUserId());
        if ($form->isPost()) {
            if ($form->isValid()) {
                if ($form->change()) {
                    $this->setFlashMessage('Profile updated');
                } else {
                    $this->setFlashMessage('Profile not changed');
                }
                $this->redirect('profile');
            }
        } else {
            $profile = $this->_model->find($this->getUserId());
            $populate = [
                'name' => $profile->name,
                'username' => $profile->username,
            ];
            $form->populate($populate);
        }

        $this->registerForm($form);
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    
    /**
     * set page breadcrumb
     */
    private function _breadcrumb()
    {
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Profile', $this->view->actionUrl('profile'));
        $breadrumb->add('Edit');
        
        $this->setHeadTitle('Profile :: Edit');
        $this->setPageHeading('Profile');
        $this->setPageSubheading('Edit');
    }

}
