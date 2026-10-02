<?php
/**
 * Application NonRepaying Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\FilterForm;
use Application\Utility\NonRepaymentReport;

use Model\Fmc;

use Web\View\Support\LookupStore;
use Web\Utility\CsvWriter;


class NonRepayingController extends ApplicationController
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
        
        $this->registerPairs(['fmc']);
        $this->_loadOptions();
        $this->view->setVariable('generator', $this->_generator);
        if ($this->_generator->hasReport()) {
            $this->addDatatableScripts();
            $this->addModuleScript('non-repaying');
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
        $this->registerPairs(['fmc']);
        
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
        
        $this->registerPairs(['fmc']);
        $fmcList = $this->view->lookupStore('fmc');
        $heading = [
            'Sl. No.', 'FMC', 'Member', 'Code', 'Loan Amount', 'Date', 'Loan A/C',
            'Tenure', 'Last Payment', 'Last Paid Instalment', 'Latest Unpaid Instalments',
            'Total Unpaid Instalments', 'PIOS', 'Ageing',
        ];
        
        $title = 'FMC ' . $this->_generator->fmc() . ' Non-repaying Loans';
        $export = new CsvWriter($title);
        $export->row($heading);
        
        $report = $this->_generator->report();
        $members = $report['members'];
        $schedules = $report['schedule'];
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
                $item->account_number,
                $item->tenure,
                $schedules[$item->id]['date'],
                $schedules[$item->id]['instalment'],
                $schedules[$item->id]['pending'],
                $schedules[$item->id]['unpaid'],
                $summary[$item->id]->balance_actual_principal_interest_outstanding,
                $summary[$item->id]->ageing,
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
        
        $this->_generator = new NonRepaymentReport();
        $this->_generator->setFmcId($fmcId)
                         ->setInstalmentFilter($values['instalment'])
                         ->setPaymentFilter($values['payment'])
                         ->setAgeingFilter($values['ageing'])
                         ->setUnpaidFilter($values['unpaid']);
        
        $this->_generator->run();
    }
    
    /**
     * get filter
     * 
     * @return array
     */
    private function _getFilter()
    {
        $fields = ['fmc', 'instalment', 'payment', 'ageing', 'unpaid'];
        $filters = new FilterForm();
        $filters->addFields($fields);
        $filters->numeric($fields);
        $filters->addFilter('instalment', $filters->getMinMaxFilter(11));
        $filters->addFilter('payment', $filters->getMaxTwoFilter());
        $filters->addFilter('ageing', $filters->getMinMaxFilter(9));
        $filters->addFilter('unpaid', $filters->getMinMaxFilter(9));
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
            0 => '1 or more',
            1 => 'More than 1',
            2 => 'More than 2',
            3 => 'More than 3',
            4 => 'More than 4',
            5 => 'More than 5',
            6 => 'More than 6',
            7 => 'More than 12',
            8 => 'More than 18',
            9 => 'More than 24',
            10 => 'More than 30',
            11 => 'More than 36',
        ];
        LookupStore::register('instalment', $instalmentOptions);
        
        $paymentOptions = [
            0 => 'With or without previous payment(s)',
            1 => 'With payment before non-paying instalments',
            2 => 'No payments',
        ];
        LookupStore::register('payment', $paymentOptions);
        
        $ageingOptions = [
            0 => 'All',
            1 => 'Less than 30 days',
            2 => '30+ days',
            3 => '60+ days',
            4 => '90+ days',
            5 => '120+ days',
            6 => '180+ days',
            7 => '210+ days',
            8 => '270+ days',
            9 => 'Above 365 days',
        ];
        LookupStore::register('ageing', $ageingOptions);
        
        $unpaidOptions = [
            1 => 'More than 1',
            2 => 'More than 2',
            3 => 'More than 3',
            4 => 'More than 4',
            5 => 'More than 5',
            6 => 'More than 6',
            7 => 'More than 12',
            8 => 'More than 18',
            9 => 'More than 24',
            10 => 'More than 30',
            11 => 'More than 36',
        ];
        LookupStore::register('unpaid', $unpaidOptions);
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Non-repaying Loans');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Non-repaying Loans');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Non-repaying Loans');
    }
    
}
