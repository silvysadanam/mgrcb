<?php
/**
 * Application ViewDcb Controller
 * 
 * @category Application
 * @package  Application\Controller
 */
namespace Backend\Controller;
use Backend\BackendController;
use Application\Constant\Tab;
use Application\Form\{FilterForm, AjaxForm};
use Application\Utility\{LoanStore, DcbViewer, DcbExport, LoanHelper};

use Model\{Loan, Member, Fmc, LoanType, LoanSource, LoanSummary};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class ViewDcbController extends BackendController
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
     * @var \Model\LoanType 
     */
    private $_loanTypeModel;
    
    /**
     *
     * @var \Model\LoanSource 
     */
    private $_loanSourceModel;
    
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
    private $_activeOnly = false;
    
    /**
     * 
     * @var boolean
     */
    private $_defaultActive = false;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_loanTypeModel = new LoanType();
        $this->_loanSourceModel = new LoanSource();
       // $this->currentTab(Tab::INTERFACE_DCB);
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
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where. " and approval_status ='Pending'";
        } else {
            $queryTotal .= " WHERE approval_status ='Pending'";
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`loan_type_id`, `l`.`member_id`, '
                   . '`l`.`loan_date`, `l`.`loan_amount`, `l`.`loan_source_id`, '
                   . '`l`.`account_number`, `l`.`closed`, `l`.`loan_status_id`, '
                   . '`m`.`fmc_id`, `m`.`name`, `m`.`code` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` '
                   . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
                   . 'INNER JOIN  `' . $this->_fmcModel->getTable() . '` `f` ON `f`.`id` = `m`.`fmc_id`';
            if (! empty($where)) {
                $query .= ' WHERE ' . $where;
            }
            $query .= " and approval_status ='Pending' ";
            $query .= ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        $this->view->setVariable('total', $total);
        $this->view->setVariable('isManager', $this->isManager());
        
        $this->registerPairs();
        LookupStore::register('status', LoanHelper::statusFilterOptions());
        LookupStore::register('amount', LoanHelper::loanAmountOptions());
        LookupStore::register('ageing', LoanHelper::ageingList());
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        if ($this->isManager()) {
            $this->addModuleScript('dcb-filter-manager');
        } else {
            $this->addModuleScript('dcb-filter');
        }
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function activeAction()
    {
        $this->_activeOnly = true;
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`loan_type_id`, `l`.`member_id`, '
                   . '`l`.`loan_date`, `l`.`loan_amount`, `l`.`loan_source_id`, '
                   . '`l`.`account_number`, `l`.`closed`, `l`.`loan_status_id`, '
                   . '`m`.`fmc_id`, `m`.`name`, `m`.`code` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` '
                   . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
                   . 'INNER JOIN  `' . $this->_fmcModel->getTable() . '` `f` ON `f`.`id` = `m`.`fmc_id`';
            if (! empty($where)) {
                $query .= ' WHERE ' . $where;
            }
            $query .= ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        $this->view->setVariable('total', $total);
        $this->view->setVariable('isManager', $this->isManager());
        
        $this->registerPairs();
        //LookupStore::register('status', LoanHelper::statusFilterOptions());
        LookupStore::register('amount', LoanHelper::loanAmountOptions());
        LookupStore::register('ageing', LoanHelper::ageingList());
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        if ($this->isManager()) {
            $this->addModuleScript('dcb-filter-manager');
        } else {
            $this->addModuleScript('dcb-filter');
        }
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $store = new LoanStore($this->_getId());
        $this->view->setVariable('store', $store);
        $this->view->setVariable('viewer', new DcbViewer($this->view));
        $this->layout->setLayout('');
        if ($store->defaulter()) {
            $this->layout->setScript('view-dcb/defaulter');
        }
        
        return $this->getViewModel();
    }
    
    /**
     * download action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadAction()
    {
        $store = new LoanStore($this->_getId());
        $download = new DcbExport($this->view);
        $download->store($store);
        $download->run();
        
        return $this->getViewModel();
    }
    
    /**
     * quick action
     * 
     * @return \Web\View\ViewModel
     */
    public function quickAction()
    {
        $this->addModuleScript('view-dcb-quick');
        $this->setHeadTitle('VIEW DCB');
        $this->setPageHeading('DCB');
        $this->setPageSubheading('Quick Find DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Quick Find');
        
        return $this->getViewModel();
    }
    
    
    /**
     * search action
     * 
     * @return \Web\View\ViewModel
     */
    public function searchAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['search']);
        if ($form->isPost()) {
            $values = $form->run();
            switch ($values['id']) {
                case 1:
                    $this->_sql->whereEqual('account_number', $values['search']);
                    break;
                
                case 2:
                    $this->_sql->whereId('id', $values['search']);
                    break;
                
                default:
                    if ($this->_memberModel->checkByColumn('code', $values['search'])) {
                        $memberId = $this->_memberModel->selectIdByColumn('code', $values['search']);
                        $this->_sql->whereId('member_id', $memberId);
                    } else {
                        $this->_sql->whereZero('id');
                    }
                    break;
            }
            
            $where = $this->_sql->getWhere();
            if ($this->_model->selectTotal($where) > 0) {
                $pairModel = new PairModel;
                $source = $pairModel->loanSource();
                $type = $pairModel->loanType();
                $fmc = $pairModel->fmc();
        
                $response->valid();
                $list = $this->_model->select($where, 'id DESC');
                $first = current($list);
                $response->addData($first->id, 'dcb_id');
                $html = '';
                $row = '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
                     . '<td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
                     . '<td><a href="' . $this->view->actionUrl('view-dcb/view/id') . '/%d" target="_blank">View</a></td></tr>';
                foreach ($list as $item) {
                    $member = $this->_memberModel->find($item->member_id);
                    $html .= sprintf($row, $item->id, $fmc[$member->fmc_id], $member->name, $member->code, 
                            $type[$item->loan_type_id], $source[$item->loan_source_id], $item->loan_amount,
                            $this->view->datepicker($item->loan_date), $item->account_number, $item->id);
                }
                $response->addData($html, 'html');
                $total = count($list);
                $message = $total . ' record';
                if ($total > 1) {
                    $message .='s';
                }
                $message .=' found';
            } else {
                $message = 'No records found';
            }
        }

        $response->message($message);
        
        
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
        if ($this->isManager()) {
            $this->_managerFilter();
        } else {
            $this->_adminFilter();
        }
        
        $this->_typeFilter();
        $this->_sourceFilter();
        $this->_amountFilter();
        $this->_statusFilter();
        $this->_ageingFilter();
        $this->_dateFilter();
        
        $this->_setMemberFilter();
  
        $this->view->setVariable('filters', $this->_filters);
        
        
        return $this->_sql->getWhere();
    }
    
    private function _loadFilterForm()
    {
        $this->_filters = new FilterForm();
        $fields = [
            'fmc', 'code', 'type', 'source', 'member', 'amount_from',
            'amount_to', 'ageing', 'date_from', 'date_to', 'member_id',
        ];
        
        if (! $this->_activeOnly) {
            $fields[] = 'status';
        }
        
        $this->_filters->addFields($fields);
        $this->_filters->numeric(['fmc', 'type', 'source', 'ageing', 'member_id']);
        
        if (! $this->_activeOnly) {
            $this->_filters->numeric(['status']);
            $this->_filters->addFilter('status', $this->_filters->getMinMaxFilter(8));
        }
        
        if ($this->isManager()) {
            $this->_filters->addFilter('fmc', $this->_filters->getCollectionFilter([$this->_branch->id()]));
        }
        
        $this->_filters->addFilter('ageing', $this->_filters->getMinMaxFilter(9));
        $this->_filters->addFilter('date_from', $this->_filters->getDateFilter());
        $this->_filters->addFilter('date_to', $this->_filters->getDateFilter());
        
        if (! $this->_activeOnly) {
            if ($this->_defaultActive) {
                $this->_filters->populate(['status' => LoanHelper::FILTER_STATUS_ACTIVE]);
            }
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
                    if (in_array($this->_filterValues['member_id'], $memberIds)) {
                        $this->_sql->whereId('l.member_id', $this->_filterValues['member_id']);
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
    
    private function _typeFilter()
    {
        if ($this->_filterValues['type'] > 0) {
            if ($this->_loanTypeModel->hasId($this->_filterValues['type'])) {
                $this->_sql->whereId('l.loan_type_id', $this->_filterValues['type']);
            }
        }
    }
    
    private function _sourceFilter()
    {
        if ($this->_filterValues['source'] > 0) {
            if ($this->_loanSourceModel->hasId($this->_filterValues['source'])) {
                $this->_sql->whereId('l.loan_source_id', $this->_filterValues['source']);
            }
        }
    }
    
    private function _amountFilter()
    {
        $from = 0;
        $to = 0;
        if ('' != $this->_filterValues['amount_from']) {
            if ('0' != $this->_filterValues['amount_from']) {
                if (1 == preg_match('/^[1-9]\d*$/', $this->_filterValues['amount_from'])) {
                    $from = (int) $this->_filterValues['amount_from'];
                }
            }
        }
        
        if ('' != $this->_filterValues['amount_to']) {
            if ('0' != $this->_filterValues['amount_to']) {
                if (1 == preg_match('/^[1-9]\d*$/', $this->_filterValues['amount_to'])) {
                    $to = (int) $this->_filterValues['amount_to'];
                }
            }
        }
        
        if ($to < $from) {
            $to = 0;
        }
        
        if (0 == $from && 0 == $to) {
            return;
        }
        
        $fromAmount = sprintf('%0.2f', $from);
        $toAmount = sprintf('%0.2f', $to);
        
        if ($from == $to) {
            $this->_sql->whereEqual('l.loan_amount', $fromAmount);
            return;
        }
        
        if ($from > 0 && $to > 0) {
           $this->_sql->whereGreater('l.loan_amount', $fromAmount, true)
                      ->whereLess('l.loan_amount', $toAmount, true);
           return;
        }
        
        if ($from > 0) {
            $this->_sql->whereGreater('l.loan_amount', $fromAmount, true);
            return;
        }
        
        if ($to > 0) {
            $this->_sql->whereLess('l.loan_amount', $toAmount, true);
        }
    }
    
    private function _statusFilter()
    {
        if ($this->_activeOnly) {
            $this->_sql->whereZero('l.closed');
            
            return;
        }
        
        if (! filter_has_var(INPUT_GET, 'status')) {
            if ($this->_defaultActive) {
                $this->_sql->whereZero('l.closed');
                return;
            }
        }
        
        if (0 == $this->_filterValues['status']) {
            return;
        }
        
        switch ($this->_filterValues['status']) {
            case LoanHelper::FILTER_STATUS_ACTIVE:
                $this->_sql->whereZero('l.closed');
                break;

            case LoanHelper::FILTER_STATUS_PRE_CLOSED:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_PRECLOSED);
                break;
            
            case LoanHelper::FILTER_STATUS_CLOSED:
                $this->_sql->whereOne('l.closed')
                           ->whereZero('l.loan_status_id');
                break;

            case LoanHelper::FILTER_STATUS_DELINQUENT:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_DELINQUENT);
                break;

            case LoanHelper::FILTER_STATUS_WRITTEN_OFF:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_WRITTEN_OFF);
                break;

            case LoanHelper::FILTER_STATUS_REFINANCED:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_REFINANCED);
                break;

            case LoanHelper::FILTER_STATUS_RESCHEDULED:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_RESCHEDULED);
                break;
            
            case LoanHelper::FILTER_STATUS_INACTIVE:
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', LoanHelper::STATUS_INACTIVE);
                break;

            default:
                $this->_sql->whereOne('l.closed');
                break;
        }
    }
    
    private function _ageingFilter()
    {
        if (0 == $this->_filterValues['ageing']) {
            return;
        }
        
        if ($this->_filterValues['ageing'] > 9) {
            return;
        }
        
        $loanSummary = new LoanSummary();
        $query = '(SELECT `s`.`ageing` FROM ' . $loanSummary->getTableQuoted()
               . ' `s` WHERE `s`.`loan_id` = `l`.`id` LIMIT 1) ';
        $limits = LoanHelper::getAgeing($this->_filterValues['ageing']);
        if (is_array($limits)) {
            $from = $limits[0] + 1;
            $to = $limits[1] - 1;
            $query .= 'BETWEEN ' . $from . ' AND ' . $to;
        } elseif (0 == $limits) {
            $query .= '=0';
        } else {
            $query .= ' > ' . $limits;
        }
        $this->_sql->whereGroup($query);
    }
    
    private function _dateFilter()
    {
        if ('' == $this->_filterValues['date_from'] && '' == $this->_filterValues['date_to']) {
            return;
        }
        
        if ($this->_filterValues['date_from'] == $this->_filterValues['date_to']) {
            $this->_sql->whereEqual('l.loan_date', DateHelper::fromDatepicker($this->_filterValues['date_from']));
            return;
        }
        
        $from = '';
        if ('' != $this->_filterValues['date_from']) {
            $from =  DateHelper::fromDatepicker($this->_filterValues['date_from']);
        }
        
        $to = '';
        if ('' != $this->_filterValues['date_to']) {
            $to =  DateHelper::fromDatepicker($this->_filterValues['date_to']);
        }
        
        if ('' != $from && '' != $to) {
            if (DateHelper::getDateTime($to) > DateHelper::getDateTime($from)) {
                $this->_sql->whereBetween('l.loan_date', $from, $to);
                return;
            }
            
            $this->_sql->whereGreater('l.loan_date', $from, true);
            return;
        }
        
        if ('' != $from) {
            $this->_sql->whereGreater('l.loan_date', $from, true);
            return;
        }
        
        if ('' != $to) {
            $this->_sql->whereLess('l.loan_date', $to, true);
        }
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
     * get loan id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_model->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'view-dcb');
        }
        
        if ($this->isManager()) {
            $memberId = $this->_model->selectColumnById('member_id', $id);
            if ($this->_branch->id() != $this->_memberModel->selectColumnById('fmc_id', $memberId)) {
                $this->setErrorFlashMessage('Invalid request', 'view-dcb');
            }
        }
        
        return $id;
    }
    
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('VIEW DCB');
        $this->setPageHeading('DCB');
        $this->setPageSubheading('Select DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('DCB', $this->view->actionUrl('view-dcb'));
        $breadrumb->add('Select');
    }
    
    
}
