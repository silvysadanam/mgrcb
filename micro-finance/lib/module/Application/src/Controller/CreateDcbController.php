<?php
/**
 * Application CreateDcb Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\{AjaxForm, DcbCreateForm};
use Application\Utility\{LoanHelper, SchedulePreview, NotificationLogger};
use Application\Helper\TimelineLogger;
use Application\Constant\ManagerType;

use Model\{Loan, Member, Fmc, LoanType, LoanSource, LoanNumber};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class CreateDcbController extends ApplicationController
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
        $allowed = [
            ManagerType::ADMIN, ManagerType::BRANCH,
        ];
        $isManager = 0;
        $this->allowed($allowed);
        
        $fmcLoanSources = LoanHelper::branchLoanSources();
        if ($this->isManager()) {  
            $isManager = 1;
        }      
        $form = new DcbCreateForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid($isManager)) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create($this->getUserId());
                    $accountNumber = $form->getAccountNumber();
                    $logger = new TimelineLogger($this->getUserId());
                    $logger->id($id)
                           ->save('Created', 'Loan A/C ' . $accountNumber . ' created');
                    
                    $fmc = $this->_fmcModel->selectColumnById('name', $form->getValue('fmc_id'));
                    $message = 'FMC: ' . $fmc . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $accountNumber . ' - Loan created';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan created')
                                 ->message($message)
                                 ->fmcId($form->getValue('fmc_id'))
                                 ->reference($id);
                    $notification->send();
                    
                    $this->setLinkId($id);
                    $this->_model->getAdapter()->commit();
                } catch (\PDOException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    die($ex->getMessage());
                } catch (\Web\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    die($ex->getMessage());
                }
                
                $this->setFlashMessage('DCB Loan A/C ' . $accountNumber . ' Created');
                $this->redirect('modify-dcb/view/id/' . $id);
                //$this->redirect('view-dcb/index/?code=' . rawurlencode($this->_memberModel->selectColumnById('code', $form->getValue('member_id'))));
            }
        } else {
            $populate = [
                'fmc_id' => 0,
                'member_id' => 0,
                'member_name' => '',
                'loan_type_id' => 0,
                'loan_source_id' => 0,
                'loan_date' => '',
                'interest_rate' => '',
                'tenure' => '',
                'loan_amount' => '',
                'penal_interest_rate' => '',
            ];
            
            if ($this->isManager()) {
                $populate['fmc_id'] = $this->_branch->id();
                $keys = array_keys($fmcLoanSources);
                $populate['loan_source_id'] = $keys[0];
                $fmc = $this->_branch->fmc();
                $populate['account_number'] = sprintf('%s-%d', $fmc->code, $fmc->loan_number);
            }
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->view->setVariable('branchManager', $this->isManager());
        if ($this->isManager()) {
            $this->registerPairs(['fmc', 'type', 'bank']);
            LookupStore::register('source', $fmcLoanSources);
        } else {
            $this->registerPairs();
             //$this->registerPairs(['bank'], true);
        }
        
        $memberOptions = [];
        if ($this->isManager()) {
            $memberOptions = $this->_branch->memberCodes();
        } else {
            if ($form->getValue('fmc_id') > 0) {
                $memberOptions = $this->_memberModel->selectPairs(['id', 'code'], ['fmc_id' => $form->getValue('fmc_id')], 'code');
            }
        }
        
        $this->view->setVariable('members', $memberOptions);
        
        //$this->addModuleScript('create-dcb');
        
        $this->addModuleScript('create-dcb-mf');
        $this->view->setVariable('loanSourceMF', LoanHelper::LOAN_SOURCE_MF);
       
        $this->layout->addModal('create-dcb/preview');
        $this->_breadcrumb();
        
        return $this->getViewModel();
    }
    
    /**
     * member action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        if ($form->isPost()) {
            $options = '<option value="0">--select--</option>';
            $values = $form->run();
            if ($values['id'] > 0) {
                if ($this->_fmcModel->hasId($values['id'])) {
                    $sql = $this->_memberModel->getSql();
                    $sql->whereId('fmc_id', $values['id'])
                        ->whereZero('disabled');
                    $where = $sql->getWhere();
                    if ($this->_memberModel->selectTotal($where) > 0) {
                        $list = $this->_memberModel->selectPairs(['id', 'code'], $where, 'code');
                        $options .= $this->view->formHelper('select', $list);
                        $message = 'success';
                    }
                }
            }
            $response->addData($options, 'result')
                     ->valid();
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * member name action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberNameAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        if ($form->isPost()) {
            $name = '';
            $values = $form->run();
            if ($values['id'] > 0) {
                if ($this->_memberModel->hasId($values['id'])) {
                    $name = $this->_memberModel->selectColumnById('name', $values['id']);
                    $message = 'success';
                }
            }
            $response->addData($name, 'result')
                     ->valid();
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * emi action
     * 
     * @return \Web\View\ViewModel
     */
    public function emiAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['amount', 'tenure', 'interest']);
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['amount'] > 0 && $values['tenure'] > 0) {
                $rate = LoanHelper::interestRate($values['interest']);
                $emi = LoanHelper::emi($values['amount'], $rate, $values['tenure']);
                $response->addData($emi, 'result')
                         ->valid();
                $message = 'success';
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    
    /**
     * preview action
     * 
     * @return \Web\View\ViewModel
     */
    public function previewAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['amount', 'tenure', 'interest', 'emi', 'loan_date']);
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['amount'] > 0 && $values['tenure'] > 0 && $values['emi'] > 0) {
                $preview = new SchedulePreview();
                $result = $preview->run($values);
                
                $response->addData($result['html'], 'html')
                         ->addData($result['loan'], 'loan')
                         ->addData($result['total'], 'total')
                         ->valid();
                $message = 'success';
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * loan number action
     * 
     * @return \Web\View\ViewModel
     */
    public function loanNumberAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['id', 'loan_date']);
        if ($form->isPost()) {
            $values = $form->run();
            $date = DateHelper::getDateTime();
            if ('' != $values['loan_date']) {
                $date1 = DateHelper::fromFormat($values['loan_date'], DateHelper::DATEPICKER);
                if (false !== $date1) {
                    $date = $date1;
                }
            }
            
            if (LoanHelper::LOAN_SOURCE_MF == $values['id']) {
                $lastNumber = 0;
                $loanNumberModel = new LoanNumber();
                $sql = $this->_model->getSql();
                $sql->whereId('loan_source_id', $values['id']);
                $where = $sql->getWhere();
                if ($loanNumberModel->selectTotal($where) > 0) {
                    $lastNumber = $loanNumberModel->selectMax('loan_number', $where);
                }

                $loanSourceModel = new LoanSource();
                $code = $loanSourceModel->selectColumnById('loan_source', $values['id']);

                $loanNumber = LoanHelper::getLoanNumber($code, $lastNumber, $date->format(DateHelper::DATEPICKER));
                $response->addData($loanNumber['loan_account'], 'result')
                         ->valid();
            }
            
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * set breadcrumb
     */
    private function _breadcrumb()
    {
        $this->setHeadTitle('INPUTS - CREATE NEW DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('CREATE NEW DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('CREATE NEW DCB');
    }
   
}
