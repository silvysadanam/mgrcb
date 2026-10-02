<?php
/**
 * Application LoanTotal Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\LoanTotalForm;
use Application\Form\FilterForm;
use Application\Utility\LoanTotalReport;

use Model\{Loan, Member, Fmc, LoanSummary, LoanStatus};

use Web\Utility\DateHelper;


class LoanTotalController extends ApplicationController
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
    
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_loanSummaryModel = new LoanSummary();
        $this->currentTab(Tab::INTERFACE_STATUS_REPORT);
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
        $sourceId = 0;
        $typeId = 0;
        if ($this->isManager()) {
            $defaultFmc = $this->_branch->id();
            $fmcId = $defaultFmc;
        }
        
        $form = new LoanTotalForm();
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
                        $startDate = $endDate;
                    }
                }
                
                if ($values['source'] > 0) {
                    $sourceId = $values['source'];
                }
                
                if ($values['type'] > 0) {
                    $typeId = $values['type'];
                }
            }
        } else {
            $populate = [
                'fmc_id' => $defaultFmc,
                'start_date' => $minDatepicker,
                'end_date' => DateHelper::datepicker(),
                'source' => 0,
                'type' => 0,
            ];
            
            $form->populate($populate);
        }
        
        $generator = new LoanTotalReport();
        $generator->setFmcId($fmcId)
                  ->setSourceId($sourceId)
                  ->setTypeId($typeId);
        
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
        $this->addModuleScript('loan-total-report');
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * loan action
     * 
     * @return \Web\View\ViewModel
     */
    public function loanAction()
    {
        $where = $this->_getFilters();
        $this->registerPairs(['fmc', 'source', 'type']);
        $joins = 'INNER JOIN ' . $this->_loanSummaryModel->getTableQuoted() . ' `s` ON `s`.`loan_id` = `l`.`id` '
               . 'INNER JOIN ' . $this->_memberModel->getTableQuoted() . ' `m` ON `m`.`id` = `l`.`member_id` ';
        $queryTotal = 'SELECT COUNT(`l`.`id`) '
                    . 'FROM ' . $this->_model->getTableQuoted() . ' `l` '
                    . $joins . ' WHERE ' . $where;
        $total = $this->_model->queryTotal($queryTotal);
        $paging = $this->getPaging($total);
        $query = 'SELECT `l`.`id`, `l`.`loan_amount`, `l`.`member_id`, '
               . '`l`.`loan_type_id`, `l`.`loan_source_id`, `l`.`loan_date`, '
               . '`s`.`collection_penal_interest` AS `penal_interest`, '
               . '`s`.`collection_interest_arrears` AS `interest_arrears`, '
               . '`s`.`collection_current_interest` AS `current_interest`, '
               . '`s`.`collection_principal_arrears` AS `principal_arrears`, '
               . '`s`.`collection_current_principal` AS `current_principal`, '
               . '`s`.`collection_prepayment` AS `prepayment`, '
               . '`s`.`balance_total_overdue` AS `overdue`, '
               . '`s`.`balance_scheduled_principal_outstanding` AS `scheduled_principal_outstanding`, '
               . '`s`.`balance_actual_principal_outstanding` AS `actual_principal_outstanding`, '
               . '`s`.`balance_actual_principal_interest_outstanding` AS `actual_principal_interest_outstanding`, '
               . '`s`.`par`,'
               . '`m`.`name`, `m`.`code`, `m`.`fmc_id` '
               . 'FROM ' . $this->_model->getTableQuoted() . ' `l` ' . $joins
               . ' WHERE ' . $where . ' ORDER BY `l`.`loan_date` DESC';
        if ('' != $paging->sqlLimit) {
            $query .= ' LIMIT ' . $paging->sqlLimit;
        }
        $this->view->setVariable('list', $this->_model->queryRows($query));
        $this->view->setVariable('total', $total);
        
        $this->setHeadTitle('Loan Status wise - Loans Report');
        $this->setPageHeading('Loan Status wise');
        $this->setPageSubheading('Loans Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Loan Status wise', $this->view->actionUrl('loan-total'));
        $breadrumb->add('Loans Report');
        
        return $this->getViewModel();
    }
    
    /**
     * member action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberAction()
    {
        $where = $this->_getFilters();
        $this->registerPairs(['fmc', 'source', 'type']);
        $joins = 'INNER JOIN ' . $this->_loanSummaryModel->getTableQuoted() . ' `s` ON `s`.`loan_id` = `l`.`id` '
               . 'INNER JOIN ' . $this->_memberModel->getTableQuoted() . ' `m` ON `m`.`id` = `l`.`member_id` ';
        $queryTotal = 'SELECT COUNT(DISTINCT `l`.`member_id`) '
                    . 'FROM ' . $this->_model->getTableQuoted() . ' `l` '
                    . $joins . ' WHERE ' . $where;
        $total = $this->_model->queryTotal($queryTotal);
        $paging = $this->getPaging($total);
        $query = 'SELECT DISTINCT `l`.`member_id`, COUNT(`l`.`id`) AS `loans`, '
               . 'SUM(`l`.`loan_amount`) AS `amount`, `l`.`member_id`, '
               . 'SUM(`s`.`collection_penal_interest`) AS `penal_interest`, '
               . 'SUM(`s`.`collection_interest_arrears`) AS `interest_arrears`, '
               . 'SUM(`s`.`collection_current_interest`) AS `current_interest`, '
               . 'SUM(`s`.`collection_principal_arrears`) AS `principal_arrears`, '
               . 'SUM(`s`.`collection_current_principal`) AS `current_principal`, '
               . 'SUM(`s`.`collection_prepayment`) AS `prepayment`, '
               . 'SUM(`s`.`balance_total_overdue`) AS `overdue`, '
               . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled_principal_outstanding`, '
               . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `actual_principal_outstanding`, '
               . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `actual_principal_interest_outstanding`, '
               . 'SUM(`s`.`par`) AS `par`, '
               . '`m`.`name`, `m`.`code`, `m`.`fmc_id` '
               . 'FROM ' . $this->_model->getTableQuoted() . ' `l` ' . $joins
               . ' WHERE ' . $where . ' GROUP BY `l`.`member_id` ORDER BY `m`.`name`';
        if ('' != $paging->sqlLimit) {
            $query .= ' LIMIT ' . $paging->sqlLimit;
        }
        $this->view->setVariable('list', $this->_model->queryRows($query));
        $this->view->setVariable('total', $total);
        
        $this->setHeadTitle('Loan Status wise - Members Report');
        $this->setPageHeading('Loan Status wise');
        $this->setPageSubheading('Members Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Loan Status wise', $this->view->actionUrl('loan-total'));
        $breadrumb->add('Members Report');
        
        return $this->getViewModel();
    }
    
    
    private function _getFilters()
    {
        $summary = [
            'fmc' => 0,
            'source' => 0,
            'type' => 0,
            'start' => '',
            'end' => DateHelper::datepicker(),
            'status' => 'Active',
        ];
        $filters = new FilterForm();
        $filters->addFields(['fmc', 'type', 'source', 'start', 'end', 'status']);
        $filters->numeric(['fmc', 'type', 'source', 'status']);
        $filters->addFilter('status', $filters->getMinMaxFilter(6));
        $values = $filters->getFilteredValues();
        
        $start = '';
        $end = '';
        $minDate = DateHelper::getDateTime($this->_model->selectMin('loan_date'));
        $summary['start'] = $minDate->format(DateHelper::DATEPICKER);
        $maxDate = DateHelper::getDateTime();
        
        if ('' != $values['start']) {
            if (false !== ($startTime = DateHelper::fromFormat($values['start'], 'Ymd'))) {
                if ($startTime >= $minDate && $startTime <= $maxDate) {
                    $start = $startTime->format(DateHelper::MYSQL_DATE);
                    $summary['start'] = $startTime->format(DateHelper::DATEPICKER);
                }
            }
        }
        
        if ('' != $values['end']) {
            if (false !== ($endTime = DateHelper::fromFormat($values['end'], 'Ymd'))) {
                if ($endTime >= $minDate && $endTime <= $maxDate) {
                    if ('' != $start) {
                        if ($endTime >= $startTime) {
                            $end = $endTime->format(DateHelper::MYSQL_DATE);
                            $summary['end'] = $endTime->format(DateHelper::DATEPICKER);
                        }
                    }
                }
            }
        }
        
        $sql = $this->_model->getSql();
        
        if ($values['status'] > 0) {
            $sql->whereOne('l.closed')
                ->whereId('l.loan_status_id', $values['status']);
            $loanStatusModel = new LoanStatus();
            $summary['status'] = $loanStatusModel->selectColumnById('loan_status', $values['status']);
        } else {
            $sql->whereZero('l.closed');
            $summary['status'] = 'Active';
        }
        
        if ($values['fmc'] > 0) {
            $sql->whereId('m.fmc_id', $values['fmc']);
            $summary['fmc'] = $values['fmc'];
        }
        
        if ($values['source'] > 0) {
            $sql->whereId('l.loan_source_id', $values['source']);
            $summary['source'] = $values['source'];
        }
        
        if ($values['type'] > 0) {
            $sql->whereId('l.loan_type_id', $values['type']);
            $summary['type'] = $values['type'];
        }
        
        if ('' != $start || '' != $end) {
            if ('' != $start && '' != $end) {
                if ($start == $end) {
                    $sql->whereEqual('l.loan_date', $start);
                } else {
                    $sql->whereBetween('l.loan_date', $start, $end);
                }
            } elseif ('' != $start) {
                $sql->whereGreater('l.loan_date', $start, true);
            } else {
                $sql->whereLess('l.loan_date', $end, true);
            }
        }
        
        $this->view->setVariable('filters', $filters);
        $this->view->setVariable('summary', $summary);
       
        return $sql->getWhere();
    }
    
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('Loan Status wise - Report');
        $this->setPageHeading('Loan Status wise');
        $this->setPageSubheading('Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Loan Status wise', $this->view->actionUrl('loan-total'));
        $breadrumb->add('Report');
    }
    
}
