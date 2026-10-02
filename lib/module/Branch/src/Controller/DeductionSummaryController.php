<?php
/**
 * Branch Deduction Summary Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;
use Branch\Form\Deduction\SummaryReportForm;
use Branch\Utility\DeductionSummaryReport;

use Model\DeductionMemberDaily;

use Web\Utility\{DateHelper, CsvWriter};
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;


class DeductionSummaryController extends BranchController
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
        $this->_model = new DeductionMemberDaily();
        $this->currentTab(Tab::DEDUCTION_BILL_NEW);
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
        $form = new SummaryReportForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid($this->_branch)) {
                $values = $form->getValues();
                $generator = new DeductionSummaryReport($this->_branch);
                $generator->setStartDate(DateHelper::fromDatepicker($values['start_date']))
                          ->setEndDate(DateHelper::fromDatepicker($values['end_date']));
                $generator->run();
                $report = $generator->report();
                if (0 == $report['total']) {
                    $form->addError('No records found');
                } else {
                    $this->view->setVariable('generator', $generator);
                    $this->registerPairs(['member_name', 'member_code']);
                }
            }
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('min_date', $minDatepicker);
        $this->view->setVariable('max_date', $maxDate);
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
        $this->registerPairs(['member_name', 'member_code']);
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
        $this->registerPairs(['member_name', 'member_code']);
        $fmc = $this->_branch->name();
        $report = $generator->report();
        $filters = $report['filters'];
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
        $fields = ['start', 'end'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->load();
        
        if (false === ($start = DateHelper::fromFormat($values['start'], 'Ymd'))) {
            die('Invalid request');
        }
        
        if (false === ($end = DateHelper::fromFormat($values['end'], 'Ymd'))) {
            die('Invalid request');
        }
        
        if ($start > $end) {
            die('Invalid request');
        }
        
        $generator = new DeductionSummaryReport($this->_branch);
        $generator->setStartDate($start->format(DateHelper::MYSQL_DATE))
                  ->setEndDate($end->format(DateHelper::MYSQL_DATE));
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
        $this->setHeadTitle('Deduction Bills - Summary Report');
        $this->setPageHeading('Deduction Bills');
        $this->setPageSubheading('Summary Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction'));
        $breadrumb->add('Summary Report');
    }
    
}
