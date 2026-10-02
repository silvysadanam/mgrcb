<?php
/**
 * Application Main Controller
 * 
 * @category Application
 */

namespace Application;

use Web\Mvc\ActionController;
use Web\Core\Registry;
use Web\Database\Table\Db;
use Web\View\Support\LookupStore;

use Shared\Model\PairModel;
use Application\Utility\LoanHelper;
use Application\Constant\ManagerType;
use Application\Utility\Branch;
use Application\Utility\Paging;


class ApplicationController extends ActionController
{
    /**
     *
     * @var int
     */
    protected $_userType = 0;
    
    /**
     * 
     * @var \Application\Utility\Branch
     */
    protected $_branch;
    
    protected $_fmcID;


    public function __construct()
    {
        $module = Registry::get('module');
        parent::__construct($module['session'], Db::ADMINISTRATOR);
        
        $isBranch = false;
        if ($this->session->isLogged()) {
            $this->_userType = $this->session->get('manager_type');
            if (ManagerType::BRANCH == $this->_userType) {
                $isBranch = true;
                $this->_branch = new Branch($this->session->get('fmc_id'));
                $this->addLayoutVariable('branch', $this->_branch);
                $this->_fmcID = $this->_branch->id();
            } else {
                $this->_fmcID = LoanHelper::MF_ACCOUNT_ID;
            }
        }
        
        $this->addLayoutVariable('isBranch', $isBranch);
        
        if (! $this->isAjaxRequest()) {
            $this->plugin('head')->setTitlePrefix(Registry::get('siteName') . ' :: ');
            $this->setHeadTitle('Home');
            $this->layout->setLayout('index');
            $this->view->setVariable('showFormFlash', true);
            $this->plugin('tab')->set(1);
            $this->setTheme('chameleon');
            $this->view->setVariable('today', \Web\Utility\DateHelper::datepicker());
            if ($this->session->isLogged()) {
                $this->plugin('script')->addModuleBody('common');
            }
        }
        
        Registry::set('site_id', 1);
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
     * Generate Paging
     * 
     * @param int $total 
     * @return StdClass 
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
     * access restricted message
     * 
     * @param string $url
     */
    protected function accessRestricted($url = '/')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    /**
     * add data tables scripts
     */
    protected function addDatatableScripts()
    {
        $scripts = $this->plugin('script');
        $scripts->addThemeBody('app-assets/vendors/js/tables/datatable/datatables.min.js');
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
     * is manager
     * 
     * @return boolean
     */
    protected function isManager()
    {
        return ManagerType::BRANCH == $this->_userType;
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
     * is finance manager
     * 
     * @return boolean
     */
    protected function isFinance()
    {
        return ManagerType::FINANCE == $this->_userType;
    }
    
    /**
     * is auditor
     * 
     * @return boolean
     */
    protected function isAuditor()
    {
        return ManagerType::AUDITOR == $this->_userType;
    }
    
    /**
     * restrict to admin or ceo only
     * 
     * @return boolean
     */
    protected function adminOrCeoOnly()
    {
        if ($this->isAdmin() || $this->isCeo()) {
            return;
        }
        
        $this->accessRestricted();
    }
    
    /**
     * restrict to finance or auditor only
     * 
     * @return boolean
     */
    protected function financeOrAuditorOnly()
    {
        if ($this->isFinance() || $this->isAuditor()) {
            return;
        }
        
        $this->accessRestricted();
    }
    
    /**
     * allowed user types
     * 
     * @param array $allowed
     */
    protected function allowed($allowed)
    {
        if (! in_array($this->_userType, $allowed)) {
            $this->accessRestricted();
        }
    }
    
    /**
     * restrict to admin only
     * 
     * @return boolean
     */
    protected function adminOnly()
    {
        if (! $this->isAdmin()) {
            $this->accessRestricted();
        }
    }
    
    /**
     * restrict to manager only
     * 
     * @return boolean
     */
    protected function managerOnly()
    {
        if (! $this->isManager()) {
            $this->accessRestricted();
        }
    }
    
    /**
     * get query string parameter
     * 
     * @param string $name
     * @param boolean $integer
     * @return string|int
     */
    protected function getQueryParam($name, $integer = false)
    {
        if (! filter_has_var(INPUT_GET, $name)) {
            if ($integer) {
                return 0;
            }
            
            return '';
        }

        if ($integer) {
            return (int) filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
        }
        
        return filter_input(INPUT_GET, $name, FILTER_SANITIZE_STRING);
    }
    
    /**
     * set link id for flash message
     * 
     * @param int $id
     * @param string $type
     */
    protected function setLinkId($id, $type = 'dcb')
    {
        switch ($type) {
            case 'dcb':
                $title = 'VIEW DCB';
                $url = $this->view->actionUrl('view-dcb/view/id/' . $id);
                $target = ' target="_blank"';
                break;
                
            default:
                $title = 'VIEW DCB';
                $url = $this->view->actionUrl('view-dcb/view/id/' . $id);
                $target = ' target="_blank"';
                break;
        }
        
        $link = '<a href="' . $url . '" title="' .  $title . '" class="btn btn-dark"' 
              . $target . '><i class="ft-external-link"></i> ' .  $title . '</a>';
        
        $this->plugin('link')->set($link);
    }
    
    /**
     * register pairs
     * 
     * @param array $list default options
     */
    protected function registerPairs($list = ['fmc', 'source', 'type', 'bank'])
    {
        $pairModel = new PairModel;
        // loan source
        if (in_array('source', $list)) {
            LookupStore::register('source', $pairModel->loanSource());
        }
        
        // loan type
        if (in_array('type', $list)) {
            LookupStore::register('type', $pairModel->loanType());
        }
        
        if (in_array('fmc_list', $list)) {
                 $columns =['id', 'name'];
                LookupStore::register('fmc', $pairModel->custom('fmc', $columns, " code != ''"));
         }
        // fmc
        if (in_array('fmc', $list)) {
            if ($this->isManager()) {
                LookupStore::register('fmc', $this->_branch->option());
            } else {
               // LookupStore::register('fmc', $pairModel->fmc());
                $columns =['id', 'name'];
                LookupStore::register('fmc', $pairModel->custom('fmc', $columns, " code != 'MF'"));
            }
        }
        
        // date
        if (in_array('date', $list)) {
            LookupStore::register('date', \Application\Utility\LoanHelper::reportingDateOptions());
        }
        
        if (in_array('bank', $list)) {
            $columns =['id', 'head_name'];
            LookupStore::register('bank', $pairModel->custom('rp_head', $columns, "transType ='Bank' and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('expenseHead', $list)) {
            $columns =['id', 'head_name'];
            LookupStore::register('expenseHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Expense', 'Indirect Expense', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('member', $list)) {
            LookupStore::register('member', $pairModel->member());
        }
        if (in_array('incomeHead', $list)) {
            $columns =['id', 'head_name'];
            LookupStore::register('incomeHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Bank') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
        if (in_array('receiptHead', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('receiptHead', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
         if (in_array('donor', $list)) {
            LookupStore::register('donor', $pairModel->donor());
        }
        if (in_array('heads', $list)) {            
            $pairModel = new PairModel();
            $columns =['id', 'head_name'];
            LookupStore::register('heads', $pairModel->custom('rp_head', $columns, "transType in ('Direct Income', 'Indirect Income', 'Direct Expense', 'Indirect Expense', 'Bank', 'Cash') and fmc_id = {$this->_fmcID} and status='N'", 'head_name'));
        }
    }
    
}
