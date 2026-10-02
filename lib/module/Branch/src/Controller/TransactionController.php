<?php
/**
 * Branch AuctionChit Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\{Tab, FlashMessage};

use Model\{RPTransMember, RPTransMemberDetails, Member};
use Branch\Form\{TransactionForm};
use Web\Utility\DateHelper;

use Shared\Form\FilterForm;
use Shared\Utility\{ReceiptStore, ManageReceipt};
use Shared\Utility\AccountSettings;

class TransactionController extends BranchController
{  
    /**
     *
     * @var \Model\AuctionChit 
     */
    private $_model;
    
    /**
     * 
     * @var array
     */
    private $_options = [];
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
          
        $this->_model = new RPTransMember();
        $this->_rpTransModel = new RPTransMemberDetails();
        $this->currentTab(Tab::TRANSACTION);
        $this->setFlashMessageTitle('Transaction');
        $this->_sql = $this->_model->getSql();
        $this->_fmc_id = $this->_branch->id();
        $this->_memberModel = new Member();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list();
        $this->_breadcrumb('List');
        $this->layout->addModal('transaction/summary');
        $this->addModuleScript('transaction-list-new');
        $this->registerPairs(['member', 'donor', 'receiptHead']);
        $this->view->setVariable('deductionOptions', [ 1 => 'Confirmed', 'Pending']);
        return $this->getViewModel();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function activeAction()
    {
        $this->_list(true);
        $this->_breadcrumb('List (Active)');
        $this->layout->addModal('transaction/summary');
        $this->addModuleScript('transaction-list-new');
        $this->registerPairs(['donor']);
        $this->registerPairs(['member'], true);
        $this->view->setVariable('deductionOptions', [ 1 => 'Confirmed', 'Pending']);
        
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
        $store = new ReceiptStore($id, $this->_fmc_id);
        $store->load();
        $response->addData($store->getSummary(), 'html')
                 ->valid();
        
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
        $store = new ReceiptStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['receiptHead']);
        $this->addModuleScript('receipt-view');
        $this->layout->addModal('transaction/view');
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * manage action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageAction()
    {
        $id = $this->_getId();
        
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageReceipt($id, $this->_fmc_id, false);
        $manage->setManagerId($this->getUserId());
        $result = $manage->run($this->getAjaxResponse(), $this->getParamId('action'));
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
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
        $store = new ReceiptStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['receiptHead']);
        
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
   
    
    /**
     * load start date
     * 
     * @param string $where
     */
    private function _loadStartDate($where)
    {
        $this->_options['start_date'] = $this->_model->selectMin('auction_date', $where);
        $minDate = DateHelper::getDateTime($this->_options['start_date']);
        $this->_options['min_date'] = '01/'. $minDate->format('m/Y');
        $this->view->setVariable('min_date', $this->_options['min_date']);
    }
    
    /**
     * load list
     * 
     * @param boolean $active
     */
    private function _list($active = false)
    {
        $filters = new FilterForm();
        $fields = ['date', 'donor', 'member', 'receipt_type'];
        $filters->addFields($fields);
        $filters->numeric(['donor', 'member']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->addFilter('receiptNo', $filters->getMaxTwoFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, $active);
        $where = $this->_sql->getWhere();       
        
        //$where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        //if (! empty($where)) {
        if (!empty($values['date']) || !empty($values['donor']) || !empty($values['member'])  || !empty($values['receipt_type'])) {
            $queryTotal .= ' WHERE ' . $where. " and `l`.fmc_id =  $this->_fmc_id and status='1'";
        } else {
            $queryTotal .= " WHERE `l`.fmc_id =  $this->_fmc_id and status='1'";
        }
        
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`action_chit_id`, `l`.`transDate`, `l`.`status`, `l`.`non_member`, `l`.`receiptNo`, `l`.`remarks`, `l`.`member_id`, `l`.`donor_id`, '
                   . 'sum(`f`.`amount`) as amount, `l`.`transBy` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` '
                   . 'INNER JOIN  `' . $this->_rpTransModel->getTable() . '` `f` ON `l`.`id` = `f`.`transID`';
                  // . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` ';
             if (!empty($values['date']) || !empty($values['donor']) || !empty($values['member']) || !empty($values['receipt_type'])) {
                $query .= ' WHERE ' . $where;
            }
            $query .= " and `l`.fmc_id = $this->_fmc_id  and transType = 'R'";
            $query .= ' GROUP BY `f`.transID ORDER BY `l`.`transDate` desc, `l`.`receiptNo` desc LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('receipt_type', $values['receipt_type']);
    }
    
    /**
     * generate entry list
     * 
     * @param string $where
     */
    private function _entryList($where)
    {
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $ids = $this->_model->selectIds($where, 'id');
            $list = [];
            foreach ($ids as $id) {
                $list[] = new AuctionChitList($id);
            }
            $this->view->setVariable('list', $list);
        }
        
        $this->view->setVariable('total', $total);
    }
    
    /**
     * apply filters
     * 
     * @param array $values
     * @param boolean $active
     */
    private function _applyFilters($values, $active = false)
    {
        $memberFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('transDate', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $memberFilter = false;
                $this->_sql->whereId('member_id', $values['member']);
            }
        }
        
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                $this->_sql->whereId('donor_id', $values['donor']);
                $memberFilter = false;
            }
        }
        if ($values['receipt_type'] == 'receipt') {
              $this->_sql->where('receiptNo > 0');
              $memberFilter = false;
        }
        if ($values['receipt_type'] == 'auction') {
              $this->_sql->where('action_chit_id > 0');
              $memberFilter = false;
        }
        
        if ($memberFilter) {
            $this->_sql->whereIn('member_id', $this->_branch->auctionMemberIds($active));
        }
    }
    /**
     * receipt action
     * 
     * @return \Web\View\ViewModel
     */
    public function receiptAction()
    {
        $receiptNo = 0;
        $form = new TransactionForm();
        $form->setModel($this->_model);
        //$fmc_id = $this->_branch->id();
        $options = new AccountSettings($this->_fmc_id);
        $options->load();
        $optionReceiptNo = $options->receiptNo();
        if(empty($optionReceiptNo)){
            $this->setErrorFlashMessage('Account settings not configured for this branch', 'transaction');
        }
        $currentReceiptNo = $options->getNextReceiptNo();
        if(!$currentReceiptNo){
            $receiptNo = $optionReceiptNo;
        }else {
            if($optionReceiptNo > $currentReceiptNo){
                $receiptNo = $optionReceiptNo;
            } else {
                $receiptNo = $currentReceiptNo + 1;
            }
        }
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId(), $this->_fmc_id);
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('transaction/view/id/' . $id);
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
                'receipt_date' => DateHelper::datepicker(),
                'receipt_number' => $receiptNo,
                'head' => [0],
                'amount' => [''],
                'total' => [0, 0],
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['member', 'donor', 'receiptHead', 'bank'], true);
        $this->addModuleScript('receipt-create');
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    /**
     * get receipt id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                //$memberId = $this->_model->selectColumnById('member_id', $id);
                //$valid = $this->_branch->isFmcMember($memberId);
                $valid = true;
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'transaction');
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
        $this->setHeadTitle('Member Receipts - ' . $page);
        $this->setPageHeading('Member Receipts');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Member Receipt', $this->view->actionUrl('transaction'));
        $breadrumb->add($page);
    }
    
}
