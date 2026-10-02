<?php
/**
 * Application Reconciliation Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Utility\DcbPqrReconciliation;


class ReconciliationController extends ApplicationController
{

    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->adminOrCeoOnly();
        $this->currentTab(Tab::INTERFACE_PQR);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->registerPairs();
        $generator = new DcbPqrReconciliation();
        $generator->run();
        $this->view->setVariable('generator', $generator);
        
        $this->_breadcrumb($generator->reportingDate());
        
        return $this->getViewModel();
    }
    
    /**
     * test action
     * 
     * @return \Web\View\ViewModel
     */
    public function testAction()
    {
        $this->registerPairs();
        $generator = new DcbPqrReconciliation();
        $generator->run();
        $report = $generator->report();
        echo '<pre>';
        print_r($report);
        exit;
        
        $this->_breadcrumb($generator->reportingDate());
        
        return $this->getViewModel();
    }
    

    /**
     * set breadcrumb
     */
    private function _breadcrumb($date)
    {
        $this->setHeadTitle('PQR - DCB-PQR Reconciliation');
        $this->setPageHeading('DCB-PQR Reconciliation');
        $this->setPageSubheading('As on ' . $date);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('PQR', $this->view->actionUrl('pqr'));
        $breadrumb->add('DCB-PQR Reconciliation');
    }
    
    
    
}
