<?php
/**
 * Application FillBacklog Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\{FilterForm, FillBacklogForm};
use Application\Utility\{LoanHelper, LoanStore};
use Application\Helper\TimelineLogger;

use Model\{Loan, Member, Fmc, LoanType, LoanSource, Schedule};


class FillBacklogController extends ApplicationController
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
     * @var \Application\Form\FilterForm
     */
    private $_filters;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_filterValues = [];
    
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
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $where = $this->_getFilter();
        $scheduleModel = new Schedule();
        $query = ' FROM `' . $scheduleModel->getTable() . '` `s` '
               . 'INNER JOIN `' . $this->_model->getTable() . '` `l` ON `l`.`id` = `s`.`loan_id` '
               . 'INNER JOIN `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $queryTotal = 'SELECT COUNT(`s`.`id`) ' . $query;
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $select = 'SELECT DISTINCT `s`.`loan_id`, `l`.`member_id`, '
                    . '`l`.`loan_type_id`, `l`.`loan_source_id`, `l`.`loan_amount`, '
                    . '`l`.`account_number`, `l`.`loan_date`, '
                    . '`m`.`name`, `m`.`code`, `m`.`fmc_id`'
                    . $query . ' ORDER BY `m`.`name`';
            $list = $this->_model->queryRows($select);
            $this->view->setVariable('list', $list);
            $total = count($list);
        }
        
        $this->view->setVariable('total', $total);
        
        $this->view->setVariable('isManager', $this->isManager());
        $this->registerPairs();
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        //$this->addModuleScript('fill-backlog-list');
        if ($this->isManager()) {
            $this->addModuleScript('dcb-filter-manager');
        } else {
            $this->addModuleScript('dcb-filter');
        }
        
        $this->setHeadTitle('INPUTS - FILL BACKLOG');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('FILL BACKLOG');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('FILL BACKLOG');
        
        return $this->getViewModel();
    }
    
    /**
     * entry action
     * 
     * @return \Web\View\ViewModel
     */
    public function entryAction()
    {
        $valid = false;
        $id = $this->getParamId();
        $invalidStatus = false;
        $skipCurrenIntallment = 1;
        if ($id > 0){
            $loan = $this->_model->find($id);
            if($loan->approval_status == 'Pending')
            {
                $invalidStatus = true;
                $skipCurrenIntallment = 0;
            } 
        }
        if ($id > 0 && $skipCurrenIntallment) {
            if ($this->_model->hasId($id)) {
                $loan = $this->_model->find($id);
                if (0 == $loan->closed) {
                    $sql = $this->_model->getSql();
                    $sql->whereLess('instalment_date', LoanHelper::reportingDate())
                        ->whereZero('has_dcb_entry')
                        ->whereId('loan_id', $id);
                    $scheduleModel = new Schedule();
                    if ($scheduleModel->selectTotal($sql->getWhere()) > 0) {
                        if ($this->isManager()) {
                            $valid = $this->_branch->id() == $this->_memberModel->selectColumnById('fmc_id', $loan->member_id);
                        } else {
                            $valid = true;
                        }
                    }
                }
            }
        }
        if($invalidStatus){
             $this->setErrorFlashMessage('Approval Pending', 'modify-dcb/view/id/' . $id);
        }
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'fill-backlog');
        }
        
        $store = new LoanStore($id);
        $backlog = $store->getBacklog();
        
        $form = new FillBacklogForm();
        if ($form->isPost()) {
            if ($form->isValid($backlog)) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    if ($store->closed()) {
                        $this->_model->updateById(['closed' => 0], $id);
                    }
                    $success = $form->fill($id, $this->getUserId());
                    if (! $success) {
                        $this->_model->getAdapter()->rollBack();
                        $form->addError('Repayment exceeds total outstanding');
                    } else {
                        $logger = new TimelineLogger($this->getUserId());
                        $logger->id($id)
                               ->save('Backlog', 'Backlog filled');
                        $this->_model->getAdapter()->commit();
                        $this->setLinkId($id);
                        $this->setFlashMessage('DCB Updated');
                        $this->redirect('modify-dcb/view/id/' . $id);
                    }
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
                'repayment' => [],
                'catch'  => [],
                'last' => 0,
            ];
            
            $last = 0;
            foreach ($backlog as $item) {
                $populate['repayment'][$item->id] = '';
                $populate['catch'][$item->id] = '';
                if ($item->instalment < $loan->tenure) {
                    $last = $item->id;
                }
            }
            
            if (0 == $last) {
                $end = end($backlog);
                $last = $end->id;
            }
            
            $populate['last'] = $last;
            
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('backlog', $backlog);
        
        $this->setHeadTitle('INPUTS - FILL BACKLOG');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('FILL BACKLOG');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('FILL BACKLOG', $this->view->actionUrl('fill-backlog'));
        $breadrumb->add('ENTRY');
        
        return $this->getViewModel();
    }
    
    /**
     * get list filter
     * 
     * @return string
     */
    private function _getFilter()
    {
        $this->_loadFilterForm();
        $this->_sql->clear();
        $this->_sql->whereLess('s.instalment_date', LoanHelper::reportingDate())
                   ->whereZero('s.has_dcb_entry')
                   ->whereZero('l.closed');
        if ($this->isManager()) {
            $this->_managerFilter();
        } else {
            $this->_adminFilter();
        }
        
        $this->_typeFilter();
        $this->_sourceFilter();
        
        $this->_setMemberFilter();
  
        $this->view->setVariable('filters', $this->_filters);
        
        return $this->_sql->getWhere();
    }
    
    private function _loadFilterForm()
    {
        $this->_filters = new FilterForm();
        $fields = [
            'fmc', 'code', 'type', 'source', 'member', 'member_id',
        ];
        $this->_filters->addFields($fields);
        $this->_filters->numeric(['fmc', 'type', 'source', 'member_id']);
        if ($this->isManager()) {
            $this->_filters->addFilter('fmc', $this->_filters->getCollectionFilter([$this->_branch->id()]));
        }
        
        $this->_filterValues = $this->_filters->getFilteredValues();
    }
    
    private function _managerFilter()
    {
        $memberIds = $this->_branch->memberIds();
        if (empty($memberIds)) {
            $this->_sql->whereZero('l.id');
            return;
        }
        
        if ($this->_filterValues['member_id'] > 0) {
            if (! in_array($this->_filterValues['member_id'], $memberIds)) {
                $this->_sql->whereIn('l.member_id', $memberIds);
                return;
            }
            
            $this->_sql->whereId('l.member_id', $this->_filterValues['member_id']);
            return;
        }
        
        $this->_sql->whereIn('l.member_id', $memberIds);
    }
    
    private function _adminFilter()
    {
        $sql = $this->_model->getSql();
        if ($this->_filterValues['fmc'] > 0) {
            if ($this->_fmcModel->hasId($this->_filterValues['fmc'])) {
                $sql->whereId('fmc_id', $this->_filterValues['fmc']);
                $memberIds = $this->_memberModel->selectIds($sql->getWhere());
                if (empty($memberIds)) {
                    $this->_sql->whereZero('l.id');
                    return;
                }
                
                if ($this->_filterValues['member_id'] > 0) {
                    if (! in_array($this->_filterValues['member_id'], $memberIds)) {
                        $this->_sql->whereIn('l.member_id', $memberIds);
                        return;
                    }
                    
                    $this->_sql->whereId('l.member_id', $this->_filterValues['member_id']);
                    return;
                }
            
                $this->_sql->whereIn('l.member_id', $memberIds);
                return;
            }
        }
        
        if ('' == $this->_filterValues['code']) {
            return;
        }
        
        $sql->whereEqual('code', $this->_filterValues['code']);
        $where = $sql->getWhere();
        if ($this->_memberModel->selectTotal($where) > 0) {
            $this->_sql->whereId('l.member_id', $this->_memberModel->selectId($where));
        } else {
            $this->_sql->whereZero('l.id');
        }
    }
    
    private function _typeFilter()
    {
        if ($this->_filterValues['type'] > 0) {
            if ($this->_loanTypeModel->hasId($this->_filterValues['type'])) {
                $this->_sql->whereId('l.loan_type_id', $this->_filterValues['type']);
            }
        }
    }
    
    private function _sourceFilter()
    {
        if ($this->_filterValues['source'] > 0) {
            if ($this->_loanSourceModel->hasId($this->_filterValues['source'])) {
                $this->_sql->whereId('l.loan_source_id', $this->_filterValues['source']);
            }
        }
    }
    
    private function _setMemberFilter()
    {
        $fmcId = 0;
        if ($this->isManager()) {
            $fmcId = $this->_branch->id();
        } elseif ($this->_filterValues['fmc'] > 0) {
            if ($this->_fmcModel->hasId($this->_filterValues['fmc'])) {
                $fmcId = $this->_filterValues['fmc'];
            }
        }
        
        $options = [];
        if ($fmcId > 0) {
            $sql = $this->_model->getSql();
            $sql->whereId('fmc_id', $fmcId);
            $list = $this->_memberModel->selectFields(['id', 'name', 'code'], $sql->getWhere(), 'code');
            foreach ($list as $item) {
                $options[$item->id] = $item->code . ' - ' . $item->name;
            }
        }
        
        $this->view->setVariable('memberOptions', $options);
    }
    
}
