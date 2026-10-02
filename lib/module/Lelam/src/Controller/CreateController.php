<?php
/**
 * Lelam AuctionChit Controller
 * 
 * @category Lelam
 * @package  Lelam\Controller
 */

namespace Lelam\Controller;

use Lelam\LelamController;
use Lelam\Constant\{Tab, FlashMessage};
use Lelam\Form\AuctionChitForm;

use Model\{AuctionChit, AuctionFmcDaily, AuctionMonthly};
use Web\Utility\DateHelper;

use Shared\Form\FilterForm;
use Shared\Utility\{AuctionChitStore, ManageAuctionChit, AuctionChitList};


class CreateController extends LelamController
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
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new AuctionChit();
        $this->currentTab(Tab::AUCTION);
        $this->setFlashMessageTitle('Auction Chit');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new AuctionChitForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            $form->setAuctioneerId($this->getUserId());
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('auction-chit/view/id/' . $id);
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
                'auction_date' => 0,
                'medicine' => [0],
                'amount' => [''],
                'quantity' => [''],
                'total' => [0, 0],
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['member', 'medicine']);
        $this->addModuleScript('auction-chit-create');
        $dateOptions = [];
        $today = DateHelper::getDateTime();
        $dateOptions[] = 'Today - ' . $today->format(DateHelper::DATEPICKER);
        $today->modify('-1 day');
        $dateOptions[] = 'Yesterday - ' . $today->format(DateHelper::DATEPICKER);
        $this->view->setVariable('dateOptions', $dateOptions);
                
        $this->_breadcrumb('Add');
        
        return $this->getViewModel();
    }
    
    
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        
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
        $this->addModuleScript('auction-chit-view');
        $this->layout->addModal('auction-chit/view');
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
     * daily catch action
     * 
     * @return \Web\View\ViewModel
     */
    public function dailyCatchAction()
    {
        $dailyModel = new AuctionFmcDaily();
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $this->_branch->id());
        $where1 = $sql->getWhere();
        
        if (0 == $dailyModel->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chit found', 'auction-chit');
        }
        
        $minDate = $dailyModel->selectMin('auction_date', $where1);
        $minTime = DateHelper::getDateTime($minDate);
        $this->view->setVariable('min_date', $minTime->format('d/m/Y'));
        
        $filters = new FilterForm();
        $filters->addFields(['start', 'end']);
        $filters->addFilter('start', $filters->getDateFilter());
        $filters->addFilter('end', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $maxTime = DateHelper::getDateTime(DateHelper::today());
        $start = '';
        $end = '';
        if ('' != $values['start']) {
            $date1 = DateHelper::fromFormat($values['start'], DateHelper::DATEPICKER);
            if ($date1 >= $minTime && $date1 <= $maxTime) {
                $start = $date1->format(DateHelper::MYSQL_DATE);
            }
        }
        
        if ('' != $values['end']) {
            $date2 = DateHelper::fromFormat($values['end'], DateHelper::DATEPICKER);
            if ($date2 >= $minTime && $date2 <= $maxTime) {
                if ('' != $start) {
                    if ($date2 >= $date1) {
                        $end = $date2->format(DateHelper::MYSQL_DATE);
                    }
                }
            }
        }
        
        if ('' != $start && '' != $end) {
            if ($start == $end) {
                $sql->whereEqual('auction_date', $start);
            } else {
                $sql->whereBetween('auction_date', $start, $end);
            }
        } elseif ('' != $start) {
            $sql->whereGreater('auction_date', $start, true);
        } elseif ('' != $end) {
            $sql->whereLess('auction_date', $end, true);
        }
        
        $where = $sql->getWhere();
        $total = $dailyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $dailyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->addModuleScript('list');
        $this->_breadcrumb('Daily Catch');
        
        return $this->getViewModel();
    }
    
    /**
     * daily catch print action
     * 
     * @return \Web\View\ViewModel
     */
    public function dailyCatchPrintAction()
    {
        $filters = new FilterForm();
        $filters->addFields(['date']);
        $filters->numeric(['date']);
        $values = $filters->getFilteredValues();
        $valid = false;
        if ('' != $values['date']) {
            if (false !== ($date = DateHelper::fromFormat($values['date'], 'Ymd'))) {
                $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                $this->view->setVariable('auctionDate', $date->format(DateHelper::DATEPICKER));
                $valid = true;
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $dailyModel = new AuctionFmcDaily();
        $sql = $this->_model->getSql();
        $sql->whereId('fmc_id', $this->_branch->id())
            ->whereEqual('auction_date', $auctionDate);
        
        $where1 = $sql->getWhere();
        if (0 == $dailyModel->selectTotal($where1)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $dailyModel->selectRow($where1));
        $sql->clear();
        $sql->whereIn('member_id', $this->_branch->memberIds())
            ->whereEqual('auction_date', $auctionDate);
        $where = $sql->getWhere();
        $this->view->setVariable('list', $this->_model->select($where));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        $this->registerPairs(['member_code', 'member_name', 'donor']);
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * member catch action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberCatchAction()
    {
        $monthlyModel = new AuctionMonthly();
        $memberIds = $this->_branch->auctionMemberIds();
        $sql = $this->_model->getSql();
        $sql->whereIn('member_id', $memberIds);
        $where1 = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chit found', 'auction-chit');
        }
        
        $min = $this->_model->selectMin('auction_date', $where1);
        $minDate = DateHelper::getDateTime($min);
        $this->view->setVariable('min_date', '01/'. $minDate->format('m/Y'));
        
        $filters = new FilterForm();
        $filters->addFields(['date', 'member']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->numeric(['member']);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $sql->clear();
        $date = '';
        if ('' != $values['date']) {
            $date1 = DateHelper::fromFormat($values['date'], DateHelper::DATEPICKER);
            $date1->setDate($date1->format('Y'), $date1->format('n'), $date1->format('t'));
            $date = $date1->format(DateHelper::MYSQL_DATE);
            $sql->whereEqual('auction_date', $date);
        }
        
        $memberSelected = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $sql->whereId('member_id', $values['member']);
                $memberSelected = true;
            }
        }
        
        if (! $memberSelected) {
            $sql->whereIn('member_id', $memberIds);
        }
        
        $where = $sql->getWhere();
        $total = $monthlyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $monthlyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['member']);
        $this->addModuleScript('list');
        $this->_breadcrumb('Member Catch');
        
        return $this->getViewModel();
    }
    
    /**
     * member catch action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberCatchPrintAction()
    {
        $monthlyModel = new AuctionMonthly();
        $filters = new FilterForm();
        $filters->addFields(['date', 'member']);
        $filters->numeric(['date', 'member']);
        $values = $filters->getFilteredValues();
        $valid = false;
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                if ('' != $values['date']) {
                    if (false !== ($date = DateHelper::fromFormat($values['date'], 'Ymd'))) {
                        $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                        $this->view->setVariable('auctionMonth', $date->format('F Y'));
                        $valid = true;
                    }
                }
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $values['member'])
            ->whereEqual('auction_date', $auctionDate);
        $where1 = $sql->getWhere();
        if (0 == $monthlyModel->selectTotal($where1)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $monthlyModel->selectRow($where1));
        $dateTime = DateHelper::getDateTime($auctionDate);
        $dateTime->setDate($dateTime->format('Y'), $dateTime->format('n'), 1);
        $start = $dateTime->format(DateHelper::MYSQL_DATE);
        $sql->clear();
        $sql->whereId('member_id', $values['member'])
            ->whereBetween('auction_date', $start, $auctionDate);
        
        $where = $sql->getWhere();
        $this->view->setVariable('list', $this->_model->select($where, 'auction_date'));
        $this->view->setVariable('member', $this->_branch->getMemberById($values['member']));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        $this->registerPairs(['donor']);

        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * donor daily action
     * 
     * @return \Web\View\ViewModel
     */
    public function donorDailyAction()
    {
        $donorIds = $this->_branch->donorIds();
        $sql = $this->_model->getSql();
        $sql->whereIn('donor_id', $donorIds);
        $where1 = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chit found', 'auction-chit');
        }
        
        $min = $this->_model->selectMin('auction_date', $where1);
        $minTime = DateHelper::getDateTime($min);
        $this->view->setVariable('min_date', '01/'. $minTime->format('m/Y'));
        
        $dailyModel = new AuctioneerDaily();
        
        $filters = new FilterForm();
        $filters->addFields(['start', 'end', 'donor']);
        $filters->addFilter('start', $filters->getDateFilter());
        $filters->addFilter('end', $filters->getDateFilter());
        $filters->numeric(['donor']);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $maxTime = DateHelper::getDateTime(DateHelper::today());
        $start = '';
        $end = '';
        if ('' != $values['start']) {
            $date1 = DateHelper::fromFormat($values['start'], DateHelper::DATEPICKER);
            if ($date1 >= $minTime && $date1 <= $maxTime) {
                $start = $date1->format(DateHelper::MYSQL_DATE);
            }
        }
        
        if ('' != $values['end']) {
            $date2 = DateHelper::fromFormat($values['end'], DateHelper::DATEPICKER);
            if ($date2 >= $minTime && $date2 <= $maxTime) {
                if ('' != $start) {
                    if ($date2 >= $date1) {
                        $end = $date2->format(DateHelper::MYSQL_DATE);
                    }
                }
            }
        }
        
        $sql->clear();
        
        if ('' != $start && '' != $end) {
            if ($start == $end) {
                $sql->whereEqual('auction_date', $start);
            } else {
                $sql->whereBetween('auction_date', $start, $end);
            }
        } elseif ('' != $start) {
            $sql->whereGreater('auction_date', $start, true);
        } elseif ('' != $end) {
            $sql->whereLess('auction_date', $end, true);
        }
        
        $donors = $donorIds;
        if ($values['donor'] > 0) {
            if (in_array($values['donor'], $donorIds)) {
                $donors = [$values['donor']];
            }
        }
        $sql->whereIn('donor_id', $donors);
        
        $where = $sql->getWhere();
        $total = $dailyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $dailyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['donor']);
        $this->addModuleScript('list');
        $this->_breadcrumb('Auctioneer Daily');
        
        return $this->getViewModel();
    }
    
    /**
     * donor daily print action
     * 
     * @return \Web\View\ViewModel
     */
    public function donorDailyPrintAction()
    {
        $dailyModel = new AuctioneerDaily();
        $filters = new FilterForm();
        $filters->addFields(['date', 'donor']);
        $filters->numeric(['date', 'donor']);
        $values = $filters->getFilteredValues();
        $valid = false;
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                if ('' != $values['date']) {
                    if (false !== ($date = DateHelper::fromFormat($values['date'], 'Ymd'))) {
                        $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                        $valid = true;
                    }
                }
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('donor_id', $values['donor'])
            ->whereEqual('auction_date', $auctionDate);
        $where = $sql->getWhere();
        if (0 == $dailyModel->selectTotal($where)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $dailyModel->selectRow($where));
        $this->view->setVariable('auctionDate', DateHelper::datepicker($auctionDate));
        
        $this->view->setVariable('list', $this->_model->select($where));
        $this->view->setVariable('donor', $this->_branch->getMemberById($values['donor']));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        $this->registerPairs(['member_code', 'member_name']);

        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * donor monthly action
     * 
     * @return \Web\View\ViewModel
     */
    public function donorMonthlyAction()
    {
        $monthlyModel = new AuctioneerMonthly();
        $donorIds = $this->_branch->donorIds();
        $sql = $this->_model->getSql();
        $sql->whereIn('donor_id', $donorIds);
        $where1 = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where1)) {
            $this->setErrorFlashMessage('No Auction Chit found', 'auction-chit');
        }
        
        $this->_loadStartDate($where1);
        
        $filters = new FilterForm();
        $filters->addFields(['date', 'donor']);
        $filters->addFilter('date', $filters->getDateFilter());
        $filters->numeric(['donor']);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $sql->clear();
        $date = '';
        if ('' != $values['date']) {
            $date1 = DateHelper::fromFormat($values['date'], DateHelper::DATEPICKER);
            $date1->setDate($date1->format('Y'), $date1->format('n'), $date1->format('t'));
            $date = $date1->format(DateHelper::MYSQL_DATE);
            $sql->whereEqual('auction_date', $date);
        }
        
        $donorSelected = false;
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                $sql->whereId('donor_id', $values['donor']);
                $donorSelected = true;
            }
        }
        
        if (! $donorSelected) {
            $sql->whereIn('donor_id', $donorIds);
        }
        
        $where = $sql->getWhere();
        $total = $monthlyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $monthlyModel->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->registerPairs(['donor']);
        $this->addModuleScript('list');
        $this->_breadcrumb('Auctioneer Monthly');
        
        return $this->getViewModel();
    }
    
    /**
     * donor monthly print action
     * 
     * @return \Web\View\ViewModel
     */
    public function donorMonthlyPrintAction()
    {
        $monthlyModel = new AuctioneerMonthly();
        $filters = new FilterForm();
        $filters->addFields(['date', 'donor']);
        $filters->numeric(['date', 'donor']);
        $values = $filters->getFilteredValues();
        $valid = false;
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                if ('' != $values['date']) {
                    if (false !== ($date = DateHelper::fromFormat($values['date'], 'Ymd'))) {
                        $auctionDate = $date->format(DateHelper::MYSQL_DATE);
                        $this->view->setVariable('auctionMonth', $date->format('F Y'));
                        $valid = true;
                    }
                }
            }
        }
        
        if (! $valid) {
            die('No Auction Chit found');
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('donor_id', $values['donor'])
            ->whereEqual('auction_date', $auctionDate);
        $where1 = $sql->getWhere();
        if (0 == $monthlyModel->selectTotal($where1)) {
            die('No Auction Chit found');
        }
        
        $this->view->setVariable('summary', $monthlyModel->selectRow($where1));
        $dateTime = DateHelper::getDateTime($auctionDate);
        $dateTime->setDate($dateTime->format('Y'), $dateTime->format('n'), 1);
        $start = $dateTime->format(DateHelper::MYSQL_DATE);
        $sql->clear();
        $sql->whereId('donor_id', $values['donor'])
            ->whereBetween('auction_date', $start, $auctionDate);
        
        $where = $sql->getWhere();
        $this->view->setVariable('list', $this->_model->select($where, 'auction_date'));
        $this->view->setVariable('donor', $this->_branch->getMemberById($values['donor']));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        $this->registerPairs(['member_name', 'member_code']);

        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    
    private function _loadStartDate($where)
    {
        $this->_options['start_date'] = $this->_model->selectMin('auction_date', $where);
        $minDate = DateHelper::getDateTime($this->_options['start_date']);
        $this->_options['min_date'] = '01/'. $minDate->format('m/Y');
        $this->view->setVariable('min_date', $this->_options['min_date']);
    }
    
    /**
     * load list
     */
    private function _list()
    {
        $filters = new FilterForm();
        $fields = ['date', 'donor', 'member'];
        $filters->addFields($fields);
        $filters->numeric(['donor', 'member']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        
        $sql = $this->_model->getSql();
        $members = $this->_branch->memberIds();
        if ($values['member'] > 0) {
            if ($this->_branch->isMember($values['member'])) {
                $members = [$values['member']];
            }
        }
        $sql->whereIn('member_id', $members);
        
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                $sql->whereId('donor_id', $values['donor']);
            }
        }
        
        if ('' != $values['date']) {
            $sql->whereEqual('auction_date', DateHelper::fromDatepicker($values['date']));
        }
        $where = $sql->getWhere();

        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'auction_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
    }
    
    /**
     * get member id
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
                $valid = $this->_branch->isFmcMember($memberId);
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
    
}
