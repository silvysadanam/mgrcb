<?php
/**
 * Application LoanSummary Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\LoanSummaryForm;
use Application\Utility\LoanSummaryReport;

use Model\{Loan, Member, Fmc, LoanSummary};

use Web\Utility\DateHelper;


class LoanSummaryController extends ApplicationController
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
     * @var \Model\LoanSummary 
     */
    private $_loanSummaryModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_years;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_loanSummaryModel = new LoanSummary();
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
        
        $form = new LoanSummaryForm();
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
            }
        } else {
            $populate = [
                'fmc_id' => $defaultFmc,
                'start_date' => $minDatepicker,
                'end_date' => DateHelper::datepicker(),
            ];
            
            $form->populate($populate);
        }
        
        $generator = new LoanSummaryReport();
        $generator->setFmcId($fmcId);
        
        if ('' != $startDate) {
            $generator->setStartDate($startDate);
        }
        if ('' != $endDate) {
            if ('' != $startDate) {
                if (DateHelper::timestamp($endDate) >= DateHelper::timestamp($startDate)) {
                    $generator->setEndDate($endDate);
                }
            } else {
                $generator->setEndDate($endDate);
            }
        }
        
        $valid = $generator->run();
        if (! $valid) {
            $form->addError('No matching loans found');
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('generator', $generator);
        $this->view->setVariable('min_date', $minDatepicker);
        $this->addModuleScript('loan-summary-report');
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Loan Summary');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Loan Summary');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Loan Summary');
    }
    
}
