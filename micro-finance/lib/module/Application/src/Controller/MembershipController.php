<?php
/**
 * Application Membership Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;

use Model\{Member, Fmc};

use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\Membership;

use Web\Core\{Registry, Server};


class MembershipController extends ApplicationController
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
        $this->_model = new Member();
        $this->_fmcModel = new Fmc();
        $this->currentTab(Tab::INTERFACE_MEMBERSHIP);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list();
        $this->_loadMemberTypes();
        $this->_breadcrumb('All');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->_getId();
        
        $membership = new Membership($id);
        $membership->load();
        $this->view->setVariable('membership', $membership);
        $this->_breadcrumb('View');
        $this->_setAssetUrl();
        
        return $this->getViewModel();
    }
    
    
    /**
     * load membership list
     */
    private function _list()
    {
        $filters = new FilterForm();
        $fields = ['fmc', 'type'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $filters->addFilter('type', $filters->getMaxTwoFilter());
        $fmcIds = $this->_fmcModel->selectIds();
        array_unshift($fmcIds, 0);
        $filters->addFilter('fmc', $filters->getCollectionFilter($fmcIds));
        $values = $filters->getFilteredValues();
        
        $sql = $this->_model->getSql();
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $sql->whereId('m.fmc_id', $values['fmc']);
            }
        }
        
        if ($values['type'] > 0) {
            $sql->whereId('m.member_type_id', $values['type']);
        }
        
        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_model->getTableQuoted() . ' `m` '
               . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() 
               . ' `f` ON `f`.`id` = `m`.`fmc_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`, `m`.`fmc_id`, '
                    . '`m`.`member_type_id` ' . $query 
                    . ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('filters', $filters);
        $this->registerPairs(['fmc']);
        $this->addDatatableScripts();
        $this->addModuleScript('list');
    }
    
    /**
     * load member types
     */
    private function _loadMemberTypes()
    {
        $types = [
            self::MEMBER => 'Member',
            self::AUCTIONEER => 'Auctioneer',
        ];
        
        LookupStore::register('type', $types);
    }
    
    /**
     * get member id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_model->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'membership');
        }
        
        return $id;
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Membership - ' . $page);
        $this->setPageHeading('Membership');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Membership', $this->view->actionUrl('membership'));
        $breadrumb->add($page);
    }
    
    /**
     * set member assets url
     * 
     * @return null
     */
    private function _setAssetUrl()
    {
        if ('www.fwskollam.com' == Server::host()) {
            Registry::set('member_assets', '/fws/assets/');
            return;
        }
        
        Registry::set('member_assets', 'http://www.fws.loc/assets/');
    }
    
}
