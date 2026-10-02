<?php
/**
 * Application Outstanding Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;

use Model\{Loan, Member, Fmc, LoanSummary, LoanSource};

use Shared\Form\FilterForm;
use Web\Utility\DateHelper;
use Web\Utility\CsvWriter;


class OutstandingController extends ApplicationController
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
     * @var array
     */
    private $_report = [];
    
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
        $this->_loadReport();
        $this->view->setVariable('report', $this->_report);
        $this->view->setVariable('isManager', $this->isManager());
        $this->registerPairs(['fmc', 'source', 'type']);
        $this->addModuleScript('outstanding');
        
        $this->_breadcrumb();
        
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
        $this->_sql->whereZero('l.closed');
        
        if ($this->isManager()) {
            $this->_managerFilter();
        } else {
            $this->_adminFilter();
        }

        $this->_sourceFilter();
  
        $this->view->setVariable('filters', $this->_filters);
        
        
        return $this->_sql->getWhere();
    }
    
    private function _loadFilterForm()
    {
        $this->_filters = new FilterForm();
        $fields = [
            'fmc', 'source',
        ];
        $this->_filters->addFields($fields);
        $this->_filters->numeric(['fmc', 'source']);
        if ($this->isManager()) {
            $this->_filters->addFilter('fmc', $this->_filters->getCollectionFilter([$this->_branch->id()]));
        }
        
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
    
    private function _sourceFilter()
    {
        if ($this->_filterValues['source'] > 0) {
            $sourceModel = new LoanSource();
            if ($sourceModel->hasId($this->_filterValues['source'])) {
                $this->_sql->whereId('l.loan_source_id', $this->_filterValues['source']);
            }
        }
    }
    
    private function _loadReport()
    {        
        $where = $this->_getFilter();
        if (! empty($where)) {
            $queryWhere = ' WHERE ' . $where;
        }
        $from = 'FROM  `' . $this->_model->getTable() . '` `l` '
              . 'INNER JOIN  `' . $this->_loanSummaryModel->getTable() . '` `s` ON `s`.`loan_id` = `l`.`id` '
              . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        $queryTotal = 'SELECT COUNT(`l`.`id`) ' . $from . $queryWhere;
        
        $total = $this->_model->queryTotal($queryTotal);
        $this->view->setVariable('total', $total);
        if (0 == $total) {
            return false;
        }
        
        $default = [
            'loans' => 0, 'amount' => 0, 'scheduled' => 0,
            'principal' => 0, 'outstanding' => 0,
        ];
        $fields = array_keys($default);
        $this->_report = [
            'date' => [], 'fmc' => [], 'source' => [], 'list' => [], 
            'total' => [
                'grand' => $default, 'date' => [],
            ],
            
        ];

        $query1 = 'SELECT DISTINCT `s`.`as_on_date` '. $from . $queryWhere 
                . ' ORDER BY `s`.`as_on_date` DESC';
        $dates = $this->_model->queryColumns($query1);
        foreach ($dates as $date) {
            if ('' != $date) {
                $index = DateHelper::format('Ymd', $date);
                $this->_report['date'][$index] = $date;
                $this->_report['list'][$index] = [
                    'fmc' => [], 'source' => [],
                ];
                $this->_report['fmc'][$index] = [];
                $this->_report['source'][$index] = [];
                $this->_report['total']['date'][$index] = $default;
            }
        }

        $query2 = 'SELECT COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount`, '
                . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled`, '
                . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `principal`, '
                . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `outstanding` '
                . $from . $queryWhere;
        $result2 = $this->_model->queryRow($query2);
        foreach ($fields as $field) {
            $this->_report['total']['grand'][$field] = $result2->{$field};
        }

        
        foreach ($this->_report['date'] as $dateIndex => $asOnDate) {
            $this->_sql->clear();
            if (! empty($where)) {
                $this->_sql->where($where);
            }
            $this->_sql->whereEqual('s.as_on_date', $asOnDate);
            $whereDate = ' WHERE ' . $this->_sql->getWhere();
            
            $query6 = 'SELECT COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount`, '
                    . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled`, '
                    . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `principal`, '
                    . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `outstanding` '
                    . $from . $whereDate;
            
            $result6 = $this->_model->queryRows($query6);
            foreach ($result6 as $item6) {
                foreach ($fields as $field) {
                    $this->_report['total']['date'][$dateIndex][$field] = $item6->{$field};
                }
            }
            
            $query3 = 'SELECT `l`.`id`, `l`.`member_id`, `l`.`loan_amount`, `l`.`loan_date`, '
                    . '`l`.`account_number`, `l`.`loan_source_id`, `l`.`loan_type_id`, '
                    . '`m`.`fmc_id`, `m`.`name`, `m`.`code`, `s`.`balance_scheduled_principal_outstanding` AS `scheduled`,'
                    . '`s`.`balance_actual_principal_outstanding` AS `principal`, '
                    . '`s`.`balance_actual_principal_interest_outstanding` AS `outstanding` '
                    . $from . $whereDate . ' ORDER BY `m`.`name`';
            $result3 = $this->_model->queryRows($query3);
            foreach ($result3 as $item3) {
                if (! isset($this->_report['list'][$dateIndex]['fmc'][$item3->fmc_id])) {
                    $this->_report['list'][$dateIndex]['fmc'][$item3->fmc_id] = [];
                }
                $this->_report['list'][$dateIndex]['fmc'][$item3->fmc_id][] = $item3;
                if (! isset($this->_report['list'][$dateIndex]['source'][$item3->loan_source_id])) {
                    $this->_report['list'][$dateIndex]['source'][$item3->loan_source_id] = [];
                }
                $this->_report['list'][$dateIndex]['source'][$item3->loan_source_id][] = $item3;
            }
            
            $query4 = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount`, '
                    . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled`, '
                    . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `principal`, '
                    . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `outstanding` '
                    . $from . $whereDate . ' GROUP BY `m`.`fmc_id`';
            
            $result4 = $this->_model->queryRows($query4);
            foreach ($result4 as $item4) {
                $this->_report['fmc'][$dateIndex][$item4->fmc_id] = $default;
                foreach ($fields as $field) {
                    $this->_report['fmc'][$dateIndex][$item4->fmc_id][$field] = $item4->{$field};
                }
            }
            
            $query5 = 'SELECT `l`.`loan_source_id`, COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount`, '
                    . 'SUM(`s`.`balance_scheduled_principal_outstanding`) AS `scheduled`, '
                    . 'SUM(`s`.`balance_actual_principal_outstanding`) AS `principal`, '
                    . 'SUM(`s`.`balance_actual_principal_interest_outstanding`) AS `outstanding` '
                    . $from . $whereDate . ' GROUP BY `l`.`loan_source_id`';
            
            $result5 = $this->_model->queryRows($query5);
            foreach ($result5 as $item5) {
                $this->_report['source'][$dateIndex][$item5->loan_source_id] = $default;
                foreach ($fields as $field) {
                    $this->_report['source'][$dateIndex][$item5->loan_source_id][$field] = $item5->{$field};
                }
            }
        }
        //$this->view->setVariable('queryParams', http_build_query($this->_filterValues));
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Outstanding Report');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Outstanding Report');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Outstanding Report');
    }
    
}
