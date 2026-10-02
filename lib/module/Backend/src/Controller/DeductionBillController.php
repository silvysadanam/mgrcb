<?php
/**
 * Backend Deduction Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Utility\DeductionBillReport;

use Model\{Fmc, AuctionChit};


class DeductionBillController extends BackendController
{
    /**
     * 
     * @var \Model\Fmc
     */
    private $_fmcModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::DEDUCTION_BILL);
        $this->_fmcModel = new Fmc();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $report = new DeductionBillReport();
        $this->view->setVariable('report', $report->summary());
        $this->view->setVariable('fmc', $this->_fmcModel->selectPairs(['id', 'name'], null, 'name'));
        $this->_breadcrumb('Summary');
        
        return $this->getViewModel();
    }
    
   
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->getParamId();
        $valid = false;
        if ($id > 0) {
            $valid = $this->_fmcModel->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill');
        }
        
        $report = new DeductionBillReport();
        $result = $report->monthly($id);
        if (empty($result['fmc'])) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill');
        }
        
        $this->view->setVariable('report', $result);
        $this->view->setVariable('fmcId', $id);
        $this->_loadFmc($id);
        $this->_breadcrumb('FMC Monthly Report');
        
        return $this->getViewModel();
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Deduction Bills Report - ' . $page);
        $this->setPageHeading('Deduction Bills Report');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills Report', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add($page);
    }
    
    /**
     * load fmc name
     * 
     * @param int $id
     */
    private function _loadFmc($id)
    {
        $this->view->setVariable('fmc', $this->_fmcModel->selectColumnById('name', $id));
    }
    
}
