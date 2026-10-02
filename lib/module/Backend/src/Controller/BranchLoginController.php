<?php
/**
 * Backend BranchLogin Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;

use Model\{Administrator, Impersonate};

use Shared\Constant\ManagerType;

use Web\Utility\DateHelper;


class BranchLoginController extends BackendController
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
        $this->currentTab(Tab::IMPERSONATE);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $sql = $this->_model->getSql();
        $sql->whereId('manager_type', ManagerType::FMC);
        $where = $sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'name', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('list');
        $this->registerPairs(['fmc']);
        
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * select action
     * 
     * @return \Web\View\ViewModel
     */
    public function selectAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $valid = ManagerType::FMC == $this->_model->selectColumnById('manager_type', $id);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'branch-login');
        }
        
        $impersonateModel = new Impersonate();
        $timestamp = DateHelper::timestamp();
        $expiry = $timestamp + 60;
        
        $insert = [
            'administrator_id' => $this->getUserId(),
            'manager_id' => $id,
            'created_at' => $timestamp,
            'expiry' => $expiry,
        ];
        
        $code = md5(serialize($insert));
        $insert['code'] = $code;
        $loginId = $impersonateModel->insert($insert);
        $url = 'branch/impersonate/index/id/' . $loginId . '/code/' . $code;
        
        $this->redirectBase($url);
        
        //``, ``, `code`, `created_at`, `expiry`, `used`, `used_at`
        
        return $this->getViewModel();
    }
    
    /**
     * set page breadcrumb
     */
    private function _breadcrumb()
    {
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Branch Login', $this->view->actionUrl('branch-login'));
        $breadrumb->add('Select Manager');
        
        $this->setHeadTitle('Branch Login :: Select Manager');
        $this->setPageHeading('Branch Login');
        $this->setPageSubheading('Select Manager');
    }

}
