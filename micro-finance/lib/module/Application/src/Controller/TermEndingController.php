<?php
/**
 * Application TermEnding Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\FilterForm;
use Application\Utility\TermEndingReport;

use Model\Fmc;

use Web\View\Support\LookupStore;
use Web\Utility\CsvWriter;


class TermEndingController extends ApplicationController
{ 
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    /**
     * 
     * @var \Application\Utility\NonRepaymentReport
     */
    private $_generator;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_fmcModel = new Fmc();
        $this->currentTab(Tab::INTERFACE_DCB);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_loadReport();
        
        $this->registerPairs(['fmc', 'source', 'type']);
        $this->_loadOptions();
        $this->view->setVariable('generator', $this->_generator);
        if ($this->_generator->hasReport()) {
            $this->addDatatableScripts();
            $this->addModuleScript('term-ending');
        }
       
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * print action
     * 
     * @return \Web\View\ViewModel
     */
    public function printAction()
    {
        $this->_loadReport();
        if (! $this->_generator->hasReport()) {
             die('No records found');
        }
        
        $this->view->setVariable('generator', $this->_generator);
        $this->layout->setLayout('');
        $this->registerPairs(['fmc', 'source', 'type']);
        $this->_loadOptions();
        
        return $this->getViewModel();
    }
    
    /**
     * download action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadAction()
    {
        $this->_loadReport();
        if (! $this->_generator->hasReport()) {
             die('No records found');
        }
        
        $this->registerPairs(['fmc', 'source', 'type']);
        $fmcList = $this->view->lookupStore('fmc');
        $heading = [
            'Sl. No.', 'FMC', 'Member', 'Code', 'Loan Amount', 'Date',
            'Source', 'Type', 'Tenure', 'Term End Date', 'Pending Instalments', 'PIOS',
        ];
        
        $title = 'FMC ' . $this->_generator->fmc() . ' Term Ending Loans';
        $export = new CsvWriter($title);
        $export->row($heading);
        
        $report = $this->_generator->report();
        $members = $report['members'];
        $summary = $report['summary'];
        foreach ($report['list'] as $item) {
            $member = $members[$item->member_id];
            $row = [
                $this->view->counter(),
                $fmcList[$member->fmc_id],
                $member->name,
                $member->code,
                $this->view->integer($item->loan_amount),
                $this->view->datepicker($item->loan_date),
                $this->view->lookup('source', $item->loan_source_id),
                $this->view->lookup('type', $item->loan_type_id),
                $item->tenure,
                $this->view->datepicker($item->end_date),
                $item->tenure - $item->paid_tenures,
                $summary[$item->id]->balance_actual_principal_interest_outstanding,
            ];
            $export->row($row);
        }
        
        $export->finish();
        
        return $this->getViewModel();
    }
    
    /**
     * load report
     */
    private function _loadReport()
    {
        $values = $this->_getFilter();
        $fmcId = 0;
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $fmcId = $values['fmc'];
            }
        }
        
        $this->_generator = new TermEndingReport();
        $this->_generator->setFmcId($fmcId)
                         ->setInstalmentFilter($values['instalment']);
        
        $this->_generator->run();
    }
    
    /**
     * get filter
     * 
     * @return array
     */
    private function _getFilter()
    {
        $fields = ['fmc', 'instalment'];
        $filters = new FilterForm();
        $filters->addFields($fields);
        $filters->numeric($fields);
        $filters->addFilter('instalment', $filters->getMinMaxFilter(4));
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        return $values;
    }
    
    /**
     * load options
     */
    private function _loadOptions()
    {
        $instalmentOptions = [
            0 => 'Within next 3 months',
            1 => 'This month',
            2 => 'Next month',
            3 => 'Next 2 months',
            4 => 'Next 3 months',
        ];
        LookupStore::register('instalment', $instalmentOptions);
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Term ending Loans');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Term ending Loans');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Term ending Loans');
    }
    
}
