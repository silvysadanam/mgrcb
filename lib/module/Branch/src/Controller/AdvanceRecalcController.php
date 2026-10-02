<?php
/**
 * Branch AdvanceWithdrawal Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;
use Branch\Form\Advance\CreateRecalcForm;
use Branch\Utility\{AdvanceWithdrawalStore, ManageAdvanceRepayment};

use Model\{AdvanceWithdrawal, DeductionMemberDaily};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;
use Shared\Form\AjaxForm;


class AdvanceRecalcController extends BranchController
{  
    /**
     *
     * @var \Model\AdvanceWithdrawal 
     */
    private $_model;
    
    /**
     *
     * @var \Model\DeductionMemberDaily 
     */
    private $_deductionMemberDailyModel;
    private $_fmc_id;
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
        $this->currentTab(Tab::ADVANCE_NEW);
        $this->_sql = $this->_model->getSql();
        $this->_deductionMemberDailyModel = new DeductionMemberDaily();
        $this->_fmc_id = $this->_branch->id();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $deductions = $this->_branch->deductionTypes();
        $filters = new FilterForm();
        $fields = ['date', 'member', 'status', 'type', 'deduction'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'status', 'type', 'deduction']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->addFilter('status', $filters->getMaxTwoFilter());
        $filters->addFilter('type', $filters->getMaxTwoFilter());
        $keys =  array_keys($deductions);
        array_unshift($keys, 0);
        $filters->addFilter('deduction', $filters->getCollectionFilter($keys));
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $memberFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('advance_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $this->_sql->whereId('member_id', $values['member']);
                $memberFilter = false;
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
        
        if ($values['deduction'] > 0) {
            if (isset($deductions[$values['deduction']])) {
                $this->_sql->whereId('deduction_type_id', $values['deduction']);
            }
        }
        
        if ($memberFilter) {
            $this->_sql->whereIn('member_id', $this->_branch->auctionMemberIds());
        }
        
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'advance_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('statusOptions', [1 => 'Open', 'Closed']);
        $this->view->setVariable('maxDate', DateHelper::datepicker());
        $this->registerPairs(['member', 'member_name', 'member_code']);
        LookupStore::register('deductionType', $deductions);
        $this->view->setVariable('typeOptions', [1 => 'Advance', 'Withdrawal']);
        $this->view->setVariable('groupedDeductionTypes', $this->_branch->groupedWithdrawalDeductions());
        $this->addModuleScript('advance-withdrawal/list');
        $this->_breadcrumb('List');
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $form = new CreateRecalcForm($this->_fmc_id);
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid($this->_branch->advanceDeductions())) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->recalc($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Advance/Withdrawal Reset Done');
                    $this->redirect('advance-recalc/create');
                } catch (\PDOException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } catch (\Web\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } catch (\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } catch (\ErrorException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                }
            }
        } else {
            $populate = [
                'advance_date' => DateHelper::datepicker(),
                'deduction_type_id' => 0,
                'auto_collect_overdue' => 0,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('deductionSelector', $this->_branch->groupedWithdrawalDeductions());
        $this->view->setVariable('withdrawalOnly', $this->_branch->withdrawalOnlyDeductions());
        LookupStore::register('deductionType', $this->_branch->deductionTypes());
        
        $this->addModuleScript('recalc-new');
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    
    /**
     * check balance action
     * 
     * @return \Web\View\ViewModel
     */
    public function checkBalanceAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $form = new AjaxForm();
        $fields = [
            'member_id', 'advance_amount', 'advance_date', 'deduction_type_id', 
        ];
        $form->addFields($fields);
        if ($form->isPost()) {
            $values = $form->run();
            $balance = $this->_loadBalance($values);
            $response->addData($balance, 'result')
                     ->valid();
        }
        
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
        $store = new AdvanceWithdrawalStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        
        if (! $store->withdrawal() && $store->hasOverdueBalance()) {
            $this->layout->addModal('advance-withdrawal/repayment');
            $this->addModuleScript('advance-withdrawal/repayment');
        }
        
        $pairModel = new PairModel();
        LookupStore::register('administrator', $pairModel->administrator());

        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * repayment action
     * 
     * @return \Web\View\ViewModel
     */
    public function repaymentAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $id = $this->_getId();

        $manage = new ManageAdvanceRepayment(false);
        $manage->setManagerId($this->getUserId());
        $result = $manage->run($this->getAjaxResponse(), $id);
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    /**
     * get advance id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $memberId = $this->_model->selectColumnById('member_id', $id);
                $valid = $this->_branch->isMember($memberId);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'advance');
        }
        
        return $id;
    }
    
    /**
     * load advance balance
     * 
     * @param array $values
     * @return array
     */
    private function _loadBalance($values)
    {
        $balance = [
            'advance_date' => $values['advance_date'],
            'member' => '',
            'deduction_type' => DeductionHelper::getDeductionTypeName($values['deduction_type_id']),
            'advance_type' => 'Withdrawal',
            'deduction_date' => '',
            'closing_balance' => '',
            'advance_amount' => $values['advance_amount'],
            'overdue_amount' => '',
            'modified_closing' => '',
            'message' => '',
            'last' => '',
            'allowed' => 'NOT ALLOWED',
            'valid' => 0,
        ];
        
        $member = $this->_branch->getMemberById($values['member_id']);
        $balance['member'] = $member->name;
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member_id'])
                   ->whereId('deduction_type_id', $values['deduction_type_id']);
        $where1 = $this->_sql->getWhere();
        if ($this->_model->selectTotal($where1) > 0) {
            $lastAdvance = $this->_model->selectRow($where1, 'advance_date DESC');
            $balance['last'] = DateHelper::datepicker($lastAdvance->advance_date)
                             . ', ' . $lastAdvance->advance_amount;
            $this->_sql->whereGreater('advance_date', DateHelper::fromDatepicker($values['advance_date']), true);
            if ($this->_model->selectTotal($this->_sql->getWhere()) > 0) {
                $balance['message'] = 'Advance/Withdrawal exists on or after selected date';
                
                return $balance;
            }
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member_id'])
                   ->whereId('deduction_type_id', $values['deduction_type_id'])
                   ->whereLess('deduction_date', DateHelper::fromDatepicker($values['advance_date']));
        $where = $this->_sql->getWhere();
        
        if (0 == $this->_deductionMemberDailyModel->selectTotal($where)) {
            $balance['message'] = 'Previous deduction bill not found';
            
            return $balance;
        }
        
        $last = $this->_deductionMemberDailyModel->selectRow($where, 'deduction_date DESC');
        $balance['deduction_date'] = DateHelper::datepicker($last->deduction_date);
        $balance['valid'] = 1;
        $balance['closing_balance'] = $last->closing_balance;
        if ($last->closing_balance >= $values['advance_amount']) {
            $balance['overdue_amount'] = 0;
            $balance['modified_closing'] = $last->closing_balance - $values['advance_amount'];
            $balance['allowed'] = 'ALLOWED';
        } else {
            $balance['overdue_amount'] = $values['advance_amount'] - $last->closing_balance;
            $balance['modified_closing'] = 0;
            $balance['advance_type'] = 'Advance with OD';
            $advanceItems = $this->_branch->advanceDeductions();
            if (in_array($values['deduction_type_id'], $advanceItems)) {
                $balance['allowed'] = 'ALLOWED';
            } else {
                $balance['allowed'] = 'NOT ALLOWED (Withdrawal only)';
            }
        }
        
        return $balance;
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
        $breadrumb->add('Advances and Withdrawals', $this->view->actionUrl('advance-withdrawal'));
        $breadrumb->add($page);
    }
    
}
