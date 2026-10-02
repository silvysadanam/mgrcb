<?php
/**
 * Application Profile Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\FlashMessage;
use Application\Form\Profile\ChangeForm;
use Application\Constant\Tab;

use Model\Administrator;


class ProfileController extends ApplicationController
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
        $this->currentTab(Tab::INTERFACE_PROFILE);
        $this->_model = new Administrator();
        $this->setFlashMessageTitle('Profile');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new ChangeForm();
        $form->setModel($this->_model);
        $form->setId($this->getUserId());
        if ($form->isPost()) {
            if ($form->isValid()) {
                if ($form->change()) {
                    $this->setActionFlashMessage(FlashMessage::CHANGE);
                } else {
                    $this->setActionFlashMessage(FlashMessage::NO_CHANGE);
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
