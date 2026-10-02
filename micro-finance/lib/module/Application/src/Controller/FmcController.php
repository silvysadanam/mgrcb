<?php
/**
 * Application Fmc Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\{Tab, FlashMessage};
use Application\Form\Fmc\{CreateForm, ChangeForm};

use Model\Fmc;


class FmcController extends ApplicationController
{
    /**
     *
     * @var \Model\Fmc 
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->adminOrCeoOnly();
        $this->_model = new Fmc();
        $this->currentTab(Tab::INTERFACE_SETTINGS);
        $this->setFlashMessageTitle('FMC');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $total = $this->_model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $this->_model->select(null, 'code'));
        }
        $this->view->setVariable('total', $total);
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        $this->_breadcrumb('List');
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $form = new CreateForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->create();
                $this->setActionFlashMessage(FlashMessage::CREATE);
                $this->redirect('fmc');
            }
        }
        
        $this->registerForm($form);
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    
    /**
     * change action
     * 
     * @return \Web\View\ViewModel
     */
    public function changeAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_model->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'fmc');
        }
        
        $form = new ChangeForm();
        $form->setModel($this->_model);
        $form->setId($id);
        if ($form->isPost()) {
            if ($form->isValid()) {
                if ($form->change()) {
                    $this->setActionFlashMessage(FlashMessage::CHANGE);
                } else {
                    $this->setActionFlashMessage(FlashMessage::NO_CHANGE);
                }
                
                $this->redirect('fmc');
            }
        } else {
            $form->populate($this->_model->find($id));
        }
        
        $this->registerForm($form);
        $this->_breadcrumb('Edit');
        
        return $this->getViewModel();
    }

    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Settings - FMC - ' . $page);
        $this->setPageHeading('Settings');
        $this->setPageSubheading('FMC');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings', $this->view->actionUrl('settings'));
        $breadrumb->add('FMC', $this->view->actionUrl('fmc'));
        $breadrumb->add($page);
    }
    
}
