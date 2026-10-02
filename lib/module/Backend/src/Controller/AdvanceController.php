<?php
/**
 * Backend Deduction Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\Tab;
use Backend\Utility\AdvanceStore;

use Model\{AdvanceWithdrawal, Fmc, Member};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\{AjaxForm, FilterForm};
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;
use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;


class AdvanceController extends BackendController
{
    /**
     *
     * @var \Model\AdvanceWithdrawal 
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
        $this->_model = new AdvanceWithdrawal();
        $this->currentTab(Tab::ADVANCE);
        $this->_sql = $this->_model->getSql();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $deductions = DeductionHelper::withdrawal();
        $filters = new FilterForm();
        $fields = ['date', 'fmc', 'member', 'status', 'type', 'deduction'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'status', 'type', 'deduction', 'fmc']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->addFilter('status', $filters->getMaxTwoFilter());
        $filters->addFilter('type', $filters->getMaxTwoFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);

        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('advance_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        $fmcSelectd = false;
        
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $fmcSelectd = true;
                if ($values['member'] > 0) {
                    if ($this->_memberModel->hasId($values['member'])) {
                        $member = $this->_memberModel->selectFieldsById(['member_type_id', 'fmc_id'], $values['member']);
                        if ($values['fmc'] == $member->fmc_id && MembershipType::LIFEMEMBER == $member->membership_type_id) {
                            $this->_sql->whereId('member_id', $values['member']);
                        }
                    }
                } else {
                    $sql = $this->_model->getSql();
                    $sql->whereId('fmc_id', $values['fmc'])
                        ->whereId('membership_type_id', MembershipType::LIFEMEMBER);
                    $this->_sql->whereIn('member_id', $this->_memberModel->selectIds($sql->getWhere()));
                }
            }
        } elseif ($values['member'] > 0) {
            if ($this->_memberModel->hasId($values['member'])) {
                if (MembershipType::LIFEMEMBER == $this->_memberModel->selectColumnById('membership_type_id', $values['member'])) {
                    $this->_sql->whereId('member_id', $values['member']);
                }
            }
        }
        
        if ($values['deduction'] > 0) {
            if (in_array($values['deduction'], $deductions)) {
                $this->_sql->whereId('deduction_type_id', $values['deduction']);
            }
        }
        
        if ($values['type'] > 0) {
            if (1 == $values['type']) {
                $this->_sql->whereZero('withdrawal');
                if ($values['status'] > 0) {
                    if (1 == $values['status']) {
                        $this->_sql->whereZero('closed');
                    } else {
                        $this->_sql->whereOne('closed');
                    }
                }
            } else {
                $this->_sql->whereOne('withdrawal');
            }
        }
        
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $list = $this->_model->select($where, 'advance_date DESC', $paging->sqlLimit);
            $this->view->setVariable('list', $list);
            //$memberIds = $this->_model->selectColumns('member_id', $where, 'advance_date DESC', $paging->sqlLimit);
            $memberIds = [];
            foreach ($list as $item) {
                if (!in_array($item->member_id, $memberIds)) {
                    $memberIds[] = $item->member_id;
                }
            }
            
            $this->_sql->clear();
            $this->_sql->whereIn('id', $memberIds);
            $memberList = $this->_memberModel->select($this->_sql->getWhere(), 'id');
            $this->view->setVariable('members', $this->_model->getIndexedList($memberList, 'id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('statusOptions', [1 => 'Open', 'Closed']);
        $this->view->setVariable('maxDate', DateHelper::datepicker());
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmc());
        $this->view->setVariable('typeOptions', [1 => 'Advance', 'Withdrawal']);
        
        $memberOptions = [];
        if ($fmcSelectd) {
            $this->_sql->clear();
            $this->_sql->whereId('fmc_id', $values['fmc'])
                        ->whereId('member_type_id', MemberType::LIFEMEMBER);
            $fmcMmembers = $this->_memberModel->selectFields(['id', 'code', 'name'], $this->_sql->getWhere(), 'code');
            
            foreach ($fmcMmembers as $item) {
                $memberOptions[$item->id] = $item->code . ' ' . $item->name;
            }
        }
        $this->view->setVariable('memberOptions', $memberOptions);
        
        $groups = DeductionHelper::deductionGroups();
        $deductionTypes = DeductionHelper::deductionTypes();
        $grouped = DeductionHelper::groupedDeductionTypeIdsSelection($deductions);
        $list = [];
        foreach ($grouped as $groupId => $typeIds) {
            $types = [];
            foreach ($typeIds as $id) {
                $types[$id] = $deductionTypes[$id];
            }
            
            $list[$groups[$groupId]] = $types;
        }
        
        $this->view->setVariable('groupedDeductionTypes', $list);
        $this->addModuleScript('advance');
        $this->_breadcrumb('List');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $id = $this->_getId();
        $store = new AdvanceStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * get action
     * 
     * @return \Web\View\ViewModel
     */
    public function getMemberAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $form = new AjaxForm();
        $form->addFields(['fmc']);
        if ($form->isPost()) {
            $values = $form->run();
            $options = [0 => '--all--'];
            if ($values['fmc'] > 0) {
                $fmcModel = new Fmc();
                if ($fmcModel->hasId($values['fmc'])) {
                    $sql = $fmcModel->getSql();
                    $sql->whereId('fmc_id', $values['fmc'])
                        ->whereId('member_type_id', MemberType::LIFEMEMBER);
                    $where = $sql->getWhere();
                    $memberModel = new Member();
                    if ($memberModel->selectTotal($where) > 0) {
                        $list = $memberModel->selectFields(['id', 'code', 'name'], $where, 'code');
                        foreach ($list as $item) {
                            $options[$item->id] = $item->code . ' ' . $item->name;
                        }
                    }
                }
            }
            $response->addData($this->view->formHelper('select', $options), 'result')
                     ->valid();
        }
        
        return $this->getViewModel();
    }
    
    /**
     * get deduction bill id
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
            $this->setErrorFlashMessage('Invalid request', 'advance');
        }
        
        return $id;
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Advances and Withdrawals - ' . $page);
        $this->setPageHeading('Advances and Withdrawals');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Advances and Withdrawals', $this->view->actionUrl('advance'));
        $breadrumb->add($page);
    }
    
}
