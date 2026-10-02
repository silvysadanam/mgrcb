<?php
/**
 * Branch Main Controller
 * 
 * @category Branch
 */

namespace Branch;

use Web\Mvc\ActionController;
use Web\Core\Registry;
use Web\Core\Server;
use Web\Database\Table\Db;
use Web\Utility\ActionLinkHelper;
use Web\View\Support\LookupStore;

use Branch\Utility\{BranchStore, Paging};

use Shared\Model\PairModel;


class BranchController extends ActionController
{
    /**
     *
     * @var int
     */
    protected $_userType = 0;
    
    /**
     *
     * @var \Branch\Utility\BranchStore
     */
    protected $_branch;
    protected $_fmcID;

    public function __construct()
    {
        $module = Registry::get('module');
        parent::__construct($module['session'], Db::ADMINISTRATOR);
        $branch = '';
        if ($this->session->isLogged()) {
            $this->_userType = $this->session->get('manager_type');
            $this->_branch = new BranchStore($this->session->get('fmc_id'));
            $branch = 'FMC - ' . $this->_branch->code() . ' | ';
            $this->_fmcID = $this->_branch->id();
            $this->view->setVariable('branch', $this->_branch);
        }
        
        if (! $this->isAjaxRequest()) {
            $this->plugin('head')->setTitlePrefix(Registry::get('siteName') . ' :: Branch | ' . $branch);
            $this->setHeadTitle('Home');
            $this->layout->setLayout('index');
            $this->view->setVariable('showFormFlash', true);
            $this->plugin('script')->addModuleBody('common');
            $this->plugin('tab')->set(1);
            $this->setTheme('chameleon');
        }
    }
    
    /**
     * get current user id
     * 
     * @return int
     */
    public function getUserId()
    {
        return $this->session->getId();
    }
    
    /**
     * get user type
     * 
     * @return int
     */
    public function getUserType()
    {
        return $this->_userType;
    }

    /**
     * load action link helper
     */
    public function loadActionLink()
    {
        ActionLinkHelper::run();
    }
    
    /**
     * check if super admin
     * 
     * @return boolean
     */
    protected function superAdmin()
    {
        return 1 == $this->session->getId();
    }
    
    /**
     * check if administrator
     * 
     * @return boolean
     */
    protected function administrator()
    {
        return true;
    }
    
    /**
     * check if administrator
     */
    protected function administratorOnly()
    {
        return;
    }
    
    /**
     * check if administrator
     */
    protected function superAdminOnly()
    {
        if (! $this->superAdmin()) {
            $this->accessRestricted();
        }
    }
    
    /**
     * Generate Paging
     * 
     * @param int $total 
     * @return \stdClass 
     */
    protected function getPaging($total = 0, $limit = null, $generateLinks = true)
    {
        $paging = new Paging();
        $result = $paging->run($total, $limit, $generateLinks);
        $this->view->setVariable('paging', $result->html);
        $this->view->setVariable('pagingInfo', $result->info);
        $this->view->counter($result->sqlOffset);
        
        return $result;
    }
    
    /**
     * Action link cancel redirect
     */
    protected function cancelRedirect()
    {
        $this->redirect(ActionLinkHelper::getRedirectLink());
    }

    /**
     * check user types allowed
     * 
     * @param string|array $userTypes
     * @return boolean
     */
    protected function allowed($userTypes)
    {
        if (is_array($userTypes)) {
            if (in_array($this->_userType, $userTypes)) {
                return true;
            }
        } else {
            if ($this->_userType == $userTypes) {
                return true;
            }
        }
        $this->accessRestricted();
    }
    
    /**
     * check access declined for user types
     * 
     * @param string|array $userTypes
     * @return boolean
     */
    protected function declined($userTypes)
    {
        if (is_array($userTypes)) {
            if (! in_array($this->_userType, $userTypes)) {
                return true;
            }
        } else {
            if ($this->_userType != $userTypes) {
                return true;
            }
        }
        $this->accessRestricted('/');
    }
    
    /**
     * check delete request by controller name
     * 
     * @param string $controller
     * @return boolean
     */
    protected function checkDeleteRequest($controller)
    {
        $valid = false;
        $referer = Server::referer();
        if (! empty($referer)) {
            $actionUrl = $this->view->actionUrl($controller);
            if (false !== strpos($referer, $actionUrl)) {
                $valid = 0 === strpos($referer, Server::protocol() . '://' . Server::host() . $actionUrl);
            }
        }
        if (! $valid) {
            $this->accessRestricted($controller);
        }
        
        return true;
    }
    
    /**
     * access restricted message
     * 
     * @param string $url
     */
    protected function accessRestricted($url = '/')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    /**
     * invalid access message
     * 
     * @param string $url
     */
    protected function invalidAccess($url = '')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    /**
     * enable tinyMCE editor
     */
    protected function tinymceEditor()
    {
        $this->layout->addScriptBlock('tinymce');
    }
    
    /**
     * register pairs
     * 
     * @param array $list default options
     */
    protected function registerPairs($list = ['member'], $active = false)
    {
        if (in_array('member', $list)) {
            LookupStore::register('member', $this->_branch->memberLookup($active));
        }
        
        if (in_array('member_name', $list)) {
            LookupStore::register('member_name', $this->_branch->memberNames($active));
        }
        
        if (in_array('member_code', $list)) {
            LookupStore::register('member_code', $this->_branch->memberCodes($active));
        }
        
        if (in_array('donor', $list)) {
            LookupStore::register('donor', $this->_branch->donorLookup($active));
        }
        
        if (in_array('donor_name', $list)) {
            LookupStore::register('donor_name', $this->_branch->donorNames($active));
        }
        
        if (in_array('donor_code', $list)) {
            LookupStore::register('donor_code', $this->_branch->donorCodes($active));
        }
        
        if (in_array('medicine', $list)) {
            $pairModel = new PairModel();
            LookupStore::register('medicine', $pairModel->medicine());
        }
         if (in_array('heads', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('heads', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Direct Expense', 'Indirect Expense', 'Bank', 'Cash') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('receiptHead', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('receiptHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
        if (in_array('incomeHead', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('incomeHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
        if (in_array('expenseHead', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('expenseHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Expense', 'Indirect Expense', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
        if (in_array('bank', $list)) {
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('bank', $pairModel->custom('rp_head', $columns, "transType ='Bank' and fmc_id = {$this->_fmcID} and status='N'"));
        }
    }
    
}
