<?php
/**
 * Backend Deduction Report Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Form\Deduction\DeductionReportForm;
use Backend\Utility\DeductionReport;

use Model\{DeductionMemberDaily, Fmc, DeductionFmc};

use Web\Utility\{DateHelper, CsvWriter};
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;


class DeductionReportController extends BackendController
{
    /**
     *
     * @var \Model\DeductionMemberDaily
     */
    private $_model;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::DEDUCTION_BILL);
        $this->_model = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
    }
    
   /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $minDate = $this->_model->selectMin('deduction_date');
        $minDatepicker = DateHelper::datepicker($minDate);
        $maxDate = DateHelper::datepicker();
        $form = new DeductionReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new DeductionReport();
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']))
                          ->setGroupId($values['group'])
                          ->setFmcId($values['fmc']);
                $generator->run();
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                    LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
                }
            }
        } else {
            $financialYear = DeductionHelper::financialYear();
            $populate = [
                'start_date' => $financialYear['start'],
                'end_date' => $financialYear['end'],
                'group' => 3,
                'fmc' => 0,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
        
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmc());
        
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
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
        $generator = $this->_getGenerator();
        $this->view->setVariable('generator', $generator);
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        $this->layout->setLayout('');
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        
        return $this->getViewModel();
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
        $report = $generator->report();
        $filters = $report['filters'];
        $fmc = $report['filters']['fmc'];
        $members = $this->view->lookupStore('member_code');
        $list = $report['report'];
        $fields = array_keys($report['titles']);
        $deductionTypes = DeductionHelper::deductionTypes();
        
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
        $fields = ['fmc', 'group', 'start', 'end'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->load();
        
        if (0 == $values['group']) {
            die('Invalid request');
        }
        
        if (0 == $values['fmc']) {
            die('Invalid request');
        }
        
        $fmcModel = new Fmc();
        if (! $fmcModel->hasId($values['fmc'])) {
            die('Invalid request');
        }
        
        $deductionFmcModel = new DeductionFmc();
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $values['fmc']);
        
        $brachDeductionIds = $deductionFmcModel->selectColumns('deduction_type_id', $sql->getWhere());
        $groupedDeductions = DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
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
        
        $generator = new DeductionReport();
        $generator->setStartDate($start->format(DateHelper::MYSQL_DATE))
                  ->setEndDate($end->format(DateHelper::MYSQL_DATE))
                  ->setGroupId($values['group'])
                  ->setFmcId($values['fmc']);
        
        $generator->run();
        $report = $generator->report();
        if (0 == $report['total']) {
            die('No records found');
        }
        
        return $generator;
    }
    
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('Deduction Report');
        $this->setPageHeading('Deduction  Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add('Deduction Report');
    }
    
}
