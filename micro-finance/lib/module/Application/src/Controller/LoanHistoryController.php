<?php
/**
 * Application LoanHistory Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\{AjaxForm, FilterForm};
use Application\Utility\{LoanHelper, LoanStore};

use Model\{Member, Fmc, Loan};

use Web\View\Support\LookupStore;

use Shared\Model\PairModel;


class LoanHistoryController extends ApplicationController
{
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    /**
     *
     * @var \Model\Loan 
     */
    private $_loanModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Member();
        $this->_fmcModel = new Fmc();
        $this->_loanModel = new Loan();
        $this->currentTab(Tab::INTERFACE_DCB);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $filters = new FilterForm();
        $filters->addField('fmc');
        $filters->numeric(['fmc']);
        $values = $filters->getFilteredValues();
        $sql = $this->_model->getSql();

        if ($this->isManager()) {
            $sql->whereId('m.fmc_id', $this->_branch->id());
        } elseif ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $sql->whereId('m.fmc_id', $values['fmc']);
            }
        }
        
        $where = $sql->getWhere();
        $from =  ' FROM ' . $this->_model->getTableQuoted() . ' `m` '
              . 'INNER JOIN ' . $this->_loanModel->getTableQuoted() . ' `l` '
              . 'ON `l`.`member_id` = `m`.`id`';
        if (! empty($where)) {
            $from .= ' WHERE ' . $where;
        }
        $from .= ' GROUP BY `l`.`member_id`';
        $count = 'SELECT COUNT(`m`.`id`) ' . $from;
        $total = $this->_model->queryTotal($count);
        if ($total > 0) {
            $select = 'SELECT `m`.`id`, `m`.`code`, `m`.`name`, `m`.`fmc_id`, '
                    . 'COUNT(`l`.`id`) AS `loans`' . $from
                    . 'ORDER BY `m`.`name`';
            $this->view->setVariable('list', $this->_model->queryRows($select));
        }
        $this->view->setVariable('total', $total);
        $this->view->setVariable('filters', $filters);
        $this->_loadFmcOptions();
        $this->layout->addModal('loan-history/summary');
        
        $this->addDatatableScripts();
        $this->addModuleScript('loan-history-list');
        $this->_breadcrumb('Members');
        
        return $this->getViewModel();
    }
    
    /**
     * view action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewAction()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            if ($this->_model->hasId($id)) {
                $member = $this->_model->find($id);
                if ($this->isManager()) {
                    $valid = $this->_branch->id() == $member->fmc_id;
                } else {
                    $valid = true;
                }
            }
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'loan-history');
        }
        
        $this->view->setVariable('member', $member);
        $this->view->setVariable('fmc', $this->_fmcModel->find($member->fmc_id));
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $id);
        $where = $sql->getWhere();
        $total = $this->_loanModel->selectTotal($where);
        $this->view->setVariable('total', $total);
        $this->_loadLoans($total, $id);
        
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * summary action
     * 
     * @return \Web\View\ViewModel
     */
    public function summaryAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['id'] > 0) {
                $id = $values['id'];
                if ($this->_model->hasId($id)) {
                    $member = $this->_model->find($id);
                    $result = [
                        'total' => 0,
                        'active' => 0,
                        'closed' => 0,
                        'preclosed' => 0,
                        'delinquent' => 0,
                        'refinanced' => 0,
                        'rescheduled' => 0,
                        'written_off' => 0,
                    ];
                    
                    $sql = $this->_model->getSql();
                    $sql->whereId('member_id', $id);
                    $whereMember = $sql->getWhere();
                    $result['total'] = $this->_loanModel->selectTotal($whereMember);
                    if ($result['total'] > 0) {
                        $sql->clear();
                        $sql->where($whereMember)
                            ->whereZero('closed');
                        $result['active'] = $this->_loanModel->selectTotal($sql->getWhere());
                        $sql->clear();
                        
                        $sql->where($whereMember)
                            ->whereOne('closed')
                            ->whereZero('loan_status_id');
                        $result['closed'] = $this->_loanModel->selectTotal($sql->getWhere());
                        
                        $status = [
                            'preclosed' => LoanHelper::STATUS_PRECLOSED,
                            'delinquent' => LoanHelper::STATUS_DELINQUENT,
                            'refinanced' => LoanHelper::STATUS_REFINANCED,
                            'rescheduled' => LoanHelper::STATUS_RESCHEDULED,
                            'written_off' => LoanHelper::STATUS_WRITTEN_OFF,
                        ];
                        
                        foreach ($status as $key => $loanStatus) {
                            $sql->clear();
                            $sql->where($whereMember)
                                ->whereOne('closed')
                                ->whereId('loan_status_id', $loanStatus);
                            $result[$key] = $this->_loanModel->selectTotal($sql->getWhere());
                        }
                    }
                    
                    $response->addData($result, 'result')
                             ->addData($member, 'member')
                             ->addData($this->_fmcModel->selectColumnById('name', $member->fmc_id), 'fmc')
                             ->valid();
                    $message = 'success';
                }
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    
    private function _loadLoans($total, $memberId)
    {
        $info = [
            'active' => 0,
            'closed' => 0,
            'delinquent' => 0,
            'refinanced' => 0,
            'rescheduled' => 0,
            'written_off' => 0,
            'amount' => 0,
        ];
        
        if (0 == $total) {
            $this->view->setVariable('info', $info);
            return;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $memberId);
        $where = $sql->getWhere();
        $loanIds = $this->_loanModel->selectIds($where, 'loan_date DESC');
        
        $sql->clear();
        $sql->whereIn('id', $loanIds);
        $whereIds = $sql->getWhere();
        $info['amount'] = $this->_loanModel->selectSum('loan_amount', $whereIds);
        
        $sql->whereZero('closed'); 
        $info['active'] = $this->_loanModel->selectTotal($sql->getWhere());
        $sql->clear();
        $sql->where($whereIds)
            ->whereOne('closed')
            ->whereIn('loan_status_id', [0, LoanHelper::STATUS_PRECLOSED])
            ->whereGlueAnd();
        $info['closed'] = $this->_loanModel->selectTotal($sql->getWhere());
        
        $statusOptions = [
            'delinquent' => LoanHelper::STATUS_DELINQUENT,
            'refinanced' => LoanHelper::STATUS_REFINANCED,
            'rescheduled' => LoanHelper::STATUS_RESCHEDULED,
            'written_off' => LoanHelper::STATUS_WRITTEN_OFF,
        ];
        
        foreach ($statusOptions as $key => $statusId) {
            $sql->clear();
            $sql->where($whereIds)
                ->whereOne('closed')
                ->whereId('loan_status_id', $statusId);
            $info[$key] = $this->_loanModel->selectTotal($sql->getWhere());
        }
        
        $loans = [];
        foreach ($loanIds as $id) {
            $loans[] = new LoanStore($id);
        }
        
        $this->view->setVariable('info', $info);
        $this->view->setVariable('loans', $loans);
    }
    
    /**
     * load FMC options
     */
    private function _loadFmcOptions()
    {
        if ($this->isManager()) {
            LookupStore::register('fmc', $this->_branch->option());
        } else {
            $this->registerPairs(['fmc']);
        }
    }
    
    private function _loadLoanLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('type', $pairModel->loanType());
        LookupStore::register('source', $pairModel->loanSource());
        LookupStore::register('status', $pairModel->loanStatus());
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('DCB Loan History - ' . $page);
        $this->setPageHeading('DCB');
        $this->setPageSubheading('Loan History');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Loan History', $this->view->actionUrl('loan_history'));
        $breadrumb->add($page);
    }
    
}
