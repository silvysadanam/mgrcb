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
use Branch\Form\{DeductionBillBalanceCreateForm, DeductionBillBalanceEditForm};
use Branch\Form\Deduction\CreateForm;

use Model\{Member, Deduction, AuctionDaily, InitialDeductionBalance, AuctionChit};

use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;

use Shared\Form\FilterForm;
use Shared\Utility\{DeductionHelper, DeductionStore};
use Shared\Model\PairModel;


class DeductionController extends BranchController
{  
    /**
     *
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     *
     * @var \Model\Deduction
     */
    private $_deductionModel;
    
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
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new AuctionChit();
        $this->currentTab(Tab::DEDUCTION_BILL_NEW);
        $this->_sql = $this->_model->getSql();
        $this->_deductionModel = new Deduction();
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
        $filters = new FilterForm();
        $fields = ['date', 'member'];
        $filters->addFields($fields);
        $filters->numeric(['member']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $this->_sql->whereOne('has_deduction_bill');
        $memberFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('auction_date', DateHelper::fromDatepicker($values['date']));
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
            $this->view->setVariable('list', $this->_model->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['member', 'member_name', 'member_code', 'donor']);
        $this->addModuleScript('deduction/list');
        $this->layout->addModal('deduction/summary');
        $this->_breadcrumb('List');
        
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
            $this->layout->setScript('deduction/no-config');
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
        $store = new DeductionStore($id);
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
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $auctionChit = $this->_model->find($id);
                if ($this->_branch->isMember($auctionChit->member_id) && 0 == $auctionChit->has_deduction_bill) {
                    $valid = true;
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction');
        }
        
        $deductions = $this->_branch->deductions();
        $form = new CreateForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create($auctionChit, $this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setFlashMessage('Deduction Bill created');
                    $this->redirect('deduction/view/id/' . $id);
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
                'auction_amount' => $auctionChit->amount,
                'available' => $auctionChit->amount - $auctionChit->advance,
                'member_paid_amount' => 0,
                'total_available' => 0,
                'deduction_item' => [],
                'deduction_amount' => [],
            ];
            
            $draft = $this->_branch->deductionBillDraft($auctionChit->amount, $auctionChit->advance, 1);
            $populate['balance'] = $draft['balance'];
            $populate['available'] = $draft['available'];
            $populate['total_available'] = $draft['available'];
            $populate['total_deduction'] = $draft['deduction'];
            foreach ($draft['items'] as $type => $value) {
                $populate['deduction_item'][] = $type;
                $populate['deduction_amount'][$type] = $value;
            }
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('auctionChit', $auctionChit);
        $this->view->setVariable('member', $this->_branch->getMemberById($auctionChit->member_id));
        $this->view->setVariable('donor', $this->_branch->getMemberById($auctionChit->donor_id));
        
        $this->view->setVariable('deductions', $deductions);
        $this->view->setVariable('groups', DeductionHelper::deductionGroups());
        $this->view->setVariable('grouped', DeductionHelper::groupedDeductionTypeIdsSelection(array_keys($deductions)));
        LookupStore::register('deductionType', DeductionHelper::combinedDeductionTypes());
        
        $this->addModuleScript('deduction/create');
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
        $store = new DeductionStore($id);
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
            $this->layout->setScript('deduction/no-config');
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
            $this->setErrorFlashMessage('Invalid request', 'deduction/opening-balance');
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
            $this->setErrorFlashMessage('Invalid request', 'deduction/opening-balance');
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
                    $this->redirect('deduction/opening-balance');
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
                    $this->redirect('deduction/opening-balance');
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
        $store = new DeductionStore($id);
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
        $store = new DeductionStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        LookupStore::register('deductionType', DeductionHelper::deductionTypes());
        LookupStore::register('deductionGroup', DeductionHelper::deductionGroups());
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    
    /**
     * get auction chit id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $auctionChit = $this->_model->find($id);
                if ($this->_branch->isMember($auctionChit->member_id)) {
                    $valid = $auctionChit->has_deduction_bill > 0;
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'deduction');
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
        $breadrumb->add('Deduction Bills', $this->view->actionUrl('deduction'));
        $breadrumb->add($page);
    }
    
}
