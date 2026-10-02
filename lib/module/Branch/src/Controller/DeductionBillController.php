<?php
/**
 * Branch DeductionBill Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\Tab;
use Branch\Form\{DeductionBillForm, DeductionBillBalanceCreateForm};
use Branch\Form\DeductionBillBalanceEditForm;

use Model\{DeductionBill, DeductionBillEntry, AuctionDaily, InitialDeductionBalance};
use Model\Member;

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\{DeductionHelper, DeductionBillStore};
use Shared\Model\PairModel;


class DeductionBillController extends BranchController
{  
    /**
     *
     * @var \Model\DeductionBill 
     */
    private $_model;
    
    /**
     *
     * @var \Model\DeductionBillEntry 
     */
    private $_deductionBillEntryModel;
    
    /**
     *
     * @var \Model\AuctionDaily 
     */
    private $_auctionDailyModel;
    
    /**
     *
     * @var \Model\InitialDeductionBalance 
     */
    private $_initialDeductionBalanceModel;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var boolean
     */
    private $_disableLegacy = false;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new DeductionBill();
        $this->currentTab(Tab::DEDUCTION_BILL);
        $this->_sql = $this->_model->getSql();
        $this->_deductionBillEntryModel = new DeductionBillEntry();
        $this->_auctionDailyModel = new AuctionDaily();
        $this->_initialDeductionBalanceModel = new InitialDeductionBalance();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        if (! $this->_branch->hasDeductions()) {
            $this->layout->setScript('deduction-bill/no-config');
            $this->_breadcrumb('Confirmed');
            
            return $this->getViewModel();
        }
        
        $filters = new FilterForm();
        $fields = ['date', 'member'];
        $filters->addFields($fields);
        $filters->numeric(['member']);
        $filters->addFilter('date', $filters->getDateFilter());
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
        
        if ($memberFilter) {
            $this->_sql->whereIn('member_id', $this->_branch->auctionMemberIds());
        }
        
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'deduction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['member', 'member_name', 'member_code']);
        $this->addModuleScript('deduction-bill-confirmed');
        $this->layout->addModal('deduction-bill/summary');
        $this->_breadcrumb('Confirmed');
        
        return $this->getViewModel();
    }
    
    /**
     * pending action
     * 
     * @return \Web\View\ViewModel
     */
    public function pendingAction()
    {
        if (! $this->_branch->hasDeductions()) {
            $this->layout->setScript('deduction-bill/no-config');
            $this->_breadcrumb('Pending');
            
            return $this->getViewModel();
        }
        
        $filters = new FilterForm();
        $fields = ['date', 'member'];
        $filters->addFields($fields);
        $filters->numeric(['member']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $memberFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('a.auction_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $this->_sql->whereId('a.member_id', $values['member']);
                $memberFilter = false;
            }
        }
        
        if ($memberFilter) {
            $this->_sql->whereIn('a.member_id', $this->_branch->auctionMemberIds());
        }
        
        $count = '(SELECT COUNT(`d`.`id`) FROM ' . $this->_model->getTableQuoted() . ' `d` '
               . 'WHERE `d`.`deduction_date` = `a`.`auction_date` AND '
               . '`d`.`member_id` = `a`.`member_id`) =0';
        $this->_sql->where($count);
        $where = $this->_sql->getWhere();
        
        $query1 = 'SELECT COUNT(`a`.`id`) FROM '
                . $this->_auctionDailyModel->getTableQuoted() . ' `a` WHERE ' . $where;
        
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `a`.`id`, `a`.`auction_date`, `a`.`member_id`, '
                    . '`a`.`amount`, `a`.`entries` '
                    . 'FROM ' . $this->_auctionDailyModel->getTableQuoted() . ' `a` '
                    . 'WHERE ' . $where . ' ORDER BY `a`.`auction_date`';
            if ('' != $paging->sqlLimit) {
                $query2 .= ' LIMIT ' . $paging->sqlLimit;
            }
            
            $this->view->setVariable('list', $this->_model->queryRows($query2));
            
            $query3 = 'SELECT `a`.`id` '
                    . 'FROM ' . $this->_auctionDailyModel->getTableQuoted() . ' `a` '
                    . 'WHERE ' . $where . ' ORDER BY `a`.`auction_date`';
            if ('' != $paging->sqlLimit) {
                $query3 .= ' LIMIT ' . $paging->sqlLimit;
            }
            
            $ids = $this->_model->queryColumns($query3);
            
            $this->_sql->clear();
            $this->_sql->whereIn('id', $ids);
            $members = $this->_auctionDailyModel->selectDistinct('member_id', $this->_sql->getWhere());
            $first = [];
            $query4 = 'SELECT MIN(`a`.`auction_date`) '
                    . 'FROM ' . $this->_auctionDailyModel->getTableQuoted() . ' `a` '
                    . 'WHERE `a`.`member_id` =? AND (SELECT COUNT(`d`.`id`) FROM '
                    . $this->_model->getTableQuoted() . ' `d` '
                    . 'WHERE `d`.`deduction_date` = `a`.`auction_date` AND '
                    . '`d`.`member_id` =?) =0';
            $prepared = $this->_model->getAdapter()->prepare($query4);
            foreach ($members as $memberId) {
                $prepared->execute([$memberId, $memberId]);
                $first[$memberId] = $prepared->fetchColumn();
                $prepared->closeCursor();
            }
            $this->view->setVariable('first', $first);
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['member', 'member_name', 'member_code']);
        $this->addModuleScript('list');
        $this->_breadcrumb('Pending');
        
        return $this->getViewModel();
    }
    
    /**
     * configuration action
     * 
     * @return \Web\View\ViewModel
     */
    public function configurationAction()
    {
        if (! $this->_branch->hasDeductions()) {
            $this->layout->setScript('deduction-bill/no-config');
            $this->_breadcrumb('Configuration');
            
            return $this->getViewModel();
        }
        
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        $list = $this->_branch->deductions();
        $this->view->setVariable('list', $this->_branch->deductions());
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($list)));
        
        return $this->getViewModel();
    }
    
    /**
     * summary action
     * 
     * @return \Web\View\ViewModel
     */
    public function summaryAction()
    {
        $id = $this->_getId();
        
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $store = new DeductionBillStore($id);
        $store->load();
        $response->addData($store->getSummary(), 'html')
                 ->valid();
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $this->_checkDisabled();
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_auctionDailyModel->hasId($id)) {
                $daily = $this->_auctionDailyModel->find($id);
                if ($this->_branch->isMember($daily->member_id)) {
                    $this->_sql->whereId('member_id', $daily->member_id)
                               ->whereEqual('deduction_date', $daily->auction_date);
                    if (0 == $this->_model->selectTotal($this->_sql->getWhere())) {
                        $this->_sql->clear();
                        $this->_sql->whereId('member_id', $daily->member_id)
                               ->whereGreater('deduction_date', $daily->auction_date);
                        if (0 == $this->_model->selectTotal($this->_sql->getWhere())) {
                            $query = 'SELECT COUNT(`a`.`id`) '
                                   . 'FROM ' . $this->_auctionDailyModel->getTableQuoted() . ' `a` '
                                   . 'WHERE `a`.`member_id` =' . $daily->member_id 
                                   . ' AND `a`.`auction_date` < \'' . $daily->auction_date . '\' AND (SELECT COUNT(`d`.`id`) FROM '
                                   . $this->_model->getTableQuoted() . ' `d` '
                                   . 'WHERE `d`.`deduction_date` = `a`.`auction_date` AND '
                                   . '`d`.`member_id` = ' . $daily->member_id . ') =0';
                            $valid = 0 == $this->_model->queryTotal($query);
                        }
                    }
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill');
        }
        
        $form = new DeductionBillForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($daily, $this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Deduction Bill created');
                    $this->redirect('deduction-bill/view/id/' . $id);
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
                'auction_amount' => $daily->amount,
                'available' => $daily->amount - $daily->advance,
                'member_paid_amount' => 0,
                'total_available' => 0,
                'deduction_item' => [],
                'deduction_amount' => [],
            ];
            
            $draft = $this->_branch->deductionBillDraft($daily->amount, $daily->advance, $daily->entries);
            $populate['balance'] = $draft['balance'];
            $populate['available'] = $draft['available'];
            $populate['total_available'] = $draft['available'];
            $populate['total_deduction'] = $draft['deduction'];
            foreach ($draft['items'] as $type => $value) {
                $populate['deduction_item'][] = $type;
                $populate['deduction_amount'][] = $value;
            }
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('daily', $daily);
        $this->view->setVariable('member', $this->_branch->getMemberById($daily->member_id));
        $deductions = $this->_branch->deductions();
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($deductions)));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        
        $this->addModuleScript('deduction-bill-create-new');
        $this->_breadcrumb('Create');
        
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
        $store = new DeductionBillStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        LookupStore::register('deductionGroup', DeductionHelper::deductionGroups());
        
        $pairModel = new PairModel();
        LookupStore::register('administrator', $pairModel->administrator());
        
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * opening balance action
     * 
     * @return \Web\View\ViewModel
     */
    public function openingBalanceAction()
    {
        if (! $this->_branch->hasDeductions()) {
            $this->layout->setScript('deduction-bill/no-config');
            $this->_breadcrumb('Opening Balance');
            
            return $this->getViewModel();
        }
        
        $initialBalanceMembers = [];
        $memberIds = $this->_branch->auctionMemberIds();
        
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $memberIds);
        $where = $this->_sql->getWhere();
        if ($this->_initialDeductionBalanceModel->selectTotal($where) > 0) {
            $initialBalanceMembers = $this->_initialDeductionBalanceModel->selectDistinct('member_id', $where);
        }
        
        $this->view->setVariable('initialBalance', $initialBalanceMembers);
        
        $memberModel = new Member();
        $this->_sql->clear();
        $this->_sql->whereIn('id', $memberIds);
        $this->view->setVariable('members', $memberModel->selectFields(['id', 'name', 'code'], $this->_sql->getWhere(), 'name'));
        $this->addModuleScript('list');
        $this->_breadcrumb('Initial Opening Balance');
        
        return $this->getViewModel();
    }
    
     /**
     * opening balance view action
     * 
     * @return \Web\View\ViewModel
     */
    public function openingBalanceViewAction()
    {
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_branch->isMember($id)) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $id);
                $valid = $this->_initialDeductionBalanceModel->selectTotal($this->_sql->getWhere()) > 0;
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill/opening-balance');
        }
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $id);
        $this->view->setVariable('list', $this->_initialDeductionBalanceModel->select($this->_sql->getWhere()));
        $this->view->setVariable('member', $this->_branch->getMemberById($id));
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        LookupStore::register('malayalam', DeductionHelper::malayalamDeductionTypes());
        $this->_breadcrumb('Initial Opening Balance - View');
        
        return $this->getViewModel();
    }
    
    /**
     * opening balance create action
     * 
     * @return \Web\View\ViewModel
     */
    public function openingBalanceCreateAction()
    {
        if (! $this->_branch->hasDeductions()) {
            $this->layout->setScript('deduction-bill/no-config');
            $this->_breadcrumb('Opening Balance');
            
            return $this->getViewModel();
        }
        
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_branch->isMember($id)) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $id);
                $valid = 0 == $this->_initialDeductionBalanceModel->selectTotal($this->_sql->getWhere());
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill/opening-balance');
        }
        
        $deductions = $this->_branch->deductions();
        $date = '01/04/2022';
        
        $form = new DeductionBillBalanceCreateForm();
        $form->setModel($this->_initialDeductionBalanceModel);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create($id);
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Deduction Bill Opening Balance saved');
                    $this->redirect('deduction-bill/opening-balance');
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
                'deduction_item' => [],
                'balance' => [],
                'opening_date' => [],
            ];
            
            $types = array_keys($deductions);
            foreach ($types as $typeId) {
                $populate['deduction_item'][] = $typeId;
                $populate['balance'][] = 0;
                $populate['opening_date'][] = $date;
            }

            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('member', $this->_branch->getMemberById($id));
        $this->view->setVariable('deductions', $deductions);
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        LookupStore::register('malayalam', DeductionHelper::malayalamDeductionTypes());
        
        $this->_breadcrumb('Set Initial Opening Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * opening balance edit action
     * 
     * @return \Web\View\ViewModel
     */
    public function openingBalanceEditAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_branch->isMember($id)) {
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $id);
                $valid = $this->_initialDeductionBalanceModel->selectTotal($this->_sql->getWhere()) > 0;
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill/opening-balance');
        }
        
        $deductions = $this->_branch->deductions();
        $types = array_keys($deductions);
        $date = '01/04/2022';
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $id);
        $current = $this->_initialDeductionBalanceModel->selectIndexed($this->_sql->getWhere(), 'id', null, 'deduction_type_id');
        
        $form = new DeductionBillBalanceEditForm();
        $form->setModel($this->_initialDeductionBalanceModel);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    
                    $remove = [];
                    foreach ($current as $item) {
                        if (! in_array($item->deduction_type_id, $types)) {
                            $remove[] = $item->id;
                        }
                    }
                    
                    if (! empty($remove)) {
                        $this->_initialDeductionBalanceModel->deleteByIds($remove);
                    }
                    
                    $form->update($id);
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Deduction Bill Opening Balance updated');
                    $this->redirect('deduction-bill/opening-balance');
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
                'deduction_item' => [],
                'balance' => [],
                'opening_date' => [],
            ];
            
            foreach ($types as $typeId) {
                $populate['deduction_item'][] = $typeId;
                if (isset($current[$typeId])) {
                    $populate['balance'][] = $current[$typeId]->opening_balance;
                    $populate['opening_date'][] = $current[$typeId]->opening_date;
                } else {
                    $populate['balance'][] = 0;
                    $populate['opening_date'][] = $date;
                }
            }

            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('member', $this->_branch->getMemberById($id));
        $this->view->setVariable('deductions', $deductions);
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        LookupStore::register('malayalam', DeductionHelper::malayalamDeductionTypes());
        
        $this->_breadcrumb('Edit Initial Opening Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * receipt action
     * 
     * @return \Web\View\ViewModel
     */
    public function receiptAction()
    {
        $id = $this->_getId();
        $store = new DeductionBillStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        LookupStore::register('deductionType', DeductionHelper::malayalamDeductionTypes());
        LookupStore::register('deductionGroup', DeductionHelper::deductionGroups());
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * print action
     * 
     * @return \Web\View\ViewModel
     */
    public function printAction()
    {
        $id = $this->_getId();
        $store = new DeductionBillStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        LookupStore::register('deductionGroup', DeductionHelper::deductionGroups());
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * closing balance action
     * 
     * @return \Web\View\ViewModel
     */
    public function closingBalanceAction()
    {
        $deductions = $this->_branch->deductionTypes();
        $deductionTypeIds = array_keys($deductions);
        $members = $this->_branch->memberLookup();
        $memberIds = array_keys($members);
        $list = [];
        if ($this->_model->selectTotal() > 0) {
            $query = 'SELECT `b`.`deduction_date`, `e`.`closing_balance` FROM '
                   . $this->_model->getTableQuoted() . ' `b` '
                   . 'INNER JOIN ' . $this->_deductionBillEntryModel->getTableQuoted()
                   . ' `e` ON `e`.`deduction_bill_id` = `b`.`id` '
                   . 'WHERE `b`.`member_id` = ? AND `e`.`deduction_type_id` = ? '
                    . 'ORDER BY `b`.`deduction_date` DESC LIMIT 1';

            $select = $this->_model->prepare($query);
            foreach ($memberIds as $memberId) {
                foreach ($deductionTypeIds as $typeId) {
                    $select->execute([$memberId, $typeId]);
                    if ($select->rowCount() > 0) {
                        $row = $select->fetch();
                        $select->closeCursor();
                        if (! isset($list[$memberId])) {
                            $list[$memberId] = [];
                        }
                        $list[$memberId][$typeId] = $row;
                    }
                }
            }
        }
        
        $this->view->setVariable('list', $list);
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($deductions)));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        LookupStore::register('member', $members);
        
        $this->_breadcrumb('Current Closing Balance');
        
        return $this->getViewModel();
    }
    
    /**
     * balance history action
     * 
     * @return \Web\View\ViewModel
     */
    public function balanceHistoryAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_branch->isMember($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill/closing-balance');
        }
        
        $this->view->setVariable('member', $this->_branch->getMemberById($id));
        $deductions = $this->_branch->deductionTypes();
        
        $filters = new FilterForm();
        $fields = ['date', 'type'];
        $filters->addFields($fields);
        $filters->numeric(['type']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $this->_sql->clear();
        $this->_sql->whereId('b.member_id', $id);
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('b.deduction_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['type'] > 0) {
            if (isset($deductions[$values['type']])) {
                $this->_sql->whereId('e.deduction_type_id', $values['type']);
            }
        }
        
        $from = 'FROM ' . $this->_deductionBillEntryModel->getTableQuoted() . ' `e` '
              . 'INNER JOIN ' . $this->_model->getTableQuoted() . ' `b`  ON `b`.`id` = `e`.`deduction_bill_id` '
              . 'WHERE ' . $this->_sql->getWhere();
        $query1 = 'SELECT COUNT(`e`.`id`) ' . $from;
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `e`.`deduction_type_id`, `e`.`opening_balance`, '
                    . '`e`.`closing_balance`, `b`.`deduction_date` ' . $from
                    . 'ORDER BY `b`.`deduction_date` DESC LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        
        $this->view->setVariable('total', $total);
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $id);
        $where = $this->_sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            $this->view->setVariable('minDate', DateHelper::datepicker($this->_model->selectMin('deduction_date', $where)));
        } else {
            $this->view->setVariable('minDate', DateHelper::datepicker());
        }
        
        $this->view->setVariable('maxDate', DateHelper::datepicker());
        $this->view->setVariable('deductionSelector', $this->_branch->groupedSelector());
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        
        $this->_breadcrumb('Closing Balance History');
        
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
            $this->setErrorFlashMessage('Invalid request', 'deduction-bill');
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
        $this->setHeadTitle('Deduction Bills - ' . $page);
        $this->setPageHeading('Deduction Bills');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction-bill'));
        $breadrumb->add($page);
    }
    
    /**
     * check deduction bill disabled
     */
    private function _checkDisabled()
    {
        if ($this->_disableLegacy) {
            $this->setErrorFlashMessage('Old deduction bill entry disabled', 'deduction-bill');
        }
    }
    
}
