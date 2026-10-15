<?php
/**
 * Backend Main Controller
 * 
 * @category Backend
 */

namespace Backend;

use Web\Mvc\ActionController;
use Web\Core\Registry;
use Web\Core\Server;
use Web\Database\Table\Db;
use Web\Utility\ActionLinkHelper;
use Backend\Utility\Paging;
use Web\View\Support\LookupStore;
use Application\Constant\ManagerType;
use Shared\Model\PairModel;
use Web\Utility\FormHelper;

class BackendController extends ActionController
{
    /**
     *
     * @var int
     */
    protected $_userType = 0;
        
    public function __construct()
    {
        $module = Registry::get('module');
        $isBranch = false;
        parent::__construct($module['session'], Db::ADMINISTRATOR);
        if (! $this->isAjaxRequest()) {
            $this->plugin('head')->setTitlePrefix(Registry::get('siteName') . ' :: Backend | ');
            $this->setHeadTitle('Home');
            $this->layout->setLayout('index');
            $this->view->setVariable('showFormFlash', true);
            $this->plugin('script')->addModuleBody('common');
            $this->plugin('tab')->set(1);
            $this->setTheme('chameleon');
        }
        $this->_fmcID = FormHelper::FWS_ACCOUNT_ID;
        $this->addLayoutVariable('isBranch', $isBranch);
        if ($this->session->isLogged()) {
            $this->_userType = $this->session->get('manager_type');
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
     * is admin
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        return ManagerType::ADMIN == $this->_userType;
    }
    
     /**
     * is CEO
     * 
     * @return boolean
     */
    protected function isCeo()
    {
        return ManagerType::CEO == $this->_userType;
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
    
    protected function isManager()
    {
        return ManagerType::BRANCH == $this->_userType;
    }
    
     /**
     * is finance manager
     * 
     * @return boolean
     */
    protected function isFinance()
    {
        return ManagerType::FINANCE == $this->_userType;
    }

    protected function isAuditor()
    {
        return ManagerType::AUDITOR == $this->_userType;
    }
    
    protected function addDatatableScripts()
    {
        $scripts = $this->plugin('script');
        $scripts->addThemeBody('app-assets/vendors/js/tables/datatable/datatables.min.js');
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
    protected function registerPairs($list = ['fmc'])
    {
        $pairModel = new PairModel;
        // fmc
        if (in_array('fmc', $list)) {
            LookupStore::register('fmc', $pairModel->fmc());
        }
        if (in_array('heads', $list)) {            
            $columns =['id', 'head_name'];
            LookupStore::register('heads', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Direct Expense', 'Indirect Expense', 'Bank', 'Cash') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('receiptHead', $list)) {            
            $columns =['id', 'head_name'];
            LookupStore::register('receiptHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('member', $list)) {
            LookupStore::register('member', $pairModel->member());
        }

        if (in_array('donor', $list)) {
            LookupStore::register('donor', $pairModel->donor());
        }
        if (in_array('panchayathBrand', $list)) {
            $pairModel = new PairModel();
            LookupStore::register('panchayathBrand', $pairModel->panchayathBrand());
        }
        if (in_array('diseaseTypes', $list)) {
            $pairModel = new PairModel();
            LookupStore::register('diseaseTypes', $pairModel->diseaseTypes());
        }
        if (in_array('designationType', $list)) {
            $pairModel = new PairModel();
            LookupStore::register('designationType', $pairModel->designationType());
        }
        if (in_array('incomeHead', $list)) {            
            $columns =['id', 'head_name'];
            LookupStore::register('incomeHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
        if (in_array('expenseHead', $list)) {            
            $columns =['id', 'head_name'];
            LookupStore::register('expenseHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Expense', 'Indirect Expense', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
        if (in_array('bank', $list)) {
            $columns =['id', 'head_name'];
            LookupStore::register('bank', $pairModel->custom('rp_head', $columns, "transType ='Bank' and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        
    }
    
}
