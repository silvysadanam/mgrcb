<?php
/**
 * Application Refinance Controller
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\FilterForm;
use Application\Utility\LoanHelper;

use Model\{Loan, Member, Fmc, LoanType, LoanSource, LoanSummary};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;


class RefinanceController extends ApplicationController
{
    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_memberModel;
    
    /**
     *
     * @var \Model\LoanType 
     */
    private $_loanTypeModel;
    
    /**
     *
     * @var \Model\LoanSource 
     */
    private $_loanSourceModel;
    
    /**
     * 
     * @var int
     */
    private $_filteredFmcId = 0;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->_loanTypeModel = new LoanType();
        $this->_loanSourceModel = new LoanSource();
        $this->currentTab(Tab::INTERFACE_INPUTS);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $loanSummaryModel = new LoanSummary();
        $where = $this->_getFilters();
        $query = ' FROM `' . $this->_model->getTable() . '` `l` '
               . 'INNER JOIN `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id` '
               . 'INNER JOIN `' . $loanSummaryModel->getTable() . '` `s` ON `s`.`loan_id` = `l`.`id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) ' . $query;

        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $select = 'SELECT `l`.`id`, `l`.`member_id`, `l`.`loan_type_id`, '
                    . '`l`.`loan_source_id`, `l`.`loan_amount`, `l`.`loan_date`, '
                    . '`l`.`account_number`, `l`.`tenure`, `m`.`name`, `m`.`code`, '
                    . '`m`.`fmc_id`, `s`.`balance_actual_principal_interest_outstanding` AS `outstanding`, '
                    . '`s`.`ageing`'
                    . $query . ' ORDER BY `m`.`fmc_id`, `m`.`name`';
            $this->view->setVariable('list', $this->_model->queryRows($select));
        }
        
        $this->view->setVariable('total', $total);
        
        $this->view->setVariable('branchManager', $this->isManager());
        $pairModel = new PairModel();
        LookupStore::register('type', $pairModel->loanType());
        LookupStore::register('source', $pairModel->loanSource());
        if ($this->isManager()) {
            LookupStore::register('fmc', $this->_branch->option());
        } else {
            LookupStore::register('fmc', $pairModel->fmcCode());
        }
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        $this->addModuleScript('refinance-list');
        $this->setHeadTitle('INPUTS - REFINANCE DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading(' REFINANCE DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add(' REFINANCE DCB');
        
        return $this->getViewModel();
    }
    
    private function _getFilters()
    {
        $filters = new FilterForm();
        $fields = ['fmc', 'code', 'type', 'source', 'member'];
        $filters->addFields($fields);
        $filters->numeric(['fmc', 'type', 'source']);
        $values = $filters->load();
        $sql = $this->_model->getSql();
        $sql->whereId('l.loan_status_id', LoanHelper::STATUS_DELINQUENT)
            ->whereOne('l.closed');
        $memberId = 0;
        $member = null;
        if ('' != $values['code']) {
            if ($this->_memberModel->checkByColumn('code', $values['code'])) {
                $member = $this->_memberModel->selectByColumn('code', $values['code']);
            }
        }
        
        if ($this->isManager()) {
            if (! empty($member)) {
                if ($this->_branch->id() == $member->fmc_id) {
                    $memberId = $member->id;
                }
            }
            
        } elseif (! empty($member)) {
            if ($values['fmc'] > 0) {
                if ($this->_fmcModel->hasId($values['fmc'])) {
                    if ($member->fmc_id == $values['fmc']) {
                        
                    }
                }
            } else {
                $memberId = $member->id;
            }
        }
        
        if ($memberId > 0) {
            $sql->whereId('l.member_id', $memberId);
        } else {
            if ($this->isManager()) {
                $sql->whereId('m.fmc_id', $this->_branch->id());
            } elseif ($values['fmc'] > 0) {
                if ($this->_fmcModel->hasId($values['fmc'])) {
                    $sql->whereId('m.fmc_id', $values['fmc']);
                    $this->_filteredFmcId = $values['fmc'];
                }
            }
        }
        
        if ($values['type'] > 0) {
            if ($this->_loanTypeModel->hasId($values['type'])) {
                $sql->whereId('l.loan_type_id', $values['type']);
            }
        }
        
        if ($values['source'] > 0) {
            if ($this->_loanSourceModel->hasId($values['source'])) {
                $sql->whereId('l.loan_source_id', $values['source']);
            }
        }
        
        $this->view->setVariable('filters', $filters);
        
        return $sql->getWhere();
    }
   
}
