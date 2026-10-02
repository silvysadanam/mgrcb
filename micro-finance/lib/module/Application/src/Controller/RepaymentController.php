<?php
/**
 * Application Repayment Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;

use Model\{Loan, Member, Fmc, Schedule};

use Shared\Option\{NumberRange, Month};
use Shared\Form\FilterForm;

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class RepaymentController extends ApplicationController
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
        $this->_loadYears();
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                    . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                    . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $query = 'SELECT `s`.`loan_id`, `s`.`instalment_date`, `s`.`instalment`, '
                   . '`s`.`emi`, `s`.`total_demand`, `s`.`collection_repaid`, '
                   . '`s`.`balance_actual_principal_interest_outstanding`,'
                   . '`l`.`member_id`, `l`.`loan_date`, `l`.`loan_amount`,  '
                   . '`l`.`account_number`,  '
                   . '`m`.`fmc_id`, `m`.`name`, `m`.`code` '
                   . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
                   . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
                   . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
            if (! empty($where)) {
                $query .= ' WHERE ' . $where;
            }
            $query .= ' ORDER BY `s`.`instalment_date` DESC, `m`.`name`';
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        $this->view->setVariable('total', $total);
        $this->view->setVariable('isManager', $this->isManager());
        
        $this->registerPairs(['fmc']);
        LookupStore::register('year', NumberRange::get($this->_years[1], $this->_years[0], false));
        LookupStore::register('month', Month::get());
        LookupStore::register('payment', [1 => 'Has Payment amount', 'No Payment amount']);
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        if ($this->isManager()) {
            $this->addModuleScript('repayment-filter-manager');
        } else {
            $this->addModuleScript('repayment-filter');
        }
        
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
        $this->_sql->whereOne('s.has_dcb_entry');
        
        if ($this->isManager()) {
            $this->_managerFilter();
        } else {
            $this->_adminFilter();
        }
        
        $this->_dateFilter();
        $this->_paymentFilter();
        $this->_setMemberFilter();
  
        $this->view->setVariable('filters', $this->_filters);
        
        
        return $this->_sql->getWhere();
    }
    
    private function _loadFilterForm()
    {
        $this->_filters = new FilterForm();
        $fields = [
            'fmc', 'code', 'member', 'member_id', 'year', 'month', 'payment',
        ];
        $this->_filters->addFields($fields);
        $this->_filters->numeric(['fmc', 'year', 'month', 'member_id', 'payment']);
        if ($this->isManager()) {
            $this->_filters->addFilter('fmc', $this->_filters->getCollectionFilter([$this->_branch->id()]));
        }
        
        $this->_filters->addFilter('year', $this->_filters->getCollectionFilter($this->_years[4]));
        $this->_filters->addFilter('month', $this->_filters->getMinMaxFilter(12));
        $this->_filters->addFilter('payment', $this->_filters->getMaxTwoFilter());
        $this->_filters->populate(['year' => $this->_years[1], 'month' => $this->_years[2]]);
        $this->_filterValues = $this->_filters->getFilteredValues();
    }
    
    private function _managerFilter()
    {
        $memberIds = $this->_branch->memberIds();
        if (empty($memberIds)) {
            $this->_sql->whereZero('l.id');
            return;
        }
        
        if ($this->_filterValues['member_id'] > 0) {
            if (! in_array($this->_filterValues['member_id'], $memberIds)) {
                $this->_sql->whereIn('l.member_id', $memberIds);
                return;
            }
            
            $this->_sql->whereId('l.member_id', $this->_filterValues['member_id']);
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
                
                if ($this->_filterValues['member_id'] > 0) {
                    if (! in_array($this->_filterValues['member_id'], $memberIds)) {
                        $this->_sql->whereIn('l.member_id', $memberIds);
                        return;
                    }
                }
            
                $this->_sql->whereIn('l.member_id', $memberIds);
                return;
            }
        }
        
        if ('' == $this->_filterValues['code']) {
            return;
        }
        
        $sql->whereEqual('code', $this->_filterValues['code']);
        $where = $sql->getWhere();
        if ($this->_memberModel->selectTotal($where) > 0) {
            $this->_sql->whereId('l.member_id', $this->_memberModel->selectId($where));
        } else {
            $this->_sql->whereZero('l.id');
        }
    }
    
    private function _dateFilter()
    {
        if (0 == $this->_filterValues['year']) {
            return;
        }
        
        if ($this->_filterValues['month'] > 0) {
            $dateTime1 = DateHelper::getDateTime();
            $dateTime1->setDate($this->_filterValues['year'], $this->_filterValues['month'], 1);
            $start = $dateTime1->format(DateHelper::MYSQL_DATE);
            $days = $dateTime1->format('t');
            $dateTime1->setDate($this->_filterValues['year'], $this->_filterValues['month'], $days);
            $end = $dateTime1->format(DateHelper::MYSQL_DATE);
            $this->_sql->whereBetween('s.instalment_date', $start, $end);
            
            return;
        }
        
        $dateTime2 = DateHelper::getDateTime();
        $dateTime2->setDate($this->_filterValues['year'], 1, 1);
        $start = $dateTime2->format(DateHelper::MYSQL_DATE);
        
        $month = 12;
        $day = 31;
        if (DateHelper::year() == $this->_filterValues['year']) {
            $month = DateHelper::month();
            $day = DateHelper::day();
        }
        
        $dateTime2->setDate($this->_filterValues['year'], $month, $day);
        $end = $dateTime2->format(DateHelper::MYSQL_DATE);
        $this->_sql->whereBetween('s.instalment_date', $start, $end);
    }
    
    private function _paymentFilter()
    {
        if (0 == $this->_filterValues['payment']) {
            return;
        }
        
        if (1 == $this->_filterValues['payment']) {
            $this->_sql->whereGreater('s.collection_repaid', 0);
            return;
        }
        
        $this->_sql->whereZero('s.collection_repaid');
    }
    
    private function _setMemberFilter()
    {
        $fmcId = 0;
        if ($this->isManager()) {
            $fmcId = $this->_branch->id();
        } elseif ($this->_filterValues['fmc'] > 0) {
            if ($this->_fmcModel->hasId($this->_filterValues['fmc'])) {
                $fmcId = $this->_filterValues['fmc'];
            }
        }
        
        $options = [];
        if ($fmcId > 0) {
            $sql = $this->_model->getSql();
            $sql->whereId('fmc_id', $fmcId);
            $list = $this->_memberModel->selectFields(['id', 'name', 'code'], $sql->getWhere(), 'code');
            foreach ($list as $item) {
                $options[$item->id] = $item->code . ' - ' . $item->name;
            }
        }
        
        $this->view->setVariable('memberOptions', $options);
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('DCB Reports - Repayment');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Repayment');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Repayment');
    }
    
    private function _loadYears()
    {
        $current = DateHelper::year();
        $this->_years = [$current, $current, DateHelper::month(), DateHelper::today(), [0, $current]];
        $this->_sql->whereOne('has_dcb_entry');
        $where = $this->_sql->getWhere();
        $this->_sql->clear();
        if (0 == $this->_scheduleModel->selectTotal($where)) {
            return;
        }
        
        $query1 = 'SELECT MIN(YEAR(`instalment_date`)) FROM ' 
                . $this->_scheduleModel->getTableQuoted() . ' WHERE ' . $where;
        $this->_years[0] = $this->_scheduleModel->queryColumn($query1);
        
        $query2 = 'SELECT MAX(YEAR(`instalment_date`)) FROM ' 
                . $this->_scheduleModel->getTableQuoted() . ' WHERE ' . $where;
        $this->_years[1] = $this->_scheduleModel->queryColumn($query2);
        
        $query3 = 'SELECT MAX(`instalment_date`) FROM ' 
                . $this->_scheduleModel->getTableQuoted() . ' WHERE ' . $where;
        $date = $this->_scheduleModel->queryColumn($query3);        
        $this->_years[2] = DateHelper::month($date);
        $this->_years[3] = $date;
        $this->_years[4] = [0];
        for ($year = $this->_years[0]; $year <= $this->_years[1]; $year++) {
            $this->_years[4][] = $year;
        }
    }
    
}
