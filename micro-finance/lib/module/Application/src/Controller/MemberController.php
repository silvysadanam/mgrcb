<?php
/**
 * Application Member Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\{Tab, FlashMessage};
use Application\Form\FilterForm;
use Application\Form\Member\{CreateForm, ChangeForm};

use Model\{Member, Fmc};

use Web\View\Support\LookupStore;

use Shared\Model\PairModel;


class MemberController extends ApplicationController
{
    const MEMBER = 1;
    const AUCTIONEER = 2;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->adminOrCeoOnly();
        $this->_model = new Member();
        $this->_fmcModel = new Fmc();
        $this->currentTab(Tab::INTERFACE_SETTINGS);
        $this->setFlashMessageTitle('Member');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $filters = new FilterForm();
        $filters->addFields(['fmc', 'type']);
        $filters->numeric(['fmc', 'type']);
        $values = $filters->getFilteredValues();
        $sql = $this->_model->getSql();
        $sqlJoin = $this->_model->getSql();
        if ($values['type'] > 0) {
            if (in_array($values['type'], [self::MEMBER, self::AUCTIONEER])) {
                $sql->whereId('member_type_id', $values['type']);
                $sqlJoin->whereId('m.member_type_id', $values['type']);
            }
        }
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $sql->whereId('fmc_id', $values['fmc']);
                $sqlJoin->whereId('m.fmc_id', $values['fmc']);
            }
        }
        
        $where = $sql->getWhere();
        $whereJoin = $sql->getWhere();
        
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            if (empty($where)) {
                $this->view->setVariable('list', $this->_model->select(null, 'name', $paging->sqlLimit));
            } else {
                $query = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`, `m`.`fmc_id`, `m`.`member_type_id` '
                       . 'FROM ' . $this->_model->getTableQuoted() . ' `m` '
                       . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() . ' `f` ON `f`.`id` = `m`.`fmc_id` ';

                if (! empty($whereJoin)) {
                    $query .= ' WHERE ' . $whereJoin;
                }
                $query .= ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
                $this->view->setVariable('list', $this->_model->queryRows($query));
            }
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('filters', $filters);
        $this->_loadFmcOptions();
        
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
                $this->redirect('member');
            }
        } else {
            $form->populate(['member_type_id' => self::MEMBER]);
        }
        
        $this->registerForm($form);
        $this->_loadFmcOptions();
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
            $this->setErrorFlashMessage('Invalid request', 'member');
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
                
                $this->redirect('member');
            }
        } else {
            $fields = ['fmc_id', 'member_type_id', 'name', 'code'];
            $form->populate($this->_model->selectFieldsById($fields, $id));
        }
        
        $this->registerForm($form);
        $this->_loadFmcOptions();
        $this->_breadcrumb('Edit');
        
        return $this->getViewModel();
    }

    
    /**
     * load FMC options
     */
    private function _loadFmcOptions()
    {
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmc());
        LookupStore::register('type', [self::MEMBER => 'Member', self::AUCTIONEER => 'Auctioneer']);
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Settings - Members - ' . $page);
        $this->setPageHeading('Settings');
        $this->setPageSubheading('Members');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings', $this->view->actionUrl('settings'));
        $breadrumb->add('Members', $this->view->actionUrl('member'));
        $breadrumb->add($page);
    }
    
}
