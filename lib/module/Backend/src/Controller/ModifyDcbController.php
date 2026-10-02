<?php
/**
 * Application ModifyDcb Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

namespace Backend\Controller;
use Backend\BackendController;
use Application\Constant\Tab;
use Application\Form\{FilterForm, AjaxForm};
use Application\Form\ModifyDcb\{EditForm, EditDcbForm, UpdateForm, ViewForm};
use Application\Form\ModifyDcb\{RefinanceForm, WriteOffForm, RescheduleForm};
use Application\Form\ModifyDcb\{CloseForm, PrecloseForm, InactiveForm};
use Application\Utility\{LoanStore, LoanHelper, NotificationLogger};
use Application\Helper\{TimelineLogger, CommentLogger};

use Model\{Loan, Member, Fmc, LoanType, LoanSource, Notification};

use Shared\Model\PairModel;
use Shared\Utility\AccountSettings;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class ModifyDcbController extends BackendController
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
        $this->currentTab(Tab::INTERFACE_DCB);        
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $where = $this->_getFilter();
        
        $queryTotal = 'SELECT COUNT(`l`.`id`) FROM  `' . $this->_model->getTable() . '` `l`';
        if (! empty($where)) {
            $queryTotal .= ' WHERE ' . $where;
        }
        $total = $this->_model->queryTotal($queryTotal);
        if ($total > 0) {
            $query = 'SELECT `l`.`id`, `l`.`loan_type_id`, `l`.`member_id`, '
                   . '`l`.`loan_date`, `l`.`loan_amount`, `l`.`loan_source_id`, '
                   . '`l`.`account_number`, `l`.`closed`, `l`.`loan_status_id`, '
                   . '`m`.`fmc_id`, `m`.`name`, `m`.`code` '
                   . 'FROM  `' . $this->_model->getTable() . '` `l` '
                   . 'INNER JOIN  `' . $this->_memberModel->getTable() . '` `m` ON `m`.`id` = `l`.`member_id`';
            if (! empty($where)) {
                $query .= ' WHERE ' . $where;
            }
            $query .= ' ORDER BY `m`.`fmc_id`, `m`.`name`';
            $this->view->setVariable('list', $this->_model->queryRows($query));
        }
        $this->view->setVariable('total', $total);
        $this->registerPairs();
        LookupStore::register('status', LoanHelper::statusFilterOptions());
        
        $this->addDatatableScripts();
        $this->addModuleScript('list');
        $this->addModuleScript('modify-dcb-list');
        $this->setHeadTitle('INPUTS - MODIFY DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB');
        
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
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        $this->view->setVariable('actions', $actions);
        $this->view->setVariable('store', $store);
        
        $store = new LoanStore($id);
        $hasDcb = $store->hasDcb();
        
        $form = new ViewForm();
        $form->setModel($this->_model);
        $form->setId($id);
        $form->setAdministratorId($this->getUserId());
        $loan = $store->loan();
        $branch_id = 0;
        $member = $store->member();
        if (LoanHelper::LOAN_SOURCE_FMC == $loan->loan_source_id) {
            $fmc_id = $member->fmc_id;            
        }else {
            $fmc_id = LoanHelper::MF_ACCOUNT_ID;
            $branch_id = $member->fmc_id;
        }
        $options = new AccountSettings($fmc_id);
        $options->load();
        $optionVoucherNo = $options->VoucherNo();
        if(empty($optionVoucherNo)){
            $this->setErrorFlashMessage('Account settings not configured for this branch', 'view-dcb');
        }
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                     if ($form->change($store, $fmc_id, $branch_id)) {
                        $this->setFlashMessage('DCB Modified');
                        $logger = new TimelineLogger($this->getUserId());
                        $logger->id($id)
                               ->save('Modifed', 'Loan modified');
                        
                        
                        $fmc = $store->fmc();
                        $message = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                                 . ', Loan A/C: ' . $loan->account_number . ' - Loan details modified';
                        $notification = new NotificationLogger($this->getUserId());
                        $notification->subject('Loan modified')
                                     ->message($message)
                                     ->fmcId($fmc->id)
                                     ->reference($id);
                        $notification->fmc();
                    } else {
                         $this->setFlashMessage('DCB not modified');
                    }
                    $this->_model->getAdapter()->commit();
                    $this->redirect('modify-dcb/view/id/' . $id);
                }
                catch (\PDOException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                } 
            }
        }
        $pairModel = new PairModel();
        LookupStore::register('administrator', $pairModel->administrator());
        LookupStore::register('loanStatus', $pairModel->loanStatus());
        
        $this->registerForm($form);
        $this->addModuleScript('modify-dcb-view');
        $this->layout->addModal('modify-dcb/note');
        
        $this->setHeadTitle('INPUTS - MODIFY DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('VIEW');
        
        return $this->getViewModel();
    }
    
    /**
     * edit action
     * 
     * @return \Web\View\ViewModel
     */
    public function editAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $hasDcb = $store->hasDcb();
        
        if ($hasDcb) {
            $form = new EditForm();
        } else {
            $form = new EditDcbForm();
        }
        
        $form->setModel($this->_model);
        $form->setId($id);
        $form->setAdministratorId($this->getUserId());
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    if ($form->change()) {
                        $this->setFlashMessage('DCB Modified');
                        $logger = new TimelineLogger($this->getUserId());
                        $logger->id($id)
                               ->save('Modifed', 'Loan modified');
                        
                        $loan = $store->loan();
                        $fmc = $store->fmc();
                        $message = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                                 . ', Loan A/C: ' . $loan->account_number . ' - Loan details modified';
                        $notification = new NotificationLogger($this->getUserId());
                        $notification->subject('Loan modified')
                                     ->message($message)
                                     ->fmcId($fmc->id)
                                     ->reference($id);
                        $notification->fmc();
                    } else {
                         $this->setFlashMessage('DCB not modified');
                    }
                    
                    $this->_model->getAdapter()->commit();
                    $this->setLinkId($id);
                    $this->redirect('modify-dcb/view/id/' . $id);
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
            $loan = $store->loan();
            $populate = [
                'loan_type_id' => $loan->loan_type_id,
                'loan_source_id' => $loan->loan_source_id,
                'account_number' => $loan->account_number,
            ];
            
            if (! $hasDcb) {
                 $populate['loan_date'] = $this->view->datepicker($loan->loan_date);
                 $populate['loan_amount'] = intval($loan->loan_amount);
                 $populate['interest_rate'] = $loan->interest_rate;
                 $populate['penal_interest_rate'] = $loan->penal_interest_rate;
                 $populate['tenure'] = $loan->tenure;
                 $populate['emi'] = intval($loan->emi);
            }
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->registerPairs(['type', 'source']);
        if (! $hasDcb) {
            $this->layout->setScript('modify-dcb/edit-dcb');
            $this->layout->addModal('create-dcb/preview');
            $this->addModuleScript('modify-dcb-edit-full');
        }
        
        $this->setHeadTitle('INPUTS - MODIFY DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('NEW DCB');
        
        return $this->getViewModel();
    }
    
    /**
     * update action
     * 
     * @return \Web\View\ViewModel
     */
    public function updateAction()
    {
        //$this->adminOnly();
        $id = $this->_getId();

        $store = new LoanStore($id);
        if (! $store->hasDcb()) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $actions = $this->_getActions($store);
        if (! in_array('update', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $payment = $store->payment();
        $form = new UpdateForm();
        $form->setModel($this->_model);
        $form->setId($id);
        if ($form->isPost()) {
            if ($form->isValid($payment)) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $success = $form->change($this->getUserId());
                    $logger = new TimelineLogger($this->getUserId());
                    $logger->id($id)
                           ->save('Entries updated', 'Instalment entries updated');
                    $this->_model->getAdapter()->commit();
                    if (! $success) {
                        $this->setErrorFlashMessage('Some entries not saved as repayment exceeds total outstanding', 'modify-dcb/view/id/' . $id);
                    } else {
                        $loan = $store->loan();
                        $fmc = $store->fmc();
                        $message = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                                  . ', Loan A/C: ' . $loan->account_number . ' - DCB entries updated';
                        $notification = new NotificationLogger($this->getUserId());
                        $notification->subject('DCB entires updated')
                                     ->message($message)
                                     ->fmcId($fmc->id)
                                     ->reference($id);
                        $notification->fmc();
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

                $this->setLinkId($id);
                $this->setFlashMessage('DCB Modified');
                $this->redirect('modify-dcb/view/id/' . $id);
            }
        } else {
            $populate = [
                'repayment' => [],
                'catch' => [],
                'last' => 0,
            ];
            
            foreach ($payment as $item) {
                $populate['repayment'][$item->id] = $item->collection_repaid;
                $populate['catch'][$item->id] = $item->catch_amount;
                $populate['last'] = $item->id;
            }
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - UPDATE DCB');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('UPDATE DCB');
        
        return $this->getViewModel();
    }
   
    /**
     * manage action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        //$this->adminOnly();
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['action']);
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['id'] > 0) {
                $id = $values['id'];
                if ($this->_model->hasId($id)) {
                    try {
                        $this->_model->getAdapter()->beginTransaction();
                        $store = new LoanStore($id);
                        if ('delete' == $values['action']) {
                            $store->deleteLoan();
                            $this->setFlashMessage('DCB (ID: ' . $id . ') deleted');
                            $response->valid();
                            $message = 'success';
                            $loan = $store->loan();
                            $fmc = $store->fmc();
                            $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                                     . ', Loan A/C: ' . $loan->account_number . ' - Loan deleted';
                            $notification = new NotificationLogger($this->getUserId());
                            $notification->subject('Loan deleted')
                                         ->message($content)
                                         ->fmcId($fmc->id);
                            $notification->send();
                            
                            $notificationModel = new Notification();
                            $sql = $notificationModel->getSql();
                            $sql->whereId('site_id', $this->registry('site_id'))
                                ->whereId('reference_id', $id)
                                ->whereId('reference_type', 1);
                            $where = $sql->getWhere();
                            $total = $notificationModel->selectTotal($where);
                            if ($total > 0) {
                                $notificationModel->update(['reference_id' => 0, 'reference_type' => 0], $where, $total);
                            }
                        } elseif ('reset' == $values['action']) {
                            $store->resetDcb();
                            $logger = new TimelineLogger($this->getUserId());
                            $logger->id($id)
                                   ->save('Reset', 'Loan Instalment entries removed');
                            
                            $loan = $store->loan();
                            $fmc = $store->fmc();
                            $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                                     . ', Loan A/C: ' . $loan->account_number . ' - DCB reset to defaul';
                            $notification = new NotificationLogger($this->getUserId());
                            $notification->subject('DCB reset')
                                         ->message($content)
                                         ->fmcId($fmc->id)
                                         ->reference($id);
                            $notification->send();
                            
                            $this->setLinkId($id);
                            $this->setFlashMessage('Loan DCB reset to default');
                            $response->valid();
                            $message = 'success';
                        }
                        $this->_model->getAdapter()->commit();
                    } catch (\PDOException $ex) {
                        $this->_model->getAdapter()->rollBack();
                        $response->addData($ex->getMessage(), 'exception');
                        $response->addData($ex->getTraceAsString(), 'trace');
                        $message = 'failed';
                    } catch (\Web\Exception $ex) {
                        $this->_model->getAdapter()->rollBack();
                        $response->addData($ex->getMessage(), 'exception');
                        $response->addData($ex->getTraceAsString(), 'trace');
                        $message = 'failed';
                    }
                }
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * add note action
     * 
     * @return \Web\View\ViewModel
     */
    public function addNoteAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['note']);
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['id'] > 0) {
                $id = $values['id'];
                if ($this->_model->hasId($id)) {
                    $logger = new CommentLogger($this->getUserId());
                    $logger->id($id)
                           ->save($values['note']);
                    $response->valid();
                    $message = 'success';
                    $this->setFlashMessage('Note added');
                }
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * schedule action
     * 
     * @return \Web\View\ViewModel
     */
    public function scheduleAction()
    {
        $id = $this->_getId();

        $store = new LoanStore($id);
        $this->view->setVariable('store', $store);
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - SCHEDULE');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('SCHEDULE');
        
        return $this->getViewModel();
    }
    
    /**
     * payment action
     * 
     * @return \Web\View\ViewModel
     */
    public function paymentAction()
    {
        $id = $this->_getId();

        $store = new LoanStore($id);
        $this->view->setVariable('store', $store);
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - PAYMENT');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('PAYMENT');
        
        return $this->getViewModel();
    }
    
    /**
     * schedule print action
     * 
     * @return \Web\View\ViewModel
     */
    public function schedulePrintAction()
    {
        $this->_printView();
        
        return $this->getViewModel();
    }
    
    /**
     * payment print action
     * 
     * @return \Web\View\ViewModel
     */
    public function paymentPrintAction()
    {
        $this->_printView();
        
        return $this->getViewModel();
    }
    
    /**
     * refinance action
     * 
     * @return \Web\View\ViewModel
     */
    public function refinanceAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('reschedule', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $loan = $store->loan();
        $endDate = DateHelper::getDateTime($loan->end_date);
        $endDate->modify('+1 day');
        $newStartDate = $endDate->format(DateHelper::DATEPICKER);
        $form = new RefinanceForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create();
                   
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan refinanced and rescheduled';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan refinanced')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
                    $this->setLinkId($id);
                    $this->setFlashMessage('DCB Refinanced and Rescheduled');
                    $this->redirect('modify-dcb/view/id/' . $id);
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
                } catch (\ErrorException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                }
            }
        } else {
            $summary = $store->summary();
            $populate = [
                'loan_date' => $newStartDate,
                'overdue_interest_rate' => $loan->interest_rate,
                'overdue_days' => 0,
                'overdue_amount' => 0,
                'refinance_amount' => 0,
                'loan_amount' => ceil($summary->balance_actual_principal_interest_outstanding),
                'interest_rate' => $loan->interest_rate,
                'penal_interest_rate' => $loan->penal_interest_rate,
                'loan_type_id' => $loan->loan_type_id,
                'loan_source_id' => $loan->loan_source_id,
                'account_number' => $loan->account_number,
                'tenure' => 12,
            ];
            
            $interestRate = LoanHelper::interestRate($loan->interest_rate);
            $populate['emi'] = LoanHelper::emi($populate['loan_amount'], $interestRate, $populate['tenure']);
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('new_start_date', $newStartDate);
        $this->view->setVariable('today', DateHelper::datepicker());
        $this->addModuleScript('dcb-refinance');
        $this->layout->addModal('create-dcb/preview');
        
        $pairModel = new PairModel();
        LookupStore::register('type', $pairModel->loanType());
        LookupStore::register('source', $pairModel->loanSource());
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - REFINANCE and RESCHEDULE');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('REFINANCE and RESCHEDULE');
        
        return $this->getViewModel();
    }
    
    /**
     * reschedule action
     * 
     * @return \Web\View\ViewModel
     */
    public function rescheduleAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('reschedule', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $loan = $store->loan();
        $endDate = DateHelper::getDateTime($loan->end_date);
        $endDate->modify('+1 day');
        $newStartDate = $endDate->format(DateHelper::DATEPICKER);
        $form = new RescheduleForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create();
                    
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan rescheduled';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan rescheduled')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
                    $this->setLinkId($id);
                    $this->setFlashMessage('DCB Rescheduled');
                    $this->redirect('modify-dcb/view/id/' . $id);
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
                } catch (\ErrorException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                    exit;
                }
            }
        } else {
            $summary = $store->summary();
            $populate = [
                'loan_date' => $newStartDate,
                'overdue_interest_rate' => $loan->interest_rate,
                'overdue_days' => 0,
                'overdue_amount' => 0,
                'loan_amount' => ceil($summary->balance_actual_principal_interest_outstanding),
                'interest_rate' => $loan->interest_rate,
                'penal_interest_rate' => $loan->penal_interest_rate,
                'loan_type_id' => $loan->loan_type_id,
                'loan_source_id' => $loan->loan_source_id,
                'account_number' => $loan->account_number,
                'tenure' => 12,
            ];
            
            $interestRate = LoanHelper::interestRate($loan->interest_rate);
            $populate['emi'] = LoanHelper::emi($populate['loan_amount'], $interestRate, $populate['tenure']);
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('new_start_date', $newStartDate);
        $this->view->setVariable('today', DateHelper::datepicker());
        $this->addModuleScript('dcb-reschedule');
        $this->layout->addModal('create-dcb/preview');
        
        $pairModel = new PairModel();
        LookupStore::register('type', $pairModel->loanType());
        LookupStore::register('source', $pairModel->loanSource());
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - RESCHEDULE');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('RESCHEDULE');
        
        return $this->getViewModel();
    }
    
    /**
     * write-off action
     * 
     * @return \Web\View\ViewModel
     */
    public function writeOffAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('writeoff', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $form = new WriteOffForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->change();
                    
                    $loan = $store->loan();
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan written off';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan written off')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
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

                $this->setLinkId($id);
                $this->setFlashMessage('DCB Written off');
                $this->redirect('modify-dcb/view/id/' . $id);
            }
        } else {
            $loan = $store->loan();
            $summary = $store->summary();
            $populate = [
                'write_off_amount' => $summary->balance_actual_principal_interest_outstanding,
                'write_off_interest' => 0,
                'member_paid_amount' => 0,
                'balance_written_off_amount' => $summary->balance_actual_principal_interest_outstanding,
                //'write_off_date' => $this->view->datepicker($loan->end_date),
            ];
            $loanSummary = $store->loanSummary();
            $populate['write_off_date'] = $this->view->datepicker($loanSummary->as_on_date);
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('today', DateHelper::datepicker());
        $this->addModuleScript('dcb-write-off');
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - Write Off');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('WRITE OFF');
        
        return $this->getViewModel();
    }
    
    /**
     * close action
     * 
     * @return \Web\View\ViewModel
     */
    public function closeAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('close', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $demand = $store->closingDemand();
        $form = new CloseForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create();
                    
                    $loan = $store->loan();
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan closed at tenure end';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan closed')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
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

                $this->setLinkId($id);
                $this->setFlashMessage('Loan Closed at End of Tenure');
                $this->redirect('modify-dcb/view/id/' . $id);
            }
        } else {
            $populate = [
                'closing_date' => $this->view->datepicker($demand['closing_date']),
                'overdue' => LoanHelper::amount($demand['overdue']),
                'outstanding' => LoanHelper::amount($demand['outstanding']),
                'interest' => LoanHelper::amount($demand['interest']),
                'penal_interest' => LoanHelper::amount($demand['penal_interest']),
                'total' => LoanHelper::amount($demand['total']),
                'paid_amount' => '0.00',
                'payment_date' => $this->view->datepicker($demand['payment_date']),
            ];

            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('demand', $demand);
        
        $this->addModuleScript('modify-dcb-close');
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - Close Loan at End of Tenure');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('Close Loan at End of Tenure');
        
        return $this->getViewModel();
    }
    
    /**
     * preclose action
     * 
     * @return \Web\View\ViewModel
     */
    public function precloseAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('preclose', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $demand = $store->closingDemand();
        $form = new PrecloseForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->create();
                    
                    $loan = $store->loan();
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan preclosed';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan preclosed')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
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

                $this->setLinkId($id);
                $this->setFlashMessage('Loan Preclosed');
                $this->redirect('modify-dcb/view/id/' . $id);
            }
        } else {
            $populate = [
                'closing_date' => $this->view->datepicker($demand['closing_date']),
                'overdue' => LoanHelper::amount($demand['overdue']),
                'outstanding' => LoanHelper::amount($demand['outstanding']),
                'interest' => LoanHelper::amount($demand['interest']),
                'penal_interest' => LoanHelper::amount($demand['penal_interest']),
                'total' => LoanHelper::amount($demand['total']),
                'paid_amount' => '0.00',
                'payment_date' => $this->view->datepicker($demand['payment_date']),
                'catch' => 0,
            ];

            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        $this->view->setVariable('demand', $demand);
        
        $this->addModuleScript('modify-dcb-preclose');
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - Preclose Loan');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('Preclose Loan');
        
        return $this->getViewModel();
    }
    
    /**
     * calculate action
     * 
     * @return \Web\View\ViewModel
     */
    public function calculateAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['amount', 'period', 'interest']);
        if ($form->isPost()) {
            $values = $form->run();
            if ($values['id'] > 0) {
                $id = $values['id'];
                if ($this->_model->hasId($id)) {
                    $store = new LoanStore($id);
                    $amount = $store->projectedDemand($values['amount'], $values['interest'], DateHelper::fromDatepicker($values['period']));
                    $response->valid()
                             ->addData($amount, 'amount');
                    $message = 'success';
                }
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * calculate overdue action
     * 
     * @return \Web\View\ViewModel
     */
    public function calculateOverdueAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['start_date', 'end_date', 'interest', 'amount', 'outstanding']);
        if ($form->isPost()) {
            $values = $form->run();
            $result = [
                'days' => 0,
                'overdue' => 0,
                'amount' => 0,
            ];
            $start = DateHelper::fromDatepicker($values['start_date']);
            if ($values['start_date'] != $values['end_date']) {
                $date = DateHelper::getDateTime($start);
                $period = $date->diff(DateHelper::fromFormat($values['end_date'], DateHelper::DATEPICKER));
                $result['days'] = $period->days;
            }
            if ($result['days'] > 0 && $values['interest'] > 0.00) {
                $dailyRate = LoanHelper::dailyInterestRateByDate($values['interest'], $start);
                $interestAmount = $values['outstanding'] * $dailyRate * $result['days'];
                $result['overdue'] = LoanHelper::amount($interestAmount);
            }
            
            $result['actual'] = LoanHelper::amount($values['outstanding'] + $result['overdue'] + $values['amount']);
            $result['amount'] = ceil($result['actual']);
            $response->valid()
                     ->addData($result, 'result');
            $message = 'success';
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * calculate closing amount action
     * 
     * @return \Web\View\ViewModel
     */
    public function calculateClosingAmountAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $form->addFields(['closing_date']);
        if ($form->isPost()) {
            $values = $form->run();
            $store = new LoanStore($values['id']);
            $loan = $store->loan();
            $demand = $store->closingDemand();
            $result = [
                'interest' => $demand['interest'],
                'penal_interest' => $demand['penal_interest'],
            ];

            $end = DateHelper::fromDatepicker($values['closing_date']);
            if ($end != $demand['closing_date']) {
                $date = DateHelper::getDateTime($demand['outstanding_date']);
                $period = $date->diff(DateHelper::getDateTime($end));
                $days = $period->days;
                if ($loan->penal_interest_rate > 0.00 && $demand['overdue'] > 0.00) {
                    $dailyPenalRate = LoanHelper::dailyInterestRateByDate($loan->penal_interest_rate, $end);
                    $result['penal_interest'] = LoanHelper::amount($demand['overdue'] * $dailyPenalRate * $days);
                }
                
                if ($loan->interest_rate > 0.00) {
                    $dailyRate = LoanHelper::dailyInterestRateByDate($loan->interest_rate, $end);
                    $result['interest'] = LoanHelper::amount($demand['outstanding'] * $dailyRate * $days);
                }
            }
            
            $result['total'] = LoanHelper::amount($result['interest'] + $result['penal_interest']);
            $response->valid()
                     ->addData($result, 'result');
            $message = 'success';
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * inactive action
     * 
     * @return \Web\View\ViewModel
     */
    public function inactiveAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('inactive', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $form = new InactiveForm();
        $form->setModel($this->_model);
        $form->setStore($store);
        if ($form->isPost()) {
            if ($form->isValid()) {
                $form->setAdministratorId($this->getUserId());
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $form->change();
                    
                    $loan = $store->loan();
                    $fmc = $store->fmc();
                    $content = 'FMC: ' . $fmc->name . ', DCB ID: ' . $id 
                             . ', Loan A/C: ' . $loan->account_number . ' - Loan set as inactive';
                    $notification = new NotificationLogger($this->getUserId());
                    $notification->subject('Loan inactive')
                                 ->message($content)
                                 ->fmcId($fmc->id)
                                 ->reference($id);
                    $notification->send();
                    
                    $this->_model->getAdapter()->commit();
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

                $this->setLinkId($id);
                $this->setFlashMessage('DCB moved to Inactive status');
                $this->redirect('modify-dcb/view/id/' . $id);
            }
        } else {
            $summary = $store->summary();
           
            $populate = [
                'last_instalment' => $summary->schedule_id,
                'reason' => '',
                'comments' => '',
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->view->setVariable('store', $store);
        
        $this->setHeadTitle('INPUTS - MODIFY DCB - INACTIVE');
        $this->setPageHeading('INPUTS');
        $this->setPageSubheading('MODIFY DCB');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('INPUTS', $this->view->actionUrl(''));
        $breadrumb->add('MODIFY DCB', $this->view->actionUrl('modify-dcb'));
        $breadrumb->add('INACTIVE');
        
        return $this->getViewModel();
    }
    
    /**
     * activate action
     * 
     * @return \Web\View\ViewModel
     */
    public function activateAction()
    {
        $id = $this->_getId();
        $store = new LoanStore($id);
        $actions = $this->_getActions($store);
        if (! in_array('activate', $actions)) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb/view/id/' . $id);
        }
        
        $update = [
            'closed' => 0,
            'loan_status_id' => 0,
        ];
        
        $this->_model->updateById($update, $id);
        
        $logger = new TimelineLogger($this->getUserId());
        $logger->id($id)
               ->save('DCB moved to Active status', 'Inactive Loan reactivated on ' . DateHelper::datepicker());
        
        $this->setLinkId($id);
        $this->setFlashMessage('DCB moved to Active status');
        $this->redirect('modify-dcb/view/id/' . $id);
        
        return $this->getViewModel();
    }
    
    /**
     * print view
     */
    private function _printView()
    {
        $id = $this->_getId();
        $this->view->setVariable('store', new LoanStore($id));
        $this->layout->setLayout('');
    }
    
    /**
     * get list filter
     * 
     * @return string
     */
    private function _getFilter()
    {
        $filters = new FilterForm();
        $filters->addFields(['fmc', 'code', 'type', 'source', 'member', 'status']);
        $filters->numeric(['fmc', 'type', 'source', 'status']);
        $filters->addFilter('status', $filters->getMinMaxFilter(8));
        if ($this->isManager()) {
            $filters->addFilter('fmc', $filters->getCollectionFilter([$this->_branch->id()]));
        }
        $values = $filters->getFilteredValues();
        
        $sql = $this->_model->getSql();
        
        if ($this->isManager()) {
            $memberIds = $this->_branch->memberIds();
            if (! empty($memberIds)) {
                if ('' != $values['code']) {
                    $memberSql = $this->_model->getSql();
                    $memberSql->whereIn('id', $memberIds)
                              ->whereEqual('code', $values['code']);
                    $memberWhere = $memberSql->getWhere();
                    if ($this->_memberModel->selectTotal($memberWhere) > 0) {
                        $memberId = $this->_memberModel->selectId($memberWhere);
                        $sql->whereId('l.member_id', $memberId);
                    } else {
                        $sql->whereZero('l.id');
                    }
                } else {
                    $sql->whereIn('l.member_id', $memberIds);
                }
            } else {
                $sql->whereZero('l.id');
            }
        } else {
            if ($values['fmc'] > 0) {
                if ($this->_fmcModel->hasId($values['fmc'])) {
                    $memberIds = $this->_memberModel->selectIds(['fmc_id', $values['fmc']]);
                    if (! empty($memberIds)) {
                        if ('' != $values['code']) {
                            $memberSql = $this->_model->getSql();
                            $memberSql->whereIn('id', $memberIds)
                                      ->whereEqual('code', $values['code']);
                            $memberWhere = $memberSql->getWhere();
                            if ($this->_memberModel->selectTotal($memberWhere) > 0) {
                                $memberId = $this->_memberModel->selectId($memberWhere);
                                $sql->whereId('l.member_id', $memberId);
                            } else {
                                $sql->whereZero('l.id');
                            }
                        } else {
                            $sql->whereIn('l.member_id', $memberIds);
                        }
                    } else {
                        $sql->whereZero('l.id');
                    }
                }
            } elseif ('' != $values['code']) {
                $memberSql = $this->_model->getSql();
                $memberSql->whereEqual('code', $values['code']);
                $memberWhere = $memberSql->getWhere();
                if ($this->_memberModel->selectTotal($memberWhere) > 0) {
                    $memberId = $this->_memberModel->selectId($memberWhere);
                    $sql->whereId('l.member_id', $memberId);
                } else {
                    $sql->whereZero('l.id');
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
        
        if ($values['status'] > 0) {
            switch ($values['status']) {
                case LoanHelper::FILTER_STATUS_ACTIVE:
                    $sql->whereZero('l.closed');
                    break;
                
                case LoanHelper::FILTER_STATUS_CLOSED:
                    $sql->whereOne('l.closed')
                        ->whereZero('l.loan_status_id');
                    break;
                
                case LoanHelper::FILTER_STATUS_DELINQUENT:
                    $sql->whereOne('l.closed')
                        ->whereId('l.loan_status_id', LoanHelper::STATUS_DELINQUENT);
                    break;
                
                case LoanHelper::FILTER_STATUS_WRITTEN_OFF:
                    $sql->whereOne('l.closed')
                        ->whereId('l.loan_status_id', LoanHelper::STATUS_WRITTEN_OFF);
                    break;
                
                case LoanHelper::FILTER_STATUS_REFINANCED:
                    $sql->whereOne('l.closed')
                        ->whereId('l.loan_status_id', LoanHelper::STATUS_REFINANCED);
                    break;
                
                case LoanHelper::FILTER_STATUS_RESCHEDULED:
                    $sql->whereOne('l.closed')
                        ->whereId('l.loan_status_id', LoanHelper::STATUS_RESCHEDULED);
                    break;
                
                case LoanHelper::FILTER_STATUS_INACTIVE:
                    $this->_sql->whereOne('l.closed')
                               ->whereId('l.loan_status_id', LoanHelper::STATUS_INACTIVE);
                break;
                
                default:
                    $sql->whereOne('l.closed');
                    break;
            }
        }
        
        $this->view->setVariable('filters', $filters);
        
        return $sql->getWhere();
    }
    
    /**
     * get loan id
     * 
     * @return int
     */
    private function _getId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_model->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'modify-dcb');
        }
        
        if ($this->isManager()) {
            $memberId = $this->_model->selectColumnById('member_id', $id);
            if ($this->_branch->id() != $this->_memberModel->selectColumnById('fmc_id', $memberId)) {
                $this->setErrorFlashMessage('Invalid request', 'view-dcb');
            }
        }
        
        return $id;
    }
    
    /**
     * get actions
     * 
     * @param \Application\Utility\LoanStore $store
     * @return array
     */
    private function _getActions($store)
    {
        $actions = [];
        if ($this->isAuditor() || $this->isFinance()) {
            return $actions;
        }
        
        $summary = $store->loanSummary();
        $loan = $store->loan();
        $admin = $this->isAdmin() || $this->isCeo();
        $branch = $this->isManager();
        $source = $this->_loanSourceModel->find($loan->loan_source_id);
        
        if (! $store->closed()) {
            if ($admin) {
                $actions[] = 'edit';
            }
            
            if ($store->dataEntry()) {
                $entry = $store->entry();
                if ($store->hasBacklog()) {
                    $actions[] = 'backlog';
                } else {
                    $entry = $store->entry();
                    if ($entry->instalment_date == LoanHelper::reportingDate()) {
                        $actions[] = 'current';
                    }
                }
                if ($admin || ($branch && LoanHelper::SOURCE_TYPE_FMC_LOAN == $source->loan_source_type)) {
                    if ($entry->last_instalment > 0) {
                        $actions[] = 'close';
                    } elseif ($store->hasDcb()) {
                        $actions[] = 'preclose';
                    }
                }
            } elseif ($store->hasDcb()) {
                if ($admin || ($branch && LoanHelper::SOURCE_TYPE_FMC_LOAN == $source->loan_source_type)) {
                    $actions[] = 'preclose';
                    $actions[] = 'update';
                    if ($summary->paid_tenures == $loan->tenure) {
                        if ($summary->balance_actual_principal_interest_outstanding > 0.00 && $summary->balance_total_overdue > 0.00) {
                            $actions[] = 'reschedule';
                            if ($admin) {
                                $actions[] = 'writeoff';
                            }
                        }
                    } else {
                        if ($summary->balance_actual_principal_interest_outstanding > 0.00 && $summary->balance_total_overdue > 0.00) {
                            $actions[] = 'reschedule';
                        }
                    }
                }
            }
            
            if (! in_array('reschedule', $actions)) {
                if (($branch && LoanHelper::SOURCE_TYPE_FMC_LOAN == $source->loan_source_type)) {
                    if (LoanHelper::STATUS_DELINQUENT == $loan->loan_status_id) {
                        $actions[] = 'reschedule';
                    }
                }
            }
            
            if (! $admin) {
                return $actions;
            }
            
            if ($store->hasDcb()) {
                $actions[] = 'reset';
                if ($store->dormant()) {
                    $actions[] = 'inactive';
                }
            }

            if (0 == $loan->loan_status_id && 0 == $summary->refinanced_loan) {
                $actions[] = 'delete';
            }
        }
        
        if (! $admin) {
            return $actions;
        }
        
        
        if (0 == $loan->loan_status_id) {
            if (0 == $summary->refinanced_loan) {
                $actions[] = 'delete';
            }
        } else {
            if (LoanHelper::STATUS_DELINQUENT == $loan->loan_status_id) {
                if (0 == $summary->refinanced_loan) {
                    $actions[] = 'delete';
                }
                $actions[] = 'writeoff';
                $actions[] = 'reschedule';
            } elseif (LoanHelper::STATUS_INACTIVE == $loan->loan_status_id) {
                $actions[] = 'writeoff';
                $actions[] = 'activate';
            }
        }
        
        return $actions;
    }
   
}
