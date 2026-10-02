<?php
/**
 * Backend Settings Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Utility\ManageSettings;

use Model\{CardType, MemberCard, DocumentType, MemberDocument, Medicine, Disease};
use Model\{Account, Bank, PanchayathBrand, Boat};

use Backend\Form\AjaxForm;


class SettingsController extends BackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::SETTINGS);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->setHeadTitle('Settings');
        $this->setPageHeading('Settings');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings');
        
        return $this->getViewModel();
    }
    
    /**
     * card type action
     * 
     * @return \Web\View\ViewModel
     */
    public function cardTypeAction()
    {
        $model = new CardType();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'card_type'));
        
            $memberCardModel = new MemberCard();
            $this->view->setVariable('used', $memberCardModel->selectDistinct('card_type_id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/card-type');
        $this->layout->addModal('settings/card-type');
        
        $this->_breadcrumb('Card Types');
        
        return $this->getViewModel();
    }
    
    /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageTypeAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageSettings($this->getAjaxResponse());
        switch ($this->getParam('type')) {
            case 'card':
                $result = $manage->cardType();
                break;
            
            case 'document':
                $result = $manage->documentType();
                break;
            
            case 'medicine':
                $result = $manage->medicine();
                break;
            
            case 'disease':
                $result = $manage->disease();
                break;
            
            case 'bank':
                $result = $manage->bank();
                break;
            
            case 'panchayath':
                $result = $manage->panchayathBrand();
                break;
            
            default:
                break;
        }
        
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    /**
     * document type action
     * 
     * @return \Web\View\ViewModel
     */
    public function documentTypeAction()
    {
        $model = new DocumentType();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'document_type'));
        
            $memberDocumentModel = new MemberDocument();
            $this->view->setVariable('used', $memberDocumentModel->selectDistinct('document_type_id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/document-type');
        $this->layout->addModal('settings/document-type');
        
        $this->_breadcrumb('Document Types');
        
        return $this->getViewModel();
    }
    
    /**
     * medicine action
     * 
     * @return \Web\View\ViewModel
     */
    public function medicineAction()
    {
        $model = new Medicine();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'medicine'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/medicine');
        $this->layout->addModal('settings/medicine');
        
        $this->_breadcrumb('Medicine Names');
        
        return $this->getViewModel();
    }
    
     /**
     * medicine action
     * 
     * @return \Web\View\ViewModel
     */
    public function diseaseAction()
    {
        $model = new Disease();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'disease'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/disease');
        $this->layout->addModal('settings/disease');
        
        $this->_breadcrumb('Disease Names');
        
        return $this->getViewModel();
    }
    /**
     * bank action
     * 
     * @return \Web\View\ViewModel
     */
    public function bankAction()
    {
        $model = new Bank();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'bank'));
        
            $accountModel = new Account();
            $this->view->setVariable('used', $accountModel->selectDistinct('bank_id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/bank');
        $this->layout->addModal('settings/bank');
        
        $this->_breadcrumb('Banks');
        
        return $this->getViewModel();
    }
    
    /**
     * panchayath brand action
     * 
     * @return \Web\View\ViewModel
     */
    public function panchayathBrandAction()
    {
        $model = new PanchayathBrand();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'panchayath_brand'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('settings/panchayath-brand');
        $this->layout->addModal('settings/panchayath-brand');
        
        $this->_breadcrumb('Panchayath');
        
        return $this->getViewModel();
    }
    
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Settings - ' . $page);
        $this->setPageHeading('Settings');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings', $this->view->actionUrl('settings'));
        $breadrumb->add($page);
    }
    
    /**
     * medicine action
     * 
     * @return \Web\View\ViewModel
     */
    public function medicine2Action()
    {
        $model = new Medicine();
        
        $this->view->setVariable('list', $model->select(null, 'medicine'));
        $scripts = $this->plugin('script');
        $scripts->addThemeBody('app-assets/vendors/js/tables/datatable/datatables.min.js');
        $scripts->addThemeBody('app-assets/js/scripts/tables/datatables/datatable-basic.js');
        $this->addModuleScript('settings/medicine');
        $this->layout->addModal('settings/medicine');
        
        $this->_breadcrumb('Medicine Names');
        
        return $this->getViewModel();
    }
    
    /**
     * get medicine action
     * 
     * @return \Web\View\ViewModel
     */
    public function getFishAction()
    {
        if ($this->isAjaxRequest()) {
            $response = $this->getAjaxResponse();
            $message = 'Invalid request';
            $form = new AjaxForm();
            if ($form->isPost()) {
                $values = $form->run();
                if ($values['id'] > 0) {
                    $model = new Medicine();
                    if ($model->hasId($values['id'])) {
                        $response->addData($model->find($values['id']), 'result')
                                 ->valid();
                        $message = 'success';
                    }
                }
            }
            
            $response->message($message);
        }
        
        return $this->getViewModel();
    }
    
    /**
     * save medicine action
     * 
     * @return \Web\View\ViewModel
     */
    public function saveFishAction()
    {
        if ($this->isAjaxRequest()) {
            $response = $this->getAjaxResponse();
            $message = 'Invalid request';
            $form = new AjaxForm();
            $fields = ['medicine', 'name_english', 'name_malayalam', 'active'];
            $form->addFields($fields);
            if ($form->isPost()) {
                $model = new Medicine();
                $values = $form->run();
                if ($values['id'] > 0) {
                    if ($model->hasId($values['id'])) {
                        if ('' == $values['medicine']) {
                            $message = 'Medicine Name is required';
                        } elseif ($model->checkByColumnExceptId('medicine', $values['medicine'], $values['id'])) {
                            $message = 'Medicine Name exists';
                        } else {
                            $medicine = $model->find($values['id']);
                            $update = [];
                            foreach ($fields as $field) {
                                if ($values[$field] != $medicine->{$field}) {
                                    $update[$field] = $values[$field];
                                }
                            }

                            if (! empty($update)) {
                                if (isset($update['medicine']) || isset($update['name_english']) || isset($update['name_malayalam'])) {
                                    $update['name'] = $this->_medicineName([$values['medicine'], $values['name_english'], $values['name_malayalam']]);
                                }
                                $model->updateById($update, $values['id']);
                                $response->valid();
                                $this->setFlashMessage('Medicine updated');
                            } else {
                                $message = 'No changes';
                            }
                        }
                    }
                } else {
                    if ('' == $values['medicine']) {
                        $message = 'Medicine Name is required';
                    } elseif ($model->checkByColumn('medicine', $values['medicine'])) {
                        $message = 'Medicine Name exists';
                    } else {
                        $insert = $values;
                        unset($insert['id']);
                        $insert['name'] = $this->_medicineName([$values['medicine'], $values['name_english'], $values['name_malayalam']]);
                        $model->insert($insert);
                        $response->valid();
                        $this->setFlashMessage('Medicine added');
                    }
                }
            }
            
            $response->message($message);
        }
        
        return $this->getViewModel();
    }
    
    
    private function _medicineName($options)
    {
        $list = array_filter($options);
        
        return implode(' / ', $list);
    }
    
}
