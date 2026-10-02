<?php
/**
 * Backend Deduction Summary Report Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Form\Deduction\DeductionSummaryForm;
use Backend\Utility\DeductionSummaryReport;

use Model\{DeductionMemberDaily, Fmc};

use Web\Utility\{DateHelper, CsvWriter};
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Model\PairModel;


class DeductionSummaryReportController extends BackendController
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
        $form = new DeductionSummaryForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new DeductionSummaryReport();
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']))
                          ->setFmcId($values['fmc']);
                $generator->run();
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                }
            }
        } else {
            $populate = [ 'fmc' => 0];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
        
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmc());
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
        $this->layout->setLayout('');
        
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
        $report = $generator->report();
        $filters = $report['filters'];
        $fmc = $report['filters']['fmc'];
        $members = $this->view->lookupStore('member_code');
        $list = $report['report'];
        $deductions = $report['deductions'];
        $deductionIds = array_keys($deductions);
        $summary = $report['summary'];
        $dates = $report['dates'];
        
        $title1 = 'FMC ' . $fmc . ' Sanghom Statement '
                . $this->view->formatDateTime('d-m-Y', $filters['start']) . ' to '
                . $this->view->formatDateTime('d-m-Y', $filters['end']);
        $title2 = 'FMC ' . $fmc . ' - Sanghom Statement '
                . $filters['period'][0] . ' to ' . $filters['period'][1];
        
        $export = new CsvWriter($title1);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title2]);
        
        $heading = ['Sl. No.', 'Code', 'Name', 'Catching'];
        foreach ($deductions as $type) {
            $heading[] = $type;
        }
        $heading[] = 'Total deduction';
        $export->row($heading);
        foreach ($dates as $key => $date) {
            $this->view->counter(0);
            $export->row([$this->view->datepicker($date)]);
            foreach ($members as $memberId => $code) {
                if (isset($list[$key][$memberId])) {
                    $row = [
                        $this->view->counter(),
                        $code,
                        $this->view->lookup('member_name', $memberId),
                        $report['catch'][$key][$memberId],
                    ];

                    foreach ($deductionIds as $deductionId) {
                        if (isset($list[$key][$memberId][$deductionId])) {
                            $row[] = $list[$key][$memberId][$deductionId];
                        } else {
                            $row[] = '';
                        }
                    }
                    $row[] = $summary['members'][$key][$memberId];
                    $export->row($row);
                }
            }
            $export->row(['']);
        }
        
        $footer = [ 'Total', '', '', $summary['catch'] ];
        foreach ($summary['deductions'] as $value) {
            $footer[] = $value;
        }
        
        $footer[] = array_sum($summary['deductions']);
        $export->row($footer);

        $export->finish();
        
        return $this->getViewModel();
    }
    
    private function _getGenerator()
    {
        $filters = new FilterForm();
        $fields = ['fmc', 'start', 'end'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->load();
        
        if (0 == $values['fmc']) {
            die('Invalid request');
        }
        
        $fmcModel = new Fmc();
        if (! $fmcModel->hasId($values['fmc'])) {
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
        
        $generator = new DeductionSummaryReport();
        $generator->setStartDate($start->format(DateHelper::MYSQL_DATE))
                  ->setEndDate($end->format(DateHelper::MYSQL_DATE))
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
        $this->setPageHeading('Deduction Summary Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add('Deduction Summary Report');
    }
    
}
