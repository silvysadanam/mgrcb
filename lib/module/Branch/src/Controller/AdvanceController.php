<?php
/**
 * Branch Advance Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;
use Branch\Form\AdvanceForm;

use Model\{Advance, DeductionBill, DeductionBillEntry};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\{DeductionHelper, AdvanceStore, ManageAdvance};
use Shared\Model\PairModel;
use Shared\Form\AjaxForm;


class AdvanceController extends BranchController
{  
    /**
     *
     * @var \Model\Advance 
     */
    private $_model;
    
    /**
     *
     * @var \Model\DeductionBill 
     */
    private $_deductionBillModel;
    
    /**
     *
     * @var \Model\DeductionBillEntry 
     */
    private $_deductionBillEntryModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Advance();
        $this->currentTab(Tab::ADVANCE);
        $this->_sql = $this->_model->getSql();
        $this->_deductionBillModel = new DeductionBill();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
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
        $fields = ['date', 'member', 'status', 'type'];
        $filters->addFields($fields);
        $filters->numeric(['member', 'status', 'type']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->addFilter('status', $filters->getMaxTwoFilter());
        $keys =  array_keys($deductions);
        array_unshift($keys, 0);
        $filters->addFilter('type', $filters->getCollectionFilter($keys));
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $memberFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('deduction_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $this->_sql->whereId('member_id', $values['member']);
                $memberFilter = false;
            }
        }
        
        if ($values['status'] > 0) {
            if (1 == $values['status']) {
                $this->_sql->whereZero('closed');
            } else {
                $this->_sql->whereOne('closed');
            }
        }
        
        if ($values['type'] > 0) {
            if (isset($deductions[$values['type']])) {
                $this->_sql->whereId('deduction_type_id', $values['type']);
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
        $this->addModuleScript('advance-list');
        $this->_breadcrumb('Advances');
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $this->redirect('advance');
        $form = new AdvanceForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Advance created');
                    $this->redirect('advance/view/id/' . $id);
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
                }
            }
        } else {
            $populate = [
                'advance_date' => DateHelper::datepicker(),
                'deduction_type_id' => 0,
            ];
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['member']);
        $this->view->setVariable('deductionSelector', $this->_branch->groupedSelector());
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        
        $this->addModuleScript('advance-create');
        $this->_breadcrumb('New Advance');
        
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
        $store = new AdvanceStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        
        $pairModel = new PairModel();
        LookupStore::register('administrator', $pairModel->administrator());
        
        if ($store->hasBalance()) {
            $this->layout->addModal('advance/view');
            $this->addModuleScript('advance-view');
        }
        
        $this->_breadcrumb('View Advance');
        
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

        $manage = new ManageAdvance(false);
        $manage->setManagerId($this->getUserId());
        $result = $manage->run($this->getAjaxResponse());
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
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
            'deduction_date' => '',
            'closing_balance' => '',
            'advance_amount' => $values['advance_amount'],
            'overdue_amount' => '',
            'modified_closing' => '',
            'message' => '',
            'last' => '',
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
            $this->_sql->whereGreater('advance_date', $values['advance_date'], true);
            if ($this->_model->selectTotal($this->_sql->getWhere()) > 0) {
                $balance['message'] = 'Advance exists on or after selected date';
                
                return $balance;
            }
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $values['member_id'])
                   ->whereLess('deduction_date', DateHelper::fromDatepicker($values['advance_date']));
        $where = $this->_sql->getWhere();
        
        if (0 == $this->_deductionBillModel->selectTotal($where)) {
            $balance['message'] = 'Previous deduction bill not found';
            
            return $balance;
        }
        
        $last = $this->_deductionBillModel->selectRow($where, 'deduction_date DESC');
        $balance['deduction_date'] = DateHelper::datepicker($last->deduction_date);
        $this->_sql->clear();
        $this->_sql->whereId('deduction_bill_id', $last->id)
                   ->whereId('deduction_type_id', $values['deduction_type_id']);
        $where2 = $this->_sql->getWhere();
        if (0 == $this->_deductionBillEntryModel->selectTotal($where2)) {
            $balance['message'] = 'Previous deduction bill closing balance not found';
            
            return $balance;
        }
            
        $entry = $this->_deductionBillEntryModel->selectRow($where2);
        $balance['valid'] = 1;
        $balance['closing_balance'] = $entry->closing_balance;
        if ($entry->closing_balance >= $values['advance_amount']) {
            $balance['overdue_amount'] = 0;
            $balance['modified_closing'] = $entry->closing_balance - $values['advance_amount'];
        } else {
            $balance['overdue_amount'] = $values['advance_amount'] - $entry->closing_balance;
            $balance['modified_closing'] = 0;
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
        $this->setHeadTitle('Deduction Bills - ' . $page);
        $this->setPageHeading('Deduction Bills');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add($page);
    }
    
}
