<?php
/**
 * Branch AuctionPayment Controller
 * 
 * @category Branch
 * @package  Branch\Controller
 */

namespace Branch\Controller;

use Branch\BranchController;
use Branch\Constant\{Tab, FlashMessage};
use Branch\Form\AuctionPaymentForm;

use Model\{AuctioneerPayment, AuctioneerAccountDaily, AuctioneerAccountMonthly};
use Model\AuctioneerMonthly;

use Web\Utility\DateHelper;
use Web\Utility\CsvWriter;

use Shared\Form\FilterForm;
use Shared\Form\AjaxForm;
use Shared\Utility\AccountSettings;

class AuctionPaymentController extends BranchController
{  
    /**
     *
     * @var \Model\AuctioneerPayment 
     */
    private $_model;
    
    /**
     *
     * @var \Model\AuctioneerAccountDaily 
     */
    private $_donorAccountDailyModel;
    
    /**
     *
     * @var \Model\AuctioneerAccountMonthly
     */
    private $_donorAccountMonthlyModel;
    
    /**
     *
     * @var \Model\AuctioneerMonthly
     */
    private $_donorMonthlyModel;
    
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
        $this->_model = new AuctioneerPayment();
        $this->currentTab(Tab::AUCTION_PAYMENT);
        $this->setFlashMessageTitle('Auction Payment');
        $this->_sql = $this->_model->getSql();
        $this->_donorAccountDailyModel = new AuctioneerAccountDaily();
        $this->_donorAccountMonthlyModel = new AuctioneerAccountMonthly();
        $this->_donorMonthlyModel = new AuctioneerMonthly();
        $this->_fmc_id = $this->_branch->id();
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
        $this->addModuleScript('auction-payment-list');
        $this->registerPairs(['donor']);
        
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
        $fields = ['donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_sql->whereEqual('payment_date', DateHelper::today());
        $this->_applyFilters($values);
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'id DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->_breadcrumb('Today');
        $this->registerPairs(['donor']);
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $form = new AuctionPaymentForm($this->_fmc_id);
        $options = new AccountSettings($this->_fmc_id);
        $options->load();
        $optionReceiptNo = $options->receiptNo();
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
        $options->load();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('auction-payment/view/id/' . $id);
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
                'payment_date' => DateHelper::datepicker(),
                'donor_id' => 0,
                'amount_paid' => 0,
                'notes' => '',
                'receipt_number' => $receiptNo,
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $balance = $this->_loadPaymentBalance($form->getValue('donor_id'), $form->getValue('payment_date'));
        $this->view->setVariable('balance', $balance);
        $this->registerPairs(['donor'], true);
        $this->addModuleScript('auction-payment-create');
        $this->_breadcrumb('New');
        
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
        $payment = $this->_model->find($id);
        $this->_sql->whereId('donor_id', $payment->donor_id)
                   ->whereEqual('payment_date', $payment->payment_date);
        $dailyAccount = $this->_donorAccountDailyModel->selectRow($this->_sql->getWhere());
        $this->view->setVariable('payment', $payment);
        $this->view->setVariable('dailyAccount', $dailyAccount);
        $this->view->setVariable('donor', $this->_branch->getMemberById($payment->donor_id));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * print receipt action
     * 
     * @return \Web\View\ViewModel
     */
    public function printReceiptAction()
    {
        $id = $this->_getId();$payment = $this->_model->find($id);
        $this->view->setVariable('payment', $payment);
        $this->view->setVariable('donor', $this->_branch->getMemberById($payment->donor_id));
        $this->view->setVariable('fmc', $this->_branch->fmc());
        
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
        $this->_sql->whereIn('donor_id', $this->_branch->donorIds());
        $where = $this->_sql->getWhere();
        if ($this->_donorAccountDailyModel->selectTotal($where) > 0) {
            $query = 'SELECT `donor_id`, MAX(`payment_date`) FROM '
                   . $this->_donorAccountDailyModel->getTableQuoted() 
                   . ' WHERE ' . $where . ' GROUP BY `donor_id`';
            $latest = $this->_model->queryPairs($query);

            $list = [];
            foreach ($latest as $donorId => $date) {
                $this->_sql->clear();
                $this->_sql->whereId('donor_id', $donorId)
                           ->whereEqual('payment_date', $date);
                $list[] = $this->_donorAccountDailyModel->selectRow($this->_sql->getWhere());
            }

            $this->view->setVariable('list', $list);
            $this->addModuleScript('list');
        }
        
        $this->_breadcrumb('Account Balance - Current');
        $this->registerPairs(['donor']);
        
        return $this->getViewModel();
    }
    
    /**
     * closing balance export action
     * 
     * @return \Web\View\ViewModel
     */
    public function closingBalanceExportAction()
    {
        $this->_sql->whereIn('donor_id', $this->_branch->donorIds());
        $where = $this->_sql->getWhere();
        if (0 == $this->_donorAccountDailyModel->selectTotal($where)) {
            $this->setErrorFlashMessage('No records found', 'auction-payment/closing-balance');
        }
        
        $query = 'SELECT `donor_id`, MAX(`payment_date`) FROM '
               . $this->_donorAccountDailyModel->getTableQuoted() 
               . ' WHERE ' . $where . ' GROUP BY `donor_id`';
        $latest = $this->_model->queryPairs($query);
        $fmc = $this->_branch->fmc();
        $title = [$fmc->name, 'Closing Balance', DateHelper::format('d-m-Y')];
        $csvWriter = new CsvWriter(implode(' ', $title));
        $header = [
            'Sl. No.', 'Code', 'Name', 'Date', 'Opening Balance', 'Catch',
            'Advance', 'Balance', 'Payment', 'Closing Balance',
        ];
        $csvWriter->row($header);
        
        $this->registerPairs(['donor_code', 'donor_name']);
        $total = 0;
        foreach ($latest as $donorId => $date) {
            $this->_sql->clear();
            $this->_sql->whereId('donor_id', $donorId)
                       ->whereEqual('payment_date', $date);
            $entry = $this->_donorAccountDailyModel->selectRow($this->_sql->getWhere());
            $row = [
                $this->view->counter(),
                $this->view->lookup('donor_code', $donorId),
                $this->view->lookup('donor_name', $donorId),
                $this->view->datepicker($date),
                $entry->opening_balance,
                $entry->auction_amount,
                $entry->advance,
                $entry->balance,
                $entry->amount_paid,
                $entry->closing_balance,
            ];
            $total += $entry->closing_balance;
            $csvWriter->row($row);
        }
        
        $blank = array_fill(0, 10, '');
        $blank[1] = 'Total';
        $blank[9] = $total;
        $csvWriter->row($blank);
        $csvWriter->finish();
        
        return $this->getViewModel();
    }
    
    /**
     * closing balance print action
     * 
     * @return \Web\View\ViewModel
     */
    public function closingBalancePrintAction()
    {
        $this->_sql->whereIn('donor_id', $this->_branch->donorIds());
        $where = $this->_sql->getWhere();
        if (0 == $this->_donorAccountDailyModel->selectTotal($where)) {
            die('No records found');
        }
        
        $query = 'SELECT `donor_id`, MAX(`payment_date`) FROM '
               . $this->_donorAccountDailyModel->getTableQuoted() 
               . ' WHERE ' . $where . ' GROUP BY `donor_id`';
        $latest = $this->_model->queryPairs($query);

        $list = [];
        foreach ($latest as $donorId => $date) {
            $this->_sql->clear();
            $this->_sql->whereId('donor_id', $donorId)
                       ->whereEqual('payment_date', $date);
            $list[] = $this->_donorAccountDailyModel->selectRow($this->_sql->getWhere());
        }

        $this->view->setVariable('list', $list);
        $this->registerPairs(['donor_code', 'donor_name']);
        $this->view->setVariable('fmc', $this->_branch->fmc());
        
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
    /**
     * daily closing balance action
     * 
     * @return \Web\View\ViewModel
     */
    public function dailyClosingBalanceAction()
    {
        $filters = new FilterForm();
        $fields = ['date', 'donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values);
        $where = $this->_sql->getWhere();
        $total = $this->_donorAccountDailyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_donorAccountDailyModel->select($where, 'payment_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
        $this->_breadcrumb('Daily Closing Balance');
        $this->addModuleScript('list');
        $this->registerPairs(['donor']);
        
        return $this->getViewModel();
    }
    
    /**
     * monthly closing balance action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyClosingBalanceAction()
    {
        $filters = new FilterForm();
        $fields = ['month', 'donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $filters->addFilter('month', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values);
        if ('' != $values['month']) {
            $date = DateHelper::fromFormat($values['month'], DateHelper::DATEPICKER);
            $this->_sql->whereId('payment_year', $date->format('Y'))
                       ->whereId('payment_month', $date->format('n'));
        }
        
        $where = $this->_sql->getWhere();
        $total = $this->_donorAccountMonthlyModel->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $list = $this->_donorAccountMonthlyModel->select($where, 'payment_year DESC, payment_month DESC', $paging->sqlLimit);
            $this->view->setVariable('list', $list);
            $auctionChits = [];
            $donorIds = [];
            $dates = ['min' => '', 'max' => ''];
            $time = DateHelper::getDateTime();
            foreach ($list as $item) {
                if (! in_array($item->donor_id, $donorIds)) {
                    $donorIds[] = $item->donor_id;
                }
                
                $time->setDate($item->payment_year, $item->payment_month, 10);
                $date = $time->format('Ym');
                if ('' == $dates['min']) {
                    $dates['min'] = $date;
                } else {
                    $dates['min'] = min($date, $dates['min']);
                }
                if ('' == $dates['max']) {
                    $dates['max'] = $date;
                } else {
                    $dates['max'] = max($date, $dates['max']);
                }
            }
            
            $this->_sql->clear();
            $this->_sql->whereIn('donor_id', $donorIds);
            if ($dates['min'] == $dates['max']) {
                $selected = DateHelper::fromFormat($dates['min'], 'Ym');
                $selected->setDate($selected->format('Y'), $selected->format('n'), $selected->format('t'));
                $this->_sql->whereEqual('auction_date', $selected->format(DateHelper::MYSQL_DATE));
            } else {
                $from = DateHelper::fromFormat($dates['min'], 'Ym');
                $from->setDate($from->format('Y'), $from->format('n'), $from->format('t'));
                $to = DateHelper::fromFormat($dates['max'], 'Ym');
                $to->setDate($to->format('Y'), $to->format('n'), $to->format('t'));
                $this->_sql->whereBetween('auction_date', $from->format(DateHelper::MYSQL_DATE), $to->format(DateHelper::MYSQL_DATE));
            }
            
            $where2 = $this->_sql->getWhere();
            if ($this->_donorMonthlyModel->selectTotal($where2) > 0) {
                $monthly = $this->_donorMonthlyModel->select($where2);
                foreach ($monthly as $entry) {
                    if (! isset($auctionChits[$entry->donor_id])) {
                        $auctionChits[$entry->donor_id] = [];
                    }
                    
                    $auctionMonth = DateHelper::getDateTime($entry->auction_date);
                    $year = $auctionMonth->format('Y');
                    if (! isset($auctionChits[$entry->donor_id][$year])) {
                        $auctionChits[$entry->donor_id][$year] = [];
                    }
                    $month = $auctionMonth->format('n');
                    $auctionChits[$entry->donor_id][$year][$month] = $entry->entries;
                }
            }
            
            $this->view->setVariable('auctionChits', $auctionChits);
        }
        
        $this->view->setVariable('total', $total);
        $this->_breadcrumb('Monthly Closing Balance');
        $this->addModuleScript('list');
        $this->registerPairs(['donor']);
        
        return $this->getViewModel();
    }
    
    /**
     * monthly closing balance export action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyClosingBalanceExportAction()
    {
        $filters = new FilterForm();
        $fields = ['month', 'donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $filters->addFilter('month', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values);
        $titleMonth = '';
        if ('' != $values['month']) {
            $date = DateHelper::fromFormat($values['month'], DateHelper::DATEPICKER);
            $this->_sql->whereId('payment_year', $date->format('Y'))
                       ->whereId('payment_month', $date->format('n'));
            $titleMonth = $date->format('F Y');
        }
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_donorAccountMonthlyModel->selectTotal($where)) {
            $this->setErrorFlashMessage('No records found', 'auction-payment/monthly-closing-balance');
        }
        
        $list = $this->_donorAccountMonthlyModel->select($where, 'payment_year DESC, payment_month DESC');
        $donorIds = $this->_donorAccountMonthlyModel->selectColumns('donor_id', $where);
        $this->_sql->clear();
        $this->_sql->whereIn('donor_id', $donorIds);
        $where2 = $this->_sql->getWhere();
        $auctionChits = [];
        if ($this->_donorMonthlyModel->selectTotal($where2) > 0) {
            $monthly = $this->_donorMonthlyModel->select($where2);
            foreach ($monthly as $entry) {
                if (! isset($auctionChits[$entry->donor_id])) {
                    $auctionChits[$entry->donor_id] = [];
                }

                $auctionMonth = DateHelper::getDateTime($entry->auction_date);
                $year = $auctionMonth->format('Y');
                if (! isset($auctionChits[$entry->donor_id][$year])) {
                    $auctionChits[$entry->donor_id][$year] = [];
                }
                $month = $auctionMonth->format('n');
                $auctionChits[$entry->donor_id][$year][$month] = $entry->entries;
            }
        }
        
        $this->registerPairs(['donor_code', 'donor_name']);
        $fmc = $this->_branch->fmc();
        $title = [$fmc->name, 'Monthly Closing Balance'];
        if ('' != $titleMonth) {
            $title[] = $titleMonth;
        }
        
        if ($values['donor'] > 0) {
            $donor = $this->_branch->getMemberById($values['donor']);
            $title[] = $donor->code;
            $title[] = $donor->name;
        }
        
        $csvWriter = new CsvWriter(implode(' ', $title));
        $header = [
            'Sl. No.', 'Month', 'Code', 'Name', 'Opening Balance', 'Auction Chits',
            'Catch', 'Advance', 'Balance', 'Payment', 'Closing Balance',
        ];
        
        $csvWriter->row($header);
        
        $this->registerPairs(['donor_code', 'donor_name']);
        $blank = array_fill(0, 11, '');
        $time = DateHelper::getDateTime();
        
        $summary = [
            'entries' => 0,
            'catch' => 0,
            'advance' => 0,
            'balance' => 0,
            'payment' => 0,
        ];
        
        $map = [
            'entries' => 5,
            'catch' => 6,
            'advance' => 7,
            'balance' => 8,
            'payment' => 9,
        ];
        
        foreach ($list as $item) {
            $row = $blank;
            $row[0] = $this->view->counter();
            $time->setDate($item->payment_year, $item->payment_month, 10);
            $row[1] = $time->format('F Y');
            $row[2] = $this->view->lookup('donor_code', $item->donor_id);
            $row[3] = $this->view->lookup('donor_name', $item->donor_id);
            $row[4] = $item->opening_balance;
            if (isset($auctionChits[$item->donor_id][$item->payment_year][$item->payment_month])) {
                $row[5] = $auctionChits[$item->donor_id][$item->payment_year][$item->payment_month];
                $summary['payment'] += $item->amount_paid;
            } else {
                $row[5] = 0;
            }
            $row[6] = $item->auction_amount;
            $row[7] = $item->advance;
            $row[8] = $item->balance;
            $row[9] = $item->amount_paid;
            $row[10] = $item->closing_balance;
            $csvWriter->row($row);
            foreach ($map as $column => $index) {
                $summary[$column] += $row[$index];
            }
        }
        
        $blank[1] = 'Total';
        foreach ($map as $column => $index) {
            $blank[$index] = $summary[$column];
        }
        $csvWriter->row($blank);
        
        $csvWriter->finish();
        
        return $this->getViewModel();
    }
    
    /**
     * monthly closing balance print action
     * 
     * @return \Web\View\ViewModel
     */
    public function monthlyClosingBalancePrintAction()
    {
        $filters = new FilterForm();
        $fields = ['month', 'donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $filters->addFilter('month', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values);
        $titleMonth = '';
        if ('' != $values['month']) {
            $date = DateHelper::fromFormat($values['month'], DateHelper::DATEPICKER);
            $this->_sql->whereId('payment_year', $date->format('Y'))
                       ->whereId('payment_month', $date->format('n'));
            $titleMonth = $date->format('F Y');
        }
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_donorAccountMonthlyModel->selectTotal($where)) {
            die('No records found');
        }
        
        $this->view->setVariable('list', $this->_donorAccountMonthlyModel->select($where, 'payment_year DESC, payment_month DESC'));
        $donorIds = $this->_donorAccountMonthlyModel->selectColumns('donor_id', $where);
        $this->_sql->clear();
        $this->_sql->whereIn('donor_id', $donorIds);
        $where2 = $this->_sql->getWhere();
        $auctionChits = [];
        if ($this->_donorMonthlyModel->selectTotal($where2) > 0) {
            $monthly = $this->_donorMonthlyModel->select($where2);
            foreach ($monthly as $entry) {
                if (! isset($auctionChits[$entry->donor_id])) {
                    $auctionChits[$entry->donor_id] = [];
                }

                $auctionMonth = DateHelper::getDateTime($entry->auction_date);
                $year = $auctionMonth->format('Y');
                if (! isset($auctionChits[$entry->donor_id][$year])) {
                    $auctionChits[$entry->donor_id][$year] = [];
                }
                $month = $auctionMonth->format('n');
                $auctionChits[$entry->donor_id][$year][$month] = $entry->entries;
            }
        }
        
        $this->registerPairs(['donor_code', 'donor_name']);
        $this->view->setVariable('auctionChits', $auctionChits);
        $this->view->setVariable('month', $titleMonth);
        $this->view->setVariable('fmc', $this->_branch->fmc());
        
        
        if ($values['donor'] > 0) {
            $this->view->setVariable('donor', $this->_branch->getMemberById($values['donor']));
        }
        
        $this->layout->setLayout('');
        
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
        $form->addField('payment_date');
        if ($form->isPost()) {
            $values = $form->run();
            $balance = $this->_loadPaymentBalance($values['id'], $values['payment_date']);
            $response->addData($balance, 'result')
                     ->valid();
        }
        
        return $this->getViewModel();
    }
    
    
    /**
     * load list
     */
    private function _list()
    {
        $filters = new FilterForm();
        $fields = ['date', 'donor'];
        $filters->addFields($fields);
        $filters->numeric(['donor']);
        $filters->addFilter('date', $filters->getDateFilter());
        $values = $filters->getFilteredValues();
        $this->view->setVariable('filters', $filters);
        $this->_applyFilters($values);
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $this->view->setVariable('list', $this->_model->select($where, 'payment_date DESC', $paging->sqlLimit));
        }
        
        $this->view->setVariable('total', $total);
    }
    
    /**
     * apply filters
     * 
     * @param array $values
     */
    private function _applyFilters($values)
    {
        $donorFilter = true;
        if (isset($values['date'])) {
            if ('' != $values['date']) {
                $this->_sql->whereEqual('payment_date', DateHelper::fromDatepicker($values['date']));
            }
        }
        
        if ($values['donor'] > 0) {
            if ($this->_branch->isAuctioneer($values['donor'])) {
                $this->_sql->whereId('donor_id', $values['donor']);
                $donorFilter = false;
            }
        }
        
        if ($donorFilter) {
            $this->_sql->whereIn('donor_id', $this->_branch->donorIds());
        }
    }
    
    /**
     * get auction payment id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $donorId = $this->_model->selectColumnById('donor_id', $id);
                $valid = $this->_branch->isAuctioneer($donorId);
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'auction-payment');
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
        $this->setHeadTitle('Auction Payments - ' . $page);
        $this->setPageHeading('Auction Payments');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Auction Payments', $this->view->actionUrl('auction-payment'));
        $breadrumb->add($page);
    }
    
    /**
     * load payment balance
     * 
     * @param int $donorId
     * @param string $date
     * @return array
     */
    private function _loadPaymentBalance($donorId, $date)
    {
        $balance = [
            'payment_date' => $date,
            'donor' => '',
            'opening' => '',
            'amount' => '',
            'advance' => '',
            'payment' => '',
            'closing' => '',
        ];
        
        if ($donorId > 0) {
            $member = $this->_branch->getMemberById($donorId);
            $balance['donor'] = $member->name;
            $this->_sql->clear();
            $this->_sql->whereId('donor_id', $donorId)
                       ->whereLess('payment_date', DateHelper::fromDatepicker($date), true);
            $where = $this->_sql->getWhere();
            if ($this->_donorAccountDailyModel->selectTotal($where) > 0) {
                $last = $this->_donorAccountDailyModel->selectRow($where, 'payment_date DESC');
                $balance['payment_date'] = DateHelper::datepicker($last->payment_date);
                $balance['opening'] = $last->opening_balance;
                $balance['amount'] = $last->auction_amount;
                $balance['advance'] = $last->advance;
                $balance['payment'] = $last->amount_paid;
                $balance['closing'] = $last->closing_balance;
            }
        }
        
        return $balance;
    }
    
}
