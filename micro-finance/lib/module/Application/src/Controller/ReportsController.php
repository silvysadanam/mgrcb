<?php
/**
 * Branch Deduction Report Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\Reports\RDReportForm;
use Application\Form\Reports\LedgerReportForm;
use Application\Utility\RDReport;
use Application\Utility\IEReport;
use Application\Utility\BSReport;
use Application\Utility\LedgerReport;

use Web\Utility\{DateHelper, CsvWriter};
use Web\Utility\FormHelper;
use Web\View\Support\LookupStore;
use Model\RPTransMember;
use Shared\Form\FilterForm;
use Shared\Utility\ReportHelper;
//use Shared\Utility\DeductionHelper;

class ReportsController extends ApplicationController
{  
    /**
     *
     * @var \Model\DeductionMemberDaily
     */
    private $_model;
    private $_fmc_id;
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new RPTransMember();
        $this->currentTab(Tab::INTERFACE_TRANSACTION);
        $this->_sql = $this->_model->getSql();
        $this->_fmc_id = FormHelper::MF_ACCOUNT_ID;
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
    //    $currentFY = ReportHelper::currentFinancialYear();
     //   $minDatepicker = $currentFY['start'];
     //   $maxDate = $currentFY['end'];
        $form = new RDReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new RDReport($this->_fmc_id);
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']));                          
                $generator->run();
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                   // LookupStore::register('deductionType', ReportHelper::combinedDeductionTypes());
                    $this->registerPairs(['member_name', 'member_code']);
                }
            }
        } else {
            $financialYear = ReportHelper::currentFinancialYear();
            $populate = [
                'start_date' => $financialYear['start'],
                'end_date' => $financialYear['end'],
                'group' => 3,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
       // $this->view->setVariable('min_date', $minDatepicker);
       // $this->view->setVariable('max_date', $maxDate);
        
        $this->view->setVariable('groups', ReportHelper::deductionGroups());
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
     /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function ledgerAction()
    {
        $minDate = '2025-01-01';
        $minDatepicker = DateHelper::datepicker($minDate);
        $maxDate = DateHelper::datepicker();
        $form = new LedgerReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new LedgerReport($this->_fmc_id);
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']))
                          ->setHeadId($values['head']);
                $generator->run();
                $report = $generator->report();
                if (0 == $report['reportTotal']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                    $this->view->setVariable('headID', $values['head']);
                }
            }
        } else {
            $financialYear = ReportHelper::financialYear();
            $populate = [
                'start_date' => $financialYear['start'],
                'end_date' => $financialYear['end'],
                'head' => 0,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['heads', 'heads', 'fmc_list']);
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
        $this->_ledgerBreadcrumb();
        
        return $this->getViewModel();
    }

    private function _ledgerBreadcrumb()
    {
        $this->setHeadTitle('Ledger Account - Reports');
        $this->setPageHeading('Ledger Account');
        $this->setPageSubheading('Reports');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Ledger Account', $this->view->actionUrl('reports'));
        $breadrumb->add('Reports');
    }
    
    public function incomeAction()
    {
        $minDate = '2025-01-01';
        $minDatepicker = DateHelper::datepicker($minDate);
        $maxDate = DateHelper::datepicker();
        $form = new RDReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid($this->_branch)) {
                $values = $form->getValues();
                $generator = new IEReport($this->_fmc_id);
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']));                          
                $generator->run();
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                   // LookupStore::register('deductionType', ReportHelper::combinedDeductionTypes());
                    $this->registerPairs(['member_name', 'member_code']);
                }
            }
        } else {
            $financialYear = ReportHelper::currentFinancialYear();
            $populate = [
                'start_date' => $financialYear['start'],
                'end_date' => $financialYear['end'],
                'group' => 3,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['heads', 'heads', 'fmc_list']);
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
        $this->_incomeBreadcrumb();
        
        return $this->getViewModel();
    }
    private function _incomeBreadcrumb()
    {
        $this->setHeadTitle('Income & Expenditure - Reports');
        $this->setPageHeading('Income & Expenditure');
        $this->setPageSubheading('Reports');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Income & Expenditure', $this->view->actionUrl('reports'));
        $breadrumb->add('Reports');
    }
    /**
     * print action
     * 
     * @return \Web\View\ViewModel
     */
    public function printAction()
    {
        $generator = $this->_getGenerator();
        $this->view->setVariable('generator', $generator);
        //LookupStore::register('deductionType', ReportHelper::combinedDeductionTypes());
        //$this->registerPairs(['member_name', 'member_code']);
        $this->layout->setLayout('');
        $this->view->setVariable('groups', ReportHelper::deductionGroups());
        
        return $this->getViewModel();
    }
    
     public function bsreportAction()
    {
        $minDate = '2025-01-01';
        $minDatepicker = DateHelper::datepicker($minDate);
        $maxDate = DateHelper::datepicker();
        $maxDate = DateHelper::datepicker();
        $form = new RDReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid($this->_fmc_id)) {
                $values = $form->getValues();
                $generator = new BSReport($this->_fmc_id);
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']));                          
                $generator->run();
                //$branchName = "FMC ".$this->_branch->name() ." - Sanghom";
                //$this->view->setVariable('branchName', $branchName);
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                   // LookupStore::register('deductionType', ReportHelper::combinedDeductionTypes());
                    $this->registerPairs(['member_name', 'member_code']);
                }
            }
        } else {
            $financialYear = ReportHelper::currentFinancialYear();
            $populate = [
                'start_date' => $financialYear['start'],
                'end_date' => $financialYear['end'],
                'group' => 3,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        if(isset($values['start_date'])){
            $this->view->setVariable('start_date', $values['start_date']);
        }
        if(isset($values['end_date'])){
            $this->view->setVariable('end_date', $values['end_date']);
        }
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
        
        $this->view->setVariable('groups', ReportHelper::deductionGroups());
        $this->_bsreportBreadcrumb();
        
        return $this->getViewModel();
    }
    
    private function _bsreportBreadcrumb()
    {
        $this->setHeadTitle('Balance Sheet - Reports');
        $this->setPageHeading('Balance Sheet');
        $this->setPageSubheading('Reports');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Balance Sheet', $this->view->actionUrl('reports'));
        $breadrumb->add('Reports');
    }
    /**
     * download action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadAction()
    {
        $generator = $this->_getGenerator();
        $this->registerPairs(['member_name', 'member_code']);
        $fmc = $this->_branch->name();
        $report = $generator->report();
        $filters = $report['filters'];
        $members = $this->view->lookupStore('member_code');
        $list = $report['report'];
        $fields = array_keys($report['titles']);
        $deductionTypes = ReportHelper::deductionTypes();
        
        $title1 = 'FMC ' . $fmc . ' Sanghom Statement '
                . $this->view->formatDateTime('d-m-Y', $filters['start']) . ' to '
                . $this->view->formatDateTime('d-m-Y', $filters['end']);
        $title2 = 'FMC ' . $fmc . ' - Sanghom Statement '
                . $filters['period'][0] . ' to ' . $filters['period'][1];
        
        $export = new CsvWriter($title1);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title2]);
        
        $heading1 = ['Sl', 'Code', 'Name'];
        foreach ($report['deductions'] as $deductionId) {
            $heading1[] = $deductionTypes[$deductionId];
            $heading1[] = '';
            $heading1[] = '';
            $heading1[] = '';
        }
        $export->row($heading1);
        
        $deductionCount = count($report['deductions']);
        $heading2 = ['No', '', ''];
        for ($count = 0; $count < $deductionCount; $count++) {
            $heading2[] = $report['titles']['opening'];
            $heading2[] = $report['titles']['collection'];
            $heading2[] = $report['titles']['withdrawal'];
            $heading2[] = $report['titles']['closing'];
        }
        $export->row($heading2);
        
        foreach ($members as $memberId => $code) {
            if (isset($list[$memberId])) {
                $row = [
                    $this->view->counter(),
                    $code,
                    $this->view->lookup('member_name', $memberId),
                ];
                
                foreach ($report['deductions'] as $deductionId) {
                    foreach ($fields as $field) {
                        $row[] = $list[$memberId][$deductionId][$field];
                    }
                }
                $export->row($row);
            }
        }
        
        $footer = [ 'Total', '', '' ];
        foreach ($report['deductions'] as $deductionId) {
            foreach ($fields as $field) {
                $footer[] = $report['report_total'][$deductionId][$field];
            }
        }
        $export->row($footer);

        $export->finish();
        
        return $this->getViewModel();
    }
    
    private function _getGenerator()
    {
        $filters = new FilterForm();
        $fields = ['group', 'start', 'end'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->load();
        
        if (0 == $values['group']) {
            die('Invalid request');
        }
        
        $deductions = $this->_branch->deductionTypes();
        $brachDeductionIds = array_keys($deductions);
        $groupedDeductions = ReportHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
        if (! isset($groupedDeductions[$values['group']])) {
            die('Invalid request');
        }
        
        if (false === ($start = DateHelper::fromFormat($values['start'], 'Ymd'))) {
            die('Invalid request');
        }
        
        if (false === ($end = DateHelper::fromFormat($values['end'], 'Ymd'))) {
            die('Invalid request');
        }
        
        if ($start > $end) {
            die('Invalid request');
        }
        
        $generator = new RDReport($this->_branch);
        $generator->setStartDate($start->format(DateHelper::MYSQL_DATE))
                  ->setEndDate($end->format(DateHelper::MYSQL_DATE))
                  ->setGroupId($values['group']);
        $generator->run();
        $report = $generator->report();
        if (0 == $report['total']) {
            die('No records found');
        }
        
        return $generator;
    }
    
    /**
     * set breadcrumb
     * 
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('Receipts & Payment - Reports');
        $this->setPageHeading('Receipts & Payment');
        $this->setPageSubheading('Reports');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Receipts & Payment', $this->view->actionUrl('reports'));
        $breadrumb->add('Reports');
    }
    
}
