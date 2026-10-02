<?php
/**
 * Application AnnualRepayment Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\Repayment\AnnualForm;

use Application\Utility\LoanHelper;

use Model\{Loan, Member, Fmc, Schedule, LoanSource};

use Web\Utility\DateHelper;
use Web\Utility\CsvWriter;


class AnnualRepaymentController extends ApplicationController
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
     * @var \Model\Schedule 
     */
    private $_scheduleModel;
    
    /**
     * 
     * @var array
     */
    private $_filterValues;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var boolean
     */
    private $_paymentOnly = true;
    
    /**
     * 
     * @var array
     */
    private $_months = [];
    
    /**
     * 
     * @var array
     */
    private $_period = [];
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_monthOnly = [];
    
    /**
     * 
     * @var string
     */
    private $_where = null;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_scheduleModel = new Schedule();
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
        $endDate = LoanHelper::reportingDate();
        $time = DateHelper::getDateTime($endDate);
        $year = $time->format('Y');
        if ($time->format('n') < 4) {
            $year -= 1;
        }
        
        $time->setDate($year, 4, 1);
        $startDate = $time->format(DateHelper::MYSQL_DATE);
        
        $this->_sql->whereOne('has_dcb_entry');
        $firstDate = $this->_scheduleModel->selectMin('instalment_date', $this->_sql->getWhere());
        $minTime = DateHelper::getDateTime($firstDate);
        $minTime->setDate($minTime->format('Y'), $minTime->format('n'), 1);
        $minDate = $minTime->format(DateHelper::MYSQL_DATE);
        
        $settings = [
            'start_date' => DateHelper::datepicker($startDate),
            'end_date' => DateHelper::datepicker($endDate),
            'min_date' => DateHelper::datepicker($minDate),
        ];
        
        $this->registerPairs(['fmc', 'source']);
        
        $form = new AnnualForm();
        if ($form->isPost()) {
            $limits = [
                'max_date' => $endDate,
                'min_date' => $minDate,
            ];
            if ($form->isValid($limits)) {
                $this->_filterValues = $form->getValues();
                $this->_loadDates();
                if (! $this->_checkTotal()) {
                    $form->addError('No repayment entries found');
                } else {
                    $this->_generate();
                    if ($this->_filterValues['report_option'] > 0) {
                        $this->_download();
                    }

                    $this->_print();
                }
            }
        } else {
            $populate = [
                'fmc' => 0,
                'loan_source_id' => 0,
                'start_date' => $settings['start_date'],
                'end_date' => $settings['end_date'],
                'report_option' => 0,
            ];
            if ($this->isManager()) {
                $populate['fmc'] = $this->_branch->id();
            }
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('isManager', $this->isManager());
        $this->view->setVariable('settings', $settings);
        $this->addModuleScript('annual-repayment');
        
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    private function _checkTotal()
    {
        $this->_getFilter();
        $query = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'WHERE ' . $this->_where;
        return $this->_model->queryTotal($query) > 0;
    }
    
    private function _generate()
    {
        $query = 'SELECT `s`.`loan_id`, `s`.`collection_repaid`, `s`.`instalment_date`, '
               . ' `l`.`member_id` '
               . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'WHERE ' . $this->_where . ' ORDER BY `s`.`instalment_date`';
        $list = $this->_model->queryRows($query);
        
        $this->_report = [
            'loans' => [],
            'loan_total' => [],
            'month_total' => [],
            'member_loans' => [],
        ];
        
        $months = count($this->_period);
        $monthlyTotal = array_fill(0, $months, 0);
        $default = array_fill(0, $months, '');
        foreach ($list as $row) {
            if (! isset($this->_report['loans'][$row->loan_id])) {
                $this->_report['loans'][$row->loan_id] = $default;
                $this->_report['loan_members'][$row->loan_id] = $row->member_id;
                $this->_report['loan_total'][$row->loan_id] = 0;
                
                if (! isset($this->_report['member_loans'][$row->member_id])) {
                    $this->_report['member_loans'][$row->member_id] = [];
                }
                $this->_report['member_loans'][$row->member_id][] = $row->loan_id;
            }
            
            $index = array_search($row->instalment_date, $this->_period);
            $this->_report['loans'][$row->loan_id][$index] = $row->collection_repaid;
            $this->_report['loan_total'][$row->loan_id] += $row->collection_repaid;
            $monthlyTotal[$index] += $row->collection_repaid;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', array_keys($this->_report['loans']));
        $this->_report['loan_lookup'] = $this->_model->selectIndexed($this->_sql->getWhere(), 'id');
        
        $this->_sql->clear();
        $this->_sql->whereIn('m.id', array_keys($this->_report['member_loans']));
        
        $query = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`, `m`.`fmc_id`'
               . 'FROM ' . $this->_memberModel->getTableQuoted() . ' `m` '
               . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() . ' `f` ON `f`.`id` = `m`.`fmc_id` '
               . 'WHERE ' . $this->_sql->getWhere() . ' ORDER BY `f`.`name`, `m`.`name`';
        $members = $this->_model->queryRows($query);
        $this->_report['member_lookup'] = $this->_model->getIndexedList($members);
        //$this->_report['member_lookup'] = $this->_memberModel->selectIndexed($this->_sql->getWhere(), 'fmc_id, name');
        $this->_report['monthly_total'] = $monthlyTotal;
    }
    
    private function _print()
    {
        $this->view->setVariable('report', $this->_report);
        $this->view->setVariable('months', $this->_months);
        $this->view->setVariable('period', $this->_period);
        $this->view->setVariable('monthOnly', $this->_monthOnly);
        
        $this->layout->setLayout('');
        $this->layout->setScript('annual-repayment/print');
    }
    
    private function _download()
    {
        $title = 'Loan Repayment for the period from ' . current($this->_months) . ' to ' . end($this->_months);
        $fmcList = $this->view->lookupStore('fmc');
        $defaultHeading = [
            'Sl No.', 'Name', 'Code Number', 'GRADE', 'FMC', 'LOAN DATE', 'Loan No.',
        ];
        $heading = array_merge($defaultHeading, $this->_monthOnly);
        $heading[] = 'Loan Total';
        
        $export = new CsvWriter($title);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title]);
        $export->row($heading);

        foreach ($this->_report['member_lookup'] as $memberId => $member) {
            foreach ($this->_report['member_loans'][$memberId] as $loanId) {
                $loan = $this->_report['loan_lookup'][$loanId];
                $row = [
                    $this->view->counter(),
                    $member->name,
                    $member->code,
                    '',
                    $fmcList[$member->fmc_id],
                ];
                if ($loan->closed > 0) {
                    $row[] = 'Closed';
                } else {
                    $row[] = $this->view->datepicker($loan->loan_date);
                }
                
                $row[] = $loan->account_number;
                foreach ($this->_report['loans'][$loanId] as $repayment) {
                    if ('' != $repayment) {
                        $row[] = $this->view->integer($repayment);
                    } else {
                        $row[] = '';
                    }
                }
                $row[] = $this->view->integer($this->_report['loan_total'][$loanId]);
                $export->row($row);
            }
        }
        
        $summary = array_fill(0, 6, '');
        $summary[] = 'Grand Total';
        foreach ($this->_report['monthly_total'] as $total) {
            $summary[] = $this->view->integer($total);
        }
        $summary[] = $this->view->integer(array_sum($this->_report['monthly_total']));
        $export->row($summary);
        
        $export->finish();
    }
    
    private function _loadDates()
    {
        $endDate = DateHelper::fromDatepicker($this->_filterValues['end_date']);
        $time = DateHelper::getDateTime($endDate);
        $time->setDate($time->format('Y'), $time->format('n'), $time->format('t'));
        $start = DateHelper::fromDatepicker($this->_filterValues['start_date']);
        $startTime = DateHelper::getDateTime($start);
        $startTime->setDate($startTime->format('Y'), $startTime->format('n'), 1);
        $period = new \DatePeriod($startTime, new \DateInterval('P1M'), $time);
        foreach ($period as $month) {
            $this->_months[] = $month->format('F Y');
            $this->_monthOnly[] = $month->format('F');
            $dateTime = clone $month;
            $dateTime->setDate($month->format('Y'), $month->format('n'), $month->format('t'));
            $this->_period[] = $dateTime->format(DateHelper::MYSQL_DATE);
        }
    }
    
    private function _getFilter()
    {
        $this->_sql->clear();
        $this->_sql->whereOne('s.has_dcb_entry');
        if ($this->_paymentOnly) {
            $this->_sql->whereGreater('s.collection_repaid', 0);
        }
        
        $sql = $this->_model->getSql();
        
        if ($this->isManager()) {
            $memberIds = $this->_branch->memberIds();
            if (empty($memberIds)) {
                $this->_sql->whereZero('l.id');
            } else {
                $this->_sql->whereIn('l.member_id', $memberIds);
            }
        } else {
            if ($this->_filterValues['fmc'] > 0) {
                if ($this->_fmcModel->hasId($this->_filterValues['fmc'])) {
                    $sql->whereId('fmc_id', $this->_filterValues['fmc']);
                    $memberIds = $this->_memberModel->selectIds($sql->getWhere());
                    if (empty($memberIds)) {
                        $this->_sql->whereZero('l.id');
                    } else {
                        $this->_sql->whereIn('l.member_id', $memberIds);
                    }
                }
            }
        }
        
        if ($this->_filterValues['loan_source_id'] > 0) {
            $sourceModel = new LoanSource();
            if ($sourceModel->hasId($this->_filterValues['loan_source_id'])) {
                $this->_sql->whereId('l.loan_source_id', $this->_filterValues['loan_source_id']);
            }
        }
        $this->_sql->whereBetween('s.instalment_date', DateHelper::fromDatepicker($this->_filterValues['start_date']), end($this->_period));
        $this->_where = $this->_sql->getWhere();
    }
   
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Annual Repayment');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Annual Repayment');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Annual Repayment');
    }
    
}
