<?php
/**
 * Branch AuctionChit Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Branch\Constant\{Tab, FlashMessage};

use Model\{RPTransMember, RPTransactionDetails, Member};
use Branch\Form\{VoucherForm};
use Web\Utility\DateHelper;
use Web\Utility\FormHelper;
use Web\View\Support\LookupStore;


use Shared\Form\FilterForm;
use Shared\Utility\{VoucherStore, ManageVoucher};
use Shared\Utility\AccountSettings;
use Shared\Utility\Transactions;
use Shared\Model\PairModel;
class VoucherController extends BackendController
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
          
        $this->_model = new RPTransactionDetails();
        //$this->_rpTransModel = new RPTransactionDetails();
        $this->currentTab(Tab::TRANSACTION);
        $this->setFlashMessageTitle('Voucher');
        $this->_sql = $this->_model->getSql();
        $this->_fmc_id = FormHelper::FWS_ACCOUNT_ID;
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
        $this->layout->addModal('voucher/summary');
        $this->addModuleScript('voucher-list-new');
        $this->registerPairs(['member', 'donor', 'expenseHead']);
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
        $this->layout->addModal('voucher/summary');
        $this->addModuleScript('voucher-list-new');
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
        $store = new VoucherStore($id, $this->_fmc_id);
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
        $store = new VoucherStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['expenseHead']);
        $this->addModuleScript('voucher-view');
        $this->layout->addModal('voucher/view');
        $this->_breadcrumb('View');
        $this->registerPairs(['bank'], true);
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

        $manage = new ManageVoucher($id, $this->_fmc_id, false);
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
        $store = new VoucherStore($id, $this->_fmc_id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['expenseHead']);
        
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
        $fields = ['date', 'donor', 'member'];
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
        if (!empty($values['date']) || !empty($values['donor']) || !empty($values['member'])) {
            $queryTotal .= ' WHERE ' . $where. " and `l`.fmc_id =  $this->_fmc_id and status='1' and `l`.transType = 'P'";
        } else {
            $queryTotal .= " WHERE `l`.fmc_id =  $this->_fmc_id and status='1' and `l`.transType = 'P'";
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query = 'SELECT `l`.`id`, `l`.`transDate`, `l`.`transNo`, `l`.`status`, `l`.`non_member`, `l`.`particulars`, `l`.`member_id`, `l`.`donor_id`, '
                   . '`l`.`amount`, `l`.`transBy` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` ';
             if (!empty($values['date']) || !empty($values['donor']) || !empty($values['member'])) {
                $query .= ' WHERE ' . $where . " and `l`.fmc_id =  $this->_fmc_id ";
            } else {
                $query .= " where `l`.fmc_id = $this->_fmc_id and `l`.transType = 'P'";
            }
            $query .= ' ORDER BY `l`.`transDate` desc, `l`.`transNo` desc LIMIT ' . $paging->sqlLimit;
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
        
        if ($values['member'] > 0) {
                $memberFilter = false;
                $this->_sql->whereId('member_id', $values['member']);
        }
        
        if ($values['donor'] > 0) {
                $this->_sql->whereId('donor_id', $values['donor']);
                $memberFilter = false;
        }
        
        if ($memberFilter) {
           // $this->_sql->whereIn('member_id', $this->_branch->auctionMemberIds($active));
        }
    }
    /**
     * receipt action
     * 
     * @return \Web\View\ViewModel
     */
    public function paymentAction()
    {
        $trans = new Transactions();
        $voucherNo =  $trans->getNextVoucherNo($this->_fmc_id);
        $code = $trans->getFmcCode($this->_fmc_id);
        //$voucherNo = 0;
        $form = new VoucherForm($this->_fmc_id);
        $form->setModel($this->_model);
        if(empty($voucherNo)){
            $this->setErrorFlashMessage('Account settings not configured for this branch', 'voucher');
        }
        //$fmc_id = $this->_branch->id();
       
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId(), $this->_fmc_id);
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('voucher/view/id/' . $id);
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
                'voucher_number' => $voucherNo,
                'head' => [0],
                'code' => $code,
                'amount' => [''],
                'total' => [0, 0],
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['member', 'donor', 'expenseHead', 'bank', 'fmc'], true);
        $this->addModuleScript('voucher-create');
        $this->_breadcrumb('New');
        //$pairModel = new PairModel();
        //LookupStore::register('fmc', $pairModel->fmc());
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
        $this->setHeadTitle('Vouchers - ' . $page);
        $this->setPageHeading('Vouchers');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Voucher', $this->view->actionUrl('voucher'));
        $breadrumb->add($page);
    }
    
}
