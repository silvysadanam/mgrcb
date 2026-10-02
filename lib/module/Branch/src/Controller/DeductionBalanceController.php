<?php
/**
 * Branch Deduction Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;

use Model\{DeductionMemberDaily, DeductionMemberMonthly};

use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;


class DeductionBalanceController extends BranchController
{  
    /**
     *
     * @var \Model\DeductionMemberDaily
     */
    private $_model;
    
    /**
     *
     * @var \Model\DeductionMemberMonthly
     */
    private $_deductionMemberMonthlyModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new DeductionMemberDaily();
        $this->_deductionMemberMonthlyModel = new DeductionMemberMonthly();
        $this->currentTab(Tab::DEDUCTION_BILL_NEW);
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $members = $this->_branch->memberLookup();
        $memberIds = array_keys($members);
        $list = [];
        foreach ($memberIds as $id) {
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $id);
            $where = $this->_sql->getWhere();
            if ($this->_model->selectTotal($where) > 0) {
                $list[$id] = $this->_model->selectMax('deduction_date', $where);
            }
        }
        
        $this->view->setVariable('total', count($list));
        $this->view->setVariable('list', $list);
        LookupStore::register('member', $members);
        $this->_breadcrumb('Current Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_branch->isMember($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-balance');
        }
        
        $list = [];
        $deductions = $this->_branch->deductionTypes();
        $deductionTypeIds = array_keys($deductions);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $id);
        if ($this->_model->selectTotal($this->_sql->getWhere()) > 0) {
            foreach ($deductionTypeIds as $typeId) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $id)
                           ->whereId('deduction_type_id', $typeId);
                $where = $this->_sql->getWhere();
                if ($this->_model->selectTotal($where) > 0) {
                    $list[$typeId] = $this->_model->selectRow($where, 'deduction_date DESC');
                }
            }
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($id));
        $this->view->setVariable('list', $list);
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($deductions)));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        $this->_breadcrumb('Current Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * daily action
     * 
     * @return \Web\View\ViewModel
     */
    public function dailyAction()
    {
        $filters = new FilterForm();
        $fields = ['member', 'deduction'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'deduction']);
        
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $deductions = $this->_branch->deductionTypes();
        
        $valid = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                if ($values['deduction'] > 0) {
                    $valid = isset($deductions[$values['deduction']]);
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-balance');
        }
        
        $brachDeductionIds = array_keys($deductions);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member'])
                   ->whereId('deduction_type_id', $values['deduction']);
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'deduction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($values['member']));
        $this->view->setVariable('total', $total);
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        $this->view->setVariable('groupedSelector', DeductionHelper::groupedDeductionSelector($brachDeductionIds));
        $this->registerPairs(['member']);
        $this->_breadcrumb('Daily Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * monthly action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyAction()
    {
        $filters = new FilterForm();
        $fields = ['member', 'deduction'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'deduction']);
        
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $deductions = $this->_branch->deductionTypes();
        
        $valid = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                if ($values['deduction'] > 0) {
                    $valid = isset($deductions[$values['deduction']]);
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-balance');
        }
        
        $brachDeductionIds = array_keys($deductions);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member'])
                   ->whereId('deduction_type_id', $values['deduction']);
        $where = $this->_sql->getWhere();
        $total = $this->_deductionMemberMonthlyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_deductionMemberMonthlyModel->select($where, 'deduction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($values['member']));
        $this->view->setVariable('total', $total);
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        $this->view->setVariable('groupedSelector', DeductionHelper::groupedDeductionSelector($brachDeductionIds));
        $this->registerPairs(['member']);
        $this->_breadcrumb('Monthly Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * group daily action
     * 
     * @return \Web\View\ViewModel
     */
    public function groupDailyAction()
    {
        $filters = new FilterForm();
        $fields = ['member', 'group'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'group']);
        
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $deductions = $this->_branch->deductionTypes();
        $brachDeductionIds = array_keys($deductions);
        $groupedDeductions = DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
        
        $valid = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                if ($values['group'] > 0) {
                    $valid = isset($groupedDeductions[$values['group']]);
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-balance');
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member'])
                   ->whereIn('deduction_type_id', $groupedDeductions[$values['group']]);
        $where = $this->_sql->getWhere();
        $query1 = 'SELECT COUNT(DISTINCT `deduction_date`) FROM '
                . $this->_model->getTableQuoted() . ' WHERE ' . $where
                . ' GROUP BY `deduction_date`';
        
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT DISTINCT `deduction_date`, '
                    . 'SUM(`deduction_amount`) AS `deduction_amount`, '
                    . 'SUM(`opening_balance`) AS `opening_balance`, '
                    . 'SUM(`advance`) AS `advance`, '
                    . 'SUM(`withdrawal`) AS `withdrawal`, '
                    . 'SUM(`repayment`) AS `repayment`, '
                    . 'SUM(`closing_balance`) AS `closing_balance` '
                    . 'FROM ' . $this->_model->getTableQuoted()
                    . ' WHERE ' . $where . ' GROUP BY `deduction_date` '
                    . 'ORDER BY `deduction_date` DESC LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($values['member']));
        $this->view->setVariable('total', $total);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', $groupedDeductions);
        $this->registerPairs(['member']);
        $this->_breadcrumb('Grouped Daily Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * group monthly action
     * 
     * @return \Web\View\ViewModel
     */
    public function groupMonthlyAction()
    {
        $filters = new FilterForm();
        $fields = ['member', 'group'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'group']);
        
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $deductions = $this->_branch->deductionTypes();
        $brachDeductionIds = array_keys($deductions);
        $groupedDeductions = DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
        
        $valid = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                if ($values['group'] > 0) {
                    $valid = isset($groupedDeductions[$values['group']]);
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-balance');
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member'])
                   ->whereIn('deduction_type_id', $groupedDeductions[$values['group']]);
        $where = $this->_sql->getWhere();
        $query1 = 'SELECT COUNT(DISTINCT `deduction_date`) FROM '
                . $this->_deductionMemberMonthlyModel->getTableQuoted() . ' WHERE ' . $where
                . ' GROUP BY `deduction_date`';
        
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT DISTINCT `deduction_date`, '
                    . 'SUM(`deduction_amount`) AS `deduction_amount`, '
                    . 'SUM(`opening_balance`) AS `opening_balance`, '
                    . 'SUM(`advance`) AS `advance`, '
                    . 'SUM(`withdrawal`) AS `withdrawal`, '
                    . 'SUM(`repayment`) AS `repayment`, '
                    . 'SUM(`closing_balance`) AS `closing_balance` '
                    . 'FROM ' . $this->_deductionMemberMonthlyModel->getTableQuoted()
                    . ' WHERE ' . $where . ' GROUP BY `deduction_date` '
                    . 'ORDER BY `deduction_date` DESC LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($values['member']));
        $this->view->setVariable('total', $total);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', $groupedDeductions);
        $this->registerPairs(['member']);
        $this->_breadcrumb('Grouped Monthly Closing Balance');
        
        return $this->getViewModel();
    }
    
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Deduction Bills - ' . $page);
        $this->setPageHeading('Deduction Bills');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction'));
        $breadrumb->add('Closing Balance', $this->view->actionUrl('deduction-balance'));
        $breadrumb->add($page);
    }
    
}
