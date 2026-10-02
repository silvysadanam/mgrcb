<?php
/**
 * Application NewLoan Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\NewLoanForm;
use Application\Form\FilterForm;
use Application\Form\AjaxForm;
use Application\Utility\NewLoanReport;

use Model\{Loan, Member, Fmc};

use Web\Utility\{DateHelper, CsvWriter};


class NewLoanController extends ApplicationController
{
    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_memberModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->currentTab(Tab::INTERFACE_DCB);
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->registerPairs(['fmc', 'source', 'type']);
        $minDate = $this->_model->selectMin('loan_date');
        $minDatepicker = DateHelper::datepicker($minDate);
        $maxDate = DateHelper::datepicker();
        $fmcId = 0;
        $startDate = '';
        $endDate = '';
        $defaultFmc = 0;
        if ($this->isManager()) {
            $defaultFmc = $this->_branch->id();
            $fmcId = $defaultFmc;
        }
        
        $form = new NewLoanForm();
        if ($form->isPost()) {
            if ($form->isValid()) {
                $values = $form->getValues();
                if (0 == $defaultFmc) {
                    if ($values['fmc_id'] > 0) {
                        if ($this->_fmcModel->hasId($values['fmc_id'])) {
                            $fmcId = $values['fmc_id'];
                        }
                    }
                }
                
                if ($minDate != $values['start_date']) {
                    $startDate = DateHelper::fromDatepicker($values['start_date']);
                }
                
                if ($maxDate != $values['end_date']) {
                    $endDate = DateHelper::fromDatepicker($values['end_date']);
                }
                
                if ($endDate != '' && $startDate != '') {
                    if (DateHelper::timestamp($startDate) > DateHelper::timestamp($endDate)) {
                        $form->addError('End date should be after start date');
                    }
                }
                
                if ($form->noError()) {
                    $generator = new NewLoanReport();
                    $generator->setFmcId($fmcId)
                              ->setSourceId($values['source'])
                              ->setTypeId($values['type'])
                              ->setWise($values['wise']);

                    if ('' != $startDate) {
                        $generator->setStartDate($startDate);
                    } else {
                        $generator->setStartDate($minDate);
                    }
                    
                    if ('' != $endDate) {
                        if ('' != $startDate) {
                            if (DateHelper::timestamp($endDate) >= DateHelper::timestamp($startDate)) {
                                $generator->setEndDate($endDate);
                            }
                        } else {
                            $generator->setEndDate($endDate);
                        }
                    } else {
                        $generator->setEndDate(DateHelper::today());
                    }

                    $valid = $generator->run();
                    if (! $valid) {
                        $form->addError('No matching loans found');
                    } else {
                        $this->view->setVariable('generator', $generator);
                        $this->layout->addModal('new-loan/loan');
                        if ($generator->fmcWise()) {
                            if ($generator->fmcSelected()) {
                                $this->layout->setScript('new-loan/index-fmc-single');
                            } else {
                                $this->layout->setScript('new-loan/index-fmc');
                            }
                        } else {
                            if ($generator->fmcSelected()) {
                                $this->layout->setScript('new-loan/index-month-single');
                            } else {
                                $this->layout->setScript('new-loan/index-month');
                            }
                        }
                    }
                }
            }
        } else {
            $dates = $this->_getDefaultPeriod();
            $populate = [
                'fmc_id' => $defaultFmc,
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'source' => 0,
                'type' => 0,
                'wise' => 0,
            ];
            
            $form->populate($populate);
        }

        $this->registerForm($form);
        
        $this->view->setVariable('min_date', $minDatepicker);
        $this->addModuleScript('new-loan-report');
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
        $generator = $this->_getFilters();
        $this->registerPairs(['fmc', 'source', 'type']);
        $this->view->setVariable('generator', $generator);
        $report = $generator->report();
        if ($report['filters']['wise'] > 0) {
            $this->layout->setScript('new-loan/print-month');
        }    
        
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
        $generator = $this->_getFilters();
        
        $this->registerPairs(['fmc', 'source', 'type']);
        $generator->export();
        
        return $this->getViewModel();
    }
    
    public function summaryAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        if ($form->isPost()) {
            $values = $form->run();
            $generator = new NewLoanReport();
            $result = $generator->loanList($values['id'], $this->view->actionUrl('modify-dcb/view/id'));
            $response->addData($result['summary'], 'summary')
                     ->addData($result['html'], 'html')
                     ->valid();
            $message = 'success';
        }

        $response->message($message);
        return $this->getViewModel();
    }
    
    private function _getFilters()
    {
        $filters = new FilterForm();
        $filters->addFields(['fmc', 'type', 'source', 'start', 'end', 'wise']);
        $filters->numeric(['fmc', 'type', 'source', 'start', 'end', 'wise']);
        $filters->addFilter('wise', $filters->getYesOrNoFilter());
        $values = $filters->getFilteredValues();
        
        $start = '';
        $end = '';
        
        if ('' != $values['start']) {
            if (false !== ($startTime = DateHelper::fromFormat($values['start'], 'Ymd'))) {
                $start = $startTime->format(DateHelper::MYSQL_DATE);
            }
        }
        
        if ('' != $values['end']) {
            if (false !== ($endTime = DateHelper::fromFormat($values['end'], 'Ymd'))) {
                $end = $endTime->format(DateHelper::MYSQL_DATE);
            }
        }
        
        if ('' == $start || '' == $end) {
            die('Invalid request');
        }
        
        $fmcId = 0;
        if ($this->isManager()) {
            $fmcId = $this->_branch->id();
        } else {
            $fmcId = $values['fmc'];
        }
        
        $generator = new NewLoanReport();
        $generator->setFmcId($fmcId)
                  ->setSourceId($values['source'])
                  ->setTypeId($values['type'])
                  ->setWise($values['wise'])
                  ->setStartDate($start)
                  ->setEndDate($end);

        $valid = $generator->run();
        
        if (! $valid) {
            die('No matching loans found');
        }
        
        return $generator;
    }
    
    private function _getDefaultPeriod()
    {
        $dates = [
            'start_date' => '',
            'end_date' => DateHelper::datepicker(),
        ];
        $date = DateHelper::getDateTime();
        $month = $date->format('n');
        $year = $date->format('Y');
        $endMonth = $month;
        $startPreviousYear = true;
        if ($month < 4) {
            $endMonth = $month;
        } elseif ($month < 7) {
            $endMonth = 3;
        } else {
            $startPreviousYear = false;
        }

        if ($endMonth != $month) {
            $date->setDate($year, $endMonth, 1);
            $days = $date->format('t');
            $date->setDate($year, $endMonth, $days);
            $dates['end_date'] = $date->format(DateHelper::DATEPICKER);
        }

        if ($startPreviousYear) {
            $year -= 1;
        }

        $date->setDate($year, 4, 1);
        $dates['start_date'] = $date->format(DateHelper::DATEPICKER);
        
        return $dates;
    }
    
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('New Loans Report');
        $this->setPageHeading('New Loans');
        $this->setPageSubheading('Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('New Loans', $this->view->actionUrl('new-loan'));
        $breadrumb->add('Report');
    }
    
}
