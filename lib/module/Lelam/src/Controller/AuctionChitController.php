<?php
/**
 * Lelam AuctionChit Controller
 * 
 * @category Lelam
 * @package  Lelam\Controller
 */

namespace Lelam\Controller;

use Lelam\LelamController;
use Lelam\Constant\Tab;

use Model\{AuctionChit, AuctioneerDaily, AuctioneerMonthly};
use Web\Utility\DateHelper;

use Shared\Form\FilterForm;
use Shared\Utility\{AuctionChitStore, ManageAuctionChit, AuctionChitList};


class AuctionChitController extends LelamController
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
        $this->_model = new AuctionChit();
        $this->currentTab(Tab::AUCTION);
        $this->_sql = $this->_model->getSql();
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
        $this->_applyFilters($values);
        $this->_list($this->_sql->getWhere());
        $this->view->setVariable('min_date', $this->_getMinDate());
        $this->_breadcrumb('List');
        $this->layout->addModal('auction-chit/summary');
        $this->addModuleScript('auction-chit-list');
        $this->registerPairs(['member']);
        
        return $this->getViewModel();
    }
    
    /**
     * today action
     * 
     * @return \Web\View\ViewModel
     */
    public function todayAction()
    {
        $filters = new FilterForm();
        $fields = ['member'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, DateHelper::today());
        $this->_entryList();
        $this->_breadcrumb('Today');
        $this->registerPairs(['member']);
        
        return $this->getViewModel();
    }
    
    /**
     * last day action
     * 
     * @return \Web\View\ViewModel
     */
    public function lastDayAction()
    {
        $sql = $this->_model->getSql();
        $sql->whereId('donor_id', $this->getUserId())
            ->whereLess('auction_date', DateHelper::today());
        $where1 = $sql->getWhere();
        if (0 == $this->_model->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chits found', 'auction-chit');
        }
        
        $auctionDate = $this->_model->selectMax('auction_date', $where1);
        
        $filters = new FilterForm();
        $fields = ['member'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, $auctionDate);
        $this->_entryList();
        $this->view->setVariable('auctionDate', $auctionDate);
        $this->_breadcrumb('Last Day');
        $this->registerPairs(['member']);
        
        return $this->getViewModel();
    }
    
    /**
     * day select action
     * 
     * @return \Web\View\ViewModel
     */
    public function daySelectAction()
    {
        $dailyModel = new AuctioneerDaily();
        $sql = $this->_model->getSql();
        $sql->whereId('donor_id', $this->getUserId());
        $where = $sql->getWhere();
        $total = $dailyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $dailyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->_breadcrumb('View by Day');
        
        return $this->getViewModel();
    }
    
    /**
     * day view action
     * 
     * @return \Web\View\ViewModel
     */
    public function dayViewAction()
    {
        $valid = false;
        $dateId = $this->getParamId('date');
        if ($dateId > 0) {
            if (false !== ($date = DateHelper::fromFormat($dateId, 'Ymd'))) {
                $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                $this->view->setVariable('auctionDate', $date->format(DateHelper::DATEPICKER));
                $valid = true;
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('No Auction Chits found', 'auction-chit');
        }
        
        $this->view->setVariable('date', $dateId);
        $filters = new FilterForm();
        $fields = ['member'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values, $auctionDate);
        $this->_entryList();
        $this->_breadcrumb('View by Day');
        $this->registerPairs(['member']);
        
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
        $store = new AuctionChitStore($id);
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
        $store = new AuctionChitStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['medicine']);
        if ($store->edit()) {
            $this->addModuleScript('auction-chit-view');
            $this->layout->addModal('auction-chit/view');
        }
        
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

        $manage = new ManageAuctionChit($id, false);
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
        $store = new AuctionChitStore($id);
        $store->load();
        $this->view->setVariable('store', $store);
        $this->registerPairs(['medicine']);
        
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * daily print action
     * 
     * @return \Web\View\ViewModel
     */
    public function dailyPrintAction()
    {
        $dailyModel = new AuctioneerDaily();
        $valid = false;
        $dateId = $this->getParamId('date');
        if ($dateId > 0) {
            if (false !== ($date = DateHelper::fromFormat($dateId, 'Ymd'))) {
                $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                $this->view->setVariable('auctionDate', $date->format(DateHelper::DATEPICKER));
                $valid = true;
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $this->_sql->whereId('donor_id', $this->getUserId())
                   ->whereEqual('auction_date', $auctionDate);
        $where = $this->_sql->getWhere();
        if (0 == $dailyModel->selectTotal($where)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $dailyModel->selectRow($where));
        $this->view->setVariable('auctionDate', DateHelper::datepicker($auctionDate));
        
        $this->view->setVariable('list', $this->_model->select($where));
        $this->registerPairs(['member_code', 'member_name']);

        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * monthly action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyAction()
    {
        $monthlyModel = new AuctioneerMonthly();
        $this->_sql->whereId('donor_id', $this->getUserId());
        $where1 = $this->_sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chit found', 'auction-chit');
        }
        
        $this->_loadStartDate($where1);
        
        $filters = new FilterForm();
        $filters->addFields(['date']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $date = '';
        if ('' != $values['date']) {
            $date1 = DateHelper::fromFormat($values['date'], DateHelper::DATEPICKER);
            $date1->setDate($date1->format('Y'), $date1->format('n'), $date1->format('t'));
            $date = $date1->format(DateHelper::MYSQL_DATE);
            $this->_sql->whereEqual('auction_date', $date);
        }
        $where = $this->_sql->getWhere();
        $total = $monthlyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $monthlyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('list');
        $this->_breadcrumb('Monthly Summary');
        
        return $this->getViewModel();
    }
    
    /**
     * monthly print action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyPrintAction()
    {
        $monthlyModel = new AuctioneerMonthly();
        $filters = new FilterForm();
        $filters->addFields(['date', 'donor']);
        $filters->numeric(['date', 'donor']);
        $values = $filters->getFilteredValues();
        $valid = false;
        $dateId = $this->getParamId('date');
        
        if ($dateId > 0) {
            if (false !== ($date = DateHelper::fromFormat($dateId, 'Ymd'))) {
                $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                $this->view->setVariable('auctionMonth', $date->format('F Y'));
                $valid = true;
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $this->_sql->whereEqual('auction_date', $auctionDate);
        $where1 = $this->_sql->getWhere();
        if (0 == $monthlyModel->selectTotal($where1)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $monthlyModel->selectRow($where1));
        $dateTime = DateHelper::getDateTime($auctionDate);
        $dateTime->setDate($dateTime->format('Y'), $dateTime->format('n'), 1);
        $start = $dateTime->format(DateHelper::MYSQL_DATE);
        $this->_sql->clear();
        $this->_sql->whereId('donor_id', $this->getUserId())
                   ->whereBetween('auction_date', $start, $auctionDate);
        
        $where = $this->_sql->getWhere();
        $this->view->setVariable('list', $this->_model->select($where, 'auction_date'));
        $this->registerPairs(['member_name', 'member_code']);

        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    
    /**
     * load list
     */
    private function _list($where)
    {
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
    }
    
    private function _entryList()
    {
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $ids = $this->_model->selectIds($where, 'id');
            $list = [];
            foreach ($ids as $id) {
                $list[] = new AuctionChitList($id);
            }
            $this->view->setVariable('list', $list);
            $this->registerPairs(['medicine']);
        }
        
        $this->view->setVariable('total', $total);
    }
    
    /**
     * apply filters
     * 
     * @param array $values
     * @param string $date
     */
    private function _applyFilters($values, $date = '')
    {
        if ('' != $date) {
            $this->_sql->whereEqual('auction_date', $date);
        } elseif (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('auction_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $this->_sql->whereId('member_id', $values['member']);
            }
        }
        
        $this->_sql->whereId('donor_id', $this->getUserId());
    }
    
    /**
     * get min date
     * 
     * @return string
     */
    private function _getMinDate()
    {
        $sql = $this->_model->getSql();
        $sql->whereId('donor_id', $this->getUserId());
        $where = $sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            return '';
        }
        
        return $this->_model->selectMin('auction_date', $where);
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
                $valid = $this->getUserId() == $this->_model->selectColumnById('donor_id', $id);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'auction-chit');
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
        $this->setHeadTitle('Auction Chits - ' . $page);
        $this->setPageHeading('Auction Chits');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Auction Chits', $this->view->actionUrl('auction-chit'));
        $breadrumb->add($page);
    }
    
    
    /* =============== */
    
    
    
    
    
    
    
    private function _loadStartDate($where)
    {
        $this->_options['start_date'] = $this->_model->selectMin('auction_date', $where);
        $minDate = DateHelper::getDateTime($this->_options['start_date']);
        $this->_options['min_date'] = '01/'. $minDate->format('m/Y');
        $this->view->setVariable('min_date', $this->_options['min_date']);
    }
    
    
}
