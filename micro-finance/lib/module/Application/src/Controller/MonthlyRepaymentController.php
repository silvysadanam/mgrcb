<?php
/**
 * Application MonthlyRepayment Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;

use Application\Utility\LoanHelper;

use Model\{Loan, Member, Fmc, Schedule, LoanSource};

use Shared\Form\FilterForm;
use Web\Utility\DateHelper;
use Web\Utility\CsvWriter;


class MonthlyRepaymentController extends ApplicationController
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
     * @var \Application\Form\FilterForm
     */
    private $_filters;
    
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
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                    . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                    . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $query = 'SELECT `s`.`loan_id`, `s`.`collection_repaid`, `l`.`member_id`, '
                   . '`l`.`account_number`, `m`.`fmc_id`, `m`.`name`, `l`.`loan_source_id` '
                   . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                   . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                   . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
                   . 'INNER JOIN  `' . $this->_fmcModel->getTable() . '` `f` ON `f`.`id` = `m`.`fmc_id`';
            if (! empty($where)) {
                $query .= ' WHERE ' . $where;
            }
            $query .= ' ORDER BY `f`.`name`, `m`.`name`';
            $all = $this->_model->queryRows($query);
            $list = [];
            $repayment = [];
            foreach ($all as $item) {
                if (! isset($list[$item->fmc_id])) {
                    $list[$item->fmc_id] = [];
                    $repayment[$item->fmc_id] = 0;
                }
                $list[$item->fmc_id][] = $item;
                $repayment[$item->fmc_id] += $item->collection_repaid;
            }
            
            $this->view->setVariable('list', $list);
            $this->view->setVariable('repayment', $repayment);
            $this->view->setVariable('queryParams', http_build_query($this->_filterValues));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('isManager', $this->isManager());
        $this->registerPairs(['fmc', 'date', 'source']);
        
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
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                    . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                    . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if (0 == $total) {
            die('No records found');
        }
        
        $query = 'SELECT `s`.`loan_id`, `s`.`collection_repaid`, `l`.`member_id`, '
               . '`l`.`account_number`, `m`.`fmc_id`, `m`.`name`, `l`.`loan_source_id` '
               . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `m`.`fmc_id`, `m`.`name`';
        $all = $this->_model->queryRows($query);
        $list = [];
        $repayment = [];
        foreach ($all as $item) {
            if (! isset($list[$item->fmc_id])) {
                $list[$item->fmc_id] = [];
                $repayment[$item->fmc_id] = 0;
            }
            $list[$item->fmc_id][] = $item;
            $repayment[$item->fmc_id] += $item->collection_repaid;
        }

        $this->view->setVariable('list', $list);
        $this->view->setVariable('repayment', $repayment);
        
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['fmc', 'source']);
        
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
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                    . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                    . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if (0 == $total) {
            die('No records found');
        }
        
        $query = 'SELECT `s`.`loan_id`, `s`.`collection_repaid`, `l`.`member_id`, '
               . '`l`.`account_number`, `m`.`fmc_id`, `m`.`name`, `l`.`loan_source_id` '
               . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY `m`.`fmc_id`, `m`.`name`';
        $all = $this->_model->queryRows($query);
        $list = [];
        $repayment = [];
        foreach ($all as $item) {
            if (! isset($list[$item->fmc_id])) {
                $list[$item->fmc_id] = [];
                $repayment[$item->fmc_id] = 0;
            }
            $list[$item->fmc_id][] = $item;
            $repayment[$item->fmc_id] += $item->collection_repaid;
        }
        
        $this->registerPairs(['fmc']);
        $blank = array_fill(0, 4, '');
        $defaultTotal = $blank;
        $defaultTotal[1] = 'Total';
        $fmcList = $this->view->lookupStore('fmc');
        $heading = ['Sl No', 'Name', 'Loan No', 'Amount'];
        $title = 'Loan Repayment for the month of ' . $this->view->getVariable('month');
        
        $export = new CsvWriter($title);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title]);
        foreach ($fmcList as $fmcId => $fmcName) {
            if (isset($list[$fmcId])) {
                $this->view->counter(0);
                $export->row(['FMC ' . $fmcName]);
                $export->row($heading);
                foreach ($list[$fmcId] as $row) {
                    $record = [
                        $this->view->counter(),
                        $row->name,
                        'New',
                        $this->view->amount($row->collection_repaid),
                    ];
                    if ('' != $row->account_number) {
                        $record[2] = $row->account_number;
                    }
                    $export->row($record);
                }
                
                $fmcTotal = $defaultTotal;
                $fmcTotal[3] = $repayment[$fmcId];
                $export->row($fmcTotal);
            }
        }
        
        $export->row($blank);
        $blank[1] = 'Grand Total';
        $blank[3] = $this->view->amount(array_sum($repayment));
        $export->row($blank);
        $export->finish();
        
        return $this->getViewModel();
    }
   
    
    /**
     * get list filter
     * 
     * @return string
     */
    private function _getFilter()
    {
        $this->_loadFilterForm();
        $this->_sql->whereOne('s.has_dcb_entry');
        if ($this->_paymentOnly) {
            $this->_sql->whereGreater('s.collection_repaid', 0);
        }
        
        if ($this->isManager()) {
            $this->_managerFilter();
        } else {
            $this->_adminFilter();
        }
        
        $this->_dateFilter();
        $this->_sourceFilter();
  
        $this->view->setVariable('filters', $this->_filters);
        
        
        return $this->_sql->getWhere();
    }
    
    private function _loadFilterForm()
    {
        $this->_filters = new FilterForm();
        $fields = [
            'fmc', 'source', 'date',
        ];
        $this->_filters->addFields($fields);
        $this->_filters->numeric(['fmc', 'source', 'date']);
        if ($this->isManager()) {
            $this->_filters->addFilter('fmc', $this->_filters->getCollectionFilter([$this->_branch->id()]));
        }      
        $options = LoanHelper::reportingDateOptions();
        $this->_filters->addFilter('date', $this->_filters->getCollectionFilter(array_keys($options)));
        
        $this->_filterValues = $this->_filters->getFilteredValues();
    }
    
    private function _managerFilter()
    {
        $memberIds = $this->_branch->memberIds();
        if (empty($memberIds)) {
            $this->_sql->whereZero('l.id');
            return;
        }
        
        $this->_sql->whereIn('l.member_id', $memberIds);
    }
    
    private function _adminFilter()
    {
        $sql = $this->_model->getSql();
        if ($this->_filterValues['fmc'] > 0) {
            if ($this->_fmcModel->hasId($this->_filterValues['fmc'])) {
                $sql->whereId('fmc_id', $this->_filterValues['fmc']);
                $memberIds = $this->_memberModel->selectIds($sql->getWhere());
                if (empty($memberIds)) {
                    $this->_sql->whereZero('l.id');
                    return;
                }
            
                $this->_sql->whereIn('l.member_id', $memberIds);
                return;
            }
        }
    }
    
    private function _dateFilter()
    {
        $options = LoanHelper::reportingDateOptions();
        $dates = array_keys($options);
        $date = $dates[0];
        if ($this->_filterValues['date'] > 0) {
            if (in_array($this->_filterValues['date'], $dates)) {
                $date = $this->_filterValues['date'];
            }
        }
        
        $dateTime = DateHelper::fromFormat($date, LoanHelper::REPORTING_DATE_ID);
        $this->_sql->whereEqual('s.instalment_date', $dateTime->format(DateHelper::MYSQL_DATE));
        $this->view->setVariable('month', $dateTime->format('F Y'));
    }
    
    private function _sourceFilter()
    {
        if ($this->_filterValues['source'] > 0) {
            $sourceModel = new LoanSource();
            if ($sourceModel->hasId($this->_filterValues['source'])) {
                $this->_sql->whereId('l.loan_source_id', $this->_filterValues['source']);
            }
        }
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Monthly Repayment');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Monthly Repayment');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Monthly Repayment');
    }
    
}
