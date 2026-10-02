<?php
/**
 * Application MonthlyConfirmation Controller
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
use Web\Utility\{CsvWriter, DateHelper};


class MonthlyConfirmationController extends ApplicationController
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
    private $_paymentOnly = false;
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    /**
     * 
     * @var array
     */
    private $_list = [];
    
    /**
     * 
     * @var array
     */
    private $_fmcTotal = [];
    
    /**
     * 
     * @var array
     */
    private $_grandTotal = [];
        
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
        $this->_getFilter();
        $total = $this->_getTotal();
        if ($total > 0) {
            $this->_getList();
            
            $this->view->setVariable('list', $this->_list);
            $this->view->setVariable('fmcTotal', $this->_fmcTotal);
            $this->view->setVariable('grandTotal', $this->_grandTotal);
            $this->view->setVariable('queryParams', http_build_query($this->_filterValues));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('isManager', $this->isManager());
        $this->registerPairs(['fmc', 'date', 'source', 'type']);
        
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
        $this->_getFilter();
        $total = $this->_getTotal();
        if (0 == $total) {
            die('No records found');
        }
        
        $this->_getList();
        
        $this->view->setVariable('list', $this->_list);
        $this->view->setVariable('fmcTotal', $this->_fmcTotal);
        $this->view->setVariable('grandTotal', $this->_grandTotal);
        $this->registerPairs(['fmc', 'source', 'type']);
        if ($this->_filterValues['source'] > 0) {
            $this->view->setVariable('source', $this->view->lookup('source', $this->_filterValues['source']));
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
        $this->_getFilter();
        $total = $this->_getTotal();
        if (0 == $total) {
            die('No records found');
        }
        
        $this->_getList();
        $this->registerPairs(['fmc', 'source', 'type']);
        $blank = array_fill(0, 9, '');
        $defaultTotal = $blank;
        $defaultTotal[0] = 'Total';
        $fmcList = $this->view->lookupStore('fmc');
        $heading1 = $blank;
        $heading1[5] = 'Collection';
        $heading2 = [
            'Sl No', 'Name', 'Loan No', 'Type', 'Opening', 'Principal', 
            'Interest', 'Principal + Interest', 'Current Principal Balance',
            'Current Principal + Interest Balance',
        ];
        $source = '';
        if ($this->_filterValues['source'] > 0) {
            $source = $this->view->lookup('source', $this->_filterValues['source']) . ' ';
        }
        $title = $source . 'Loan Collection and Confirmation details for the month of ' . $this->view->getVariable('month');
        
        $export = new CsvWriter($title);
        $export->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $export->row([$title]);
        foreach ($fmcList as $fmcId => $fmcName) {
            if (isset($this->_list[$fmcId])) {
                $this->view->counter(0);
                $export->row(['FMC ' . $fmcName]);
                $export->row($heading1);
                $export->row($heading2);
                foreach ($this->_list[$fmcId] as $row) {
                    $record = [
                        $this->view->counter(),
                        $row['name'],
                        $row['account_number'],
                        $this->view->lookup('type', $row['loan_type_id']),
                        $row['opening'],
                        $row['principal'],
                        $row['interest'],
                        $row['total'],
                        $row['balance'],
                        $row['actual'],
                    ];
                    $export->row($record);
                }
                
                $fmcSummary = $defaultTotal;
                $fmcSummary[4] = $this->_fmcTotal[$fmcId]['opening'];
                $fmcSummary[5] = $this->_fmcTotal[$fmcId]['principal'];
                $fmcSummary[6] = $this->_fmcTotal[$fmcId]['interest'];
                $fmcSummary[7] = $this->_fmcTotal[$fmcId]['total'];
                $fmcSummary[8] = $this->_fmcTotal[$fmcId]['balance'];
                $fmcSummary[9] = $this->_fmcTotal[$fmcId]['actual'];
                $export->row($fmcSummary);
            }
        }
        
        $export->row($blank);
        $blank[0] = 'Grand Total';
        $blank[4] = $this->_grandTotal['opening'];
        $blank[5] = $this->_grandTotal['principal'];
        $blank[6] = $this->_grandTotal['interest'];
        $blank[7] = $this->_grandTotal['total'];
        $blank[8] = $this->_grandTotal['balance'];
        $blank[9] = $this->_grandTotal['actual'];
        $export->row($blank);
        $export->finish();
        
        return $this->getViewModel();
    }
    
    
    /**
     * get total
     * 
     * @return int
     */
    private function _getTotal()
    {
        $query = 'SELECT COUNT(`s`.`id`) FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
               . 'WHERE ' . $this->_where;
        return $this->_model->queryTotal($query);
    }
    
    /**
     * get list
     */
    private function _getList()
    {
        $query = 'SELECT `s`.`instalment`, `s`.`loan_id`, `s`.`collection_repaid`, '
               . '`s`.`scheduled_demand_principal_outstanding`, `s`.`collection_principal_arrears`, '
               . '`s`.`collection_current_principal`, `s`.`collection_penal_interest`, '
               . '`s`.`collection_interest_arrears`, `s`.`collection_current_interest`, '
               . '`s`.`collection_prepayment`, `s`.`balance_actual_principal_outstanding`, '
               . '`s`.`actual_opening_principal_outstanding`, `s`.`balance_actual_principal_outstanding`, '
               . '`s`.`balance_actual_principal_interest_outstanding`, '
               . '`l`.`member_id`, `l`.`account_number`, `m`.`fmc_id`, `m`.`name`, '
                . '`l`.`loan_source_id`, `l`.`loan_type_id` '
               . 'FROM  `' . $this->_scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN  `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
               . 'WHERE ' . $this->_where . ' ORDER BY `m`.`name`';
        $all = $this->_model->queryRows($query);
        
        $default = [
            'opening' => 0,
            'principal' => 0,
            'interest' => 0,
            'total' => 0,
            'balance' => 0,
            'actual' => 0,
        ];

        $totalFields = array_keys($default);
        $direct = [
            'loan_id', 'account_number', 'loan_type_id', 'member_id', 
            'name', 'fmc_id',
        ];
        
        $this->_grandTotal = $default;
        foreach ($all as $item) {
            if (! isset($this->_list[$item->fmc_id])) {
                $this->_list[$item->fmc_id] = [];
                $this->_fmcTotal[$item->fmc_id] = $default;
            }
            $entry = [];
            foreach ($direct as $field) {
                $entry[$field] = $item->{$field};
            }

            $entry['opening'] = $item->actual_opening_principal_outstanding;
            $entry['principal'] = $item->collection_principal_arrears
                                + $item->collection_current_principal
                                + $item->collection_prepayment;
            $entry['interest'] = $item->collection_penal_interest
                                + $item->collection_interest_arrears
                                + $item->collection_current_interest;
            $entry['total'] = $entry['principal'] + $entry['interest'];
            $entry['balance'] = $item->balance_actual_principal_outstanding;
            $entry['actual'] = $item->balance_actual_principal_interest_outstanding;

            $this->_list[$item->fmc_id][] = $entry;
            foreach ($totalFields as $totalKey) {
                $this->_fmcTotal[$item->fmc_id][$totalKey] += $entry[$totalKey];
                $this->_grandTotal[$totalKey] += $entry[$totalKey];
            }
        }
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
        $this->_where = $this->_sql->getWhere();
    }
    
    /**
     * load filter form
     */
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
    
    /**
     * manager filter
     * 
     * @return null
     */
    private function _managerFilter()
    {
        $memberIds = $this->_branch->memberIds();
        if (empty($memberIds)) {
            $this->_sql->whereZero('l.id');
            return;
        }
        
        $this->_sql->whereIn('l.member_id', $memberIds);
    }
    
    /**
     * admin filter
     * 
     * @return null
     */
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
    
    /**
     * date filter
     */
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
    
    /**
     * source filter
     */
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
        $this->setHeadTitle('DCB Reports -Monthly Confirmation');
        $this->setPageHeading('DCB Reports');
        $this->setPageSubheading('Monthly Confirmation');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB Reports', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Monthly Confirmation');
    }
    
}
