<?php
/**
 * Backend Deduction Daily Report Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Form\Deduction\DeductionDailyForm;
use Backend\Utility\DeductionDailyReport;

use Model\{DeductionMemberDaily, Fmc, DeductionFmc};

use Web\Utility\{DateHelper, CsvWriter};
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;


class DeductionDailyReportController extends BackendController
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
        $form = new DeductionDailyForm();
        if ($form->isPost()) {
            $form->setModel($this->_model);
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new DeductionDailyReport();
                $generator->setDate(DateHelper::fromDatepicker($values['deduction_date']))
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
            $populate = [
                'deduction_date' => $maxDate, 'fmc' => 0,
            ];
            
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
        $report = $generator->report();
        $filters = $report['filters'];
        $fmc = $report['filters']['fmc'];
        $members = $this->view->lookupStore('member_code');
        $list = $report['report'];
        $deductions = $report['deductions'];
        $deductionIds = array_keys($deductions);
        $summary = $report['summary'];
        
        $title1 = 'FMC ' . $fmc . ' Sanghom Statement '
                . $this->view->formatDateTime('d-m-Y', $filters['date']) ;
        $title2 = 'FMC ' . $fmc . ' - Sanghom Statement for '
                . $filters['datepicker'];
        
        $export = new CsvWriter($title1);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title2]);
        
        $heading = ['Sl', 'Code', 'Name', 'Catching'];
        foreach ($deductions as $type) {
            $heading[] = $type;
        }
        $heading[] = 'Total deduction';
        $export->row($heading);
        
        foreach ($members as $memberId => $code) {
            if (isset($list[$memberId])) {
                $row = [
                    $this->view->counter(),
                    $code,
                    $this->view->lookup('member_name', $memberId),
                    $report['catch'][$memberId],
                ];
                
                foreach ($deductionIds as $deductionId) {
                    if (isset($list[$memberId][$deductionId])) {
                        $row[] = $list[$memberId][$deductionId];
                    } else {
                        $row[] = '';
                    }
                }
                $row[] = $summary['members'][$memberId];
                $export->row($row);
            }
        }
        
        $footer = [ 'Total', '', '', array_sum($report['catch']) ];
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
        $fields = ['fmc', 'date'];
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
        
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $values['fmc']);
        if (false === ($date = DateHelper::fromFormat($values['date'], 'Ymd'))) {
            die('Invalid request');
        }
        
        $generator = new DeductionDailyReport();
        $generator->setDate($date->format(DateHelper::MYSQL_DATE))
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
        $this->setPageHeading('Daily Deduction  Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add('Daily Deduction Report');
    }
    
}
