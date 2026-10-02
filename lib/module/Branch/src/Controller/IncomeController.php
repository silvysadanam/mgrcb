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

use Model\{RPTransMember, RPTransactionDetails, Member, RPHeads};
use Branch\Form\{IncomeForm};
use Web\Utility\DateHelper;
use Branch\Utility\ManageIncome;

use Shared\Form\FilterForm;
use Shared\Utility\{IncomeStore, ManageCashReceipt};
use Shared\Utility\AccountSettings;

class IncomeController extends BranchController
{  
    /**
     *
     * @var \Model\AuctionChit 
     */
    private $_model;
    private $_rpHead;
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
          
        $this->_model = new RPTransactionDetails();
        $this->_rpHead = new RPHeads();
        $this->currentTab(Tab::TRANSACTION);
        $this->setFlashMessageTitle('Cash Receipt');
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
        $this->layout->addModal('income/summary');
        $this->layout->addModal('income/cash-receipt');
        $this->addModuleScript('cash-receipt');
        $this->addModuleScript('income-list-new');
        $receiptNo = $this->getNewCashReceiptNo();
        $this->registerPairs(['incomeHead', 'bank']);
        $form = new IncomeForm();
        $form->setModel($this->_model);
        $this->view->setVariable('receipt_date', DateHelper::datepicker());
        $this->view->setVariable('receipt_number', $receiptNo);
        $this->view->setVariable('fmc_id', $this->_fmc_id);        
        $this->registerForm($form);
        $this->registerPairs(['incomeHead', 'bank'], true);
        return $this->getViewModel();
    }
    
    public function getNewCashReceiptNo()
    {
        $receiptNo = 0;
        $options = new AccountSettings($this->_fmc_id);
        $currentCashReceiptNo = $options->getCashReceiptNo();
        if(!$currentCashReceiptNo){
            $receiptNo = 1;
        }else {
            $receiptNo = $currentCashReceiptNo + 1;
        }
        return $receiptNo;
    }
    
    /**
     * manage Cash Receipt action
     * 
     * @return \Web\View\ViewModel
     */
    public function cashReceiptAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $manage = new ManageIncome($this->getAjaxResponse());
        switch ($this->getParam('type')) {
            case 'head':
                $result = $manage->head();
                break;
            default:
                break;
        }
        
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
           // $this->redirect('income');
        }
        
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
        $store = new IncomeStore($id, $this->_fmc_id);
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
        $store = new IncomeStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['incomeHead']);
        $this->addModuleScript('income-view');
        $this->layout->addModal('income/view');
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

        $manage = new ManageCashReceipt($id, $this->_fmc_id, false);
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
        $store = new IncomeStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['incomeHead']);
        
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
        $fields = ['date', 'head'];
        $filters->addFields($fields);
        $filters->numeric(['head']);
        $filters->addFilter('date', $filters->getDateFilter());
       // $filters->addFilter('receiptNo', $filters->getMaxTwoFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, $active);
        $where = $this->_sql->getWhere();       
        
        //$where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where. " and `l`.fmc_id =  $this->_fmc_id and `l`.status='1' and `l`.transType = 'R'";
        } else {
            $queryTotal .= " WHERE `l`.fmc_id =  $this->_fmc_id and `l`.status='1' and `l`.transType = 'R'";
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`transDate`, `l`.`transNo`, `h`.`head_name`, `l`.`transNo`, `l`.`headID`, `l`.`member_id`, `l`.`donor_id`, '
                   . '`l`.`amount`, `l`.`transBy` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` '
                   . 'INNER JOIN  `' . $this->_rpHead->getTable() . '` `h` ON `h`.`id` = `l`.`headID` ';
             if (!empty($values['date']) || !empty($values['donor']) || !empty($values['member'])) {
                $query .= ' WHERE ' . $where;
            } else {
                $query .= " where `l`.fmc_id = $this->_fmc_id and `l`.status='1' and `l`.transType = 'R'";
            }
            $query .= ' ORDER BY `l`.`transDate`, `l`.`transNo` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        
        $this->view->setVariable('total', $total);
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
         if ($values['head'] > 0) {
                $this->_sql->whereId('headID', $values['head']);
        }    
      
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
            $this->setErrorFlashMessage('Invalid request', 'voucher');
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
        $this->setHeadTitle('Cash Receipt - ' . $page);
        $this->setPageHeading('Cash Receipt');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Cash Receipt', $this->view->actionUrl('income'));
        $breadrumb->add($page);
    }
    
}
