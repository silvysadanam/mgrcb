<?php
/**
 * Application Settings Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\{Tab, ManagerType};
use Application\Utility\{LoanHelper, NotificationLogger};

use Model\{Loan, LoanType, LoanSource, Administrator, Fmc};

use Application\Form\AjaxForm;

use Shared\Model\PairModel;

use Web\Security\Password;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class SettingsController extends ApplicationController
{
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->adminOrCeoOnly();
        $this->currentTab(Tab::INTERFACE_SETTINGS);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->setHeadTitle('Settings');
        $this->setPageHeading('Settings');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings');
        
        return $this->getViewModel();
    }
    
    /**
     * manager action
     * 
     * @return \Web\View\ViewModel
     */
    public function managerAction()
    {
        $model = new Administrator();
        $sql = $model->getSql();
        $sql->whereId('manager_type', ManagerType::BRANCH);
        $where = $sql->getWhere();
        $total = $model->selectTotal($where);
        if ($total > 0) {
            $this->view->setVariable('list', $model->select($where, 'name'));
        }
        
        $pairModel = new PairModel();
        LookupStore::register('fmc', $pairModel->fmc());
        
        $this->view->setVariable('total', $total);
        $this->addDatatableScripts();
        $this->addModuleScript('settings/manager');
        $this->layout->addModal('settings/manager');
        
        $this->_breadcrumb('Managers');
        
        return $this->getViewModel();
    }
    
    /**
     * manage manager action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageManagerAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $response = $this->getAjaxResponse();
        $response->message('Invalid input');
        $form = new AjaxForm();
        $form->addFields(['action', 'username', 'name', 'fmc_id', 'password']);
        if (! $form->isPost()) {
            return $this->getViewModel();
        }
        
        $values = $form->run();
        $action = (int) $values['action'];
        if (! in_array($action, [1, 2, 3])) {
            return $this->getViewModel();
        }
        
        $model = new Administrator();
        $id = (int) $values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return $this->getViewModel();
            }
            
            if (ManagerType::BRANCH != $model->selectColumnById('manager_type', $id)) {
                return $this->getViewModel();
            }
        }
        
        if (1 == $action) {
            $manager = $model->selectFieldsById(['fmc_id', 'name', 'username'], $id);
            $response->valid()
                     ->addData($manager, 'manager');
            
            return $this->getViewModel();
        }
        
        if (2 == $action) {
            $valid = true;
            if (! $this->_validateUsername($values['username'])) {
                $response->message('Invalid Username');
                $valid = false;
            } elseif ($model->checkByColumnExceptId('username', $values['username'], $id)) {
                $response->message('Username exists');
                $valid = false;
            }
            
            if (! $valid) {
                return $this->getViewModel();
            }
            
            if ($id > 0) {
                if ('' != $values['password']) {
                    if (! $this->_validatePassword($values['password'])) {
                        $response->message('Invalid Password');
                        $valid = false;
                    }
                }
            } else {
                if (0 == $values['fmc_id']) {
                    $response->message('FMC not selected');
                    $valid = false;
                } elseif (! $this->_validatePassword($values['password'])) {
                    $response->message('Invalid Password');
                    $valid = false;
                }
            }
            
            if (! $valid) {
                return $this->getViewModel();
            }
            
            if ($id > 0) {
                $manager = $model->find($id);
                $update = [];
                if ('' != $values['password']) {
                    $update['access_key'] = Password::encrypt($values['password']);
                }
                
                $fields = ['username', 'name'];
                foreach ($fields as $item) {
                    if ($values[$item] != $manager->{$item}) {
                        $update[$item] = $values[$item];
                    }
                }
                
                if (empty($update)) {
                    $response->message('Manager not changed');
                } else {
                    $model->updateById($update, $id);
                    $this->setFlashMessage('Manager updated');
                    $response->valid()
                             ->message('success');
                }
                
                return $this->getViewModel();
            }
            
            $insert = [
                'name' => $values['name'],
                'username' => $values['username'],
                'fmc_id' => $values['fmc_id'],
                'manager_type' => ManagerType::BRANCH,
                'access_key' => Password::encrypt($values['password']),
                'created_at' => DateHelper::timestamp(),
            ];
            $model->insert($insert);
            $this->setFlashMessage('Manager created');
            
            $fmcModel = new Fmc();
            $fmc = $fmcModel->selectColumnById('name', $values['fmc_id']);
            $message = 'New FMC Manager account "' . $values['name'] . '" added to ' . $fmc;
            $notification = new NotificationLogger($this->getUserId());
            $notification->subject('New FMC Manager account created')
                         ->message($message)
                         ->copySelf(false)
                         ->fmcId($values['fmc_id']);
            $notification->ceo();
            
            $response->valid()
                     ->message('success');

            return $this->getViewModel();
        }
        
        $model->deleteById($id);
        $this->setFlashMessage('Manager deleted');
        $response->valid()
                 ->message('success');
        
        return $this->getViewModel();
    }
    
    /**
     * loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function loanTypeAction()
    {
        $model = new LoanType();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'loan_type'));
        
            $loanModel = new Loan();
            $this->view->setVariable('used', $loanModel->selectDistinct('loan_type_id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addDatatableScripts();
        $this->addModuleScript('settings/loan-type');
        $this->layout->addModal('settings/loan-type');
        
        $this->_breadcrumb('Loan Types');
        
        return $this->getViewModel();
    }
    
    /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageLoanTypeAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $response = $this->getAjaxResponse();
        $response->message('Invalid input');
        $form = new AjaxForm();
        $form->addFields(['action', 'loan_type']);
        if (! $form->isPost()) {
            return $this->getViewModel();
        }
        
        $values = $form->run();
        $action = (int) $values['action'];
        if (! in_array($action, [1, 2])) {
            return $this->getViewModel();
        }
        
        $model = new LoanType();
        $id = (int) $values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return $this->getViewModel();
            }
        }
        
        if (1 == $action) {
            if ($id > 0) {
                if ($model->checkByColumnExceptId('loan_type', $values['loan_type'], $id)) {
                    $response->message('Loan Type exists');
                } else {
                    if ($values['loan_type'] == $model->selectColumnById('loan_type', $id)) {
                        $response->message('Loan Type not changed');
                    } else {
                        $model->updateById(['loan_type' => $values['loan_type']], $id);
                        $this->setFlashMessage('Loan Type updated');
                        $response->valid()
                                 ->message('success');
                    }
                }
            } else {
                if ($model->checkByColumn('loan_type', $values['loan_type'])) {
                    $response->message('Loan Type exists');
                } else {
                    $model->insert(['loan_type' => $values['loan_type']]);
                    $this->setFlashMessage('Loan Type "' . $this->view->escapeHtml($values['loan_type']) . '" created');
                    $response->valid()
                             ->message('success');
                }
            }
            
            return $this->getViewModel();
        }
        
        $loanModel = new Loan();
        if ($loanModel->checkByColumnId('loan_type_id', $id)) {
            $response->message('Loan Type already in use');
        } else {
            $loanType = $model->selectColumnById('loan_type', $id);
            $model->deleteById($id);
            $this->setFlashMessage('Loan Type "' . $this->view->escapeHtml($loanType) . '" deleted');
            $response->valid()
                     ->message('success');
        }
        
        return $this->getViewModel();
    }
    
    /**
     * loan source action
     * 
     * @return \Web\View\ViewModel
     */
    public function loanSourceAction()
    {
        $model = new LoanSource();
        $total = $model->selectTotal();
        if ($total > 0) {
            $this->view->setVariable('list', $model->select(null, 'loan_source'));
        
            $loanModel = new Loan();
            $this->view->setVariable('used', $loanModel->selectDistinct('loan_source_id'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addDatatableScripts();
        $this->addModuleScript('settings/loan-source');
        $this->layout->addModal('settings/loan-source');
        $this->view->setVariable('sourceType', LoanHelper::sourceTypes());
        
        $this->_breadcrumb('Loan Sources');
        
        return $this->getViewModel();
    }
    
    
    /**
     * manage loan type action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageLoanSourceAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $response = $this->getAjaxResponse();
        $response->message('Invalid input');
        $form = new AjaxForm();
        $form->addFields(['action', 'loan_source', 'loan_source_type']);
        if (! $form->isPost()) {
            return $this->getViewModel();
        }
        
        $values = $form->run();
        $action = (int) $values['action'];
        if (! in_array($action, [1, 2])) {
            return $this->getViewModel();
        }
        
        $model = new LoanSource();
        $id = (int) $values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return $this->getViewModel();
            }
        }
        
        if (1 == $action) {
            if ($id > 0) {
                if ($model->checkByColumnExceptId('loan_source', $values['loan_source'], $id)) {
                    $response->message('Loan Source exists');
                } else {
                    $update = [];
                    $current = $model->find($id);
                    if ($values['loan_source'] != $current->loan_source) {
                        $update['loan_source'] = $values['loan_source'];
                    }
                    if ($values['loan_source_type'] != $current->loan_source_type) {
                        $update['loan_source_type'] = $values['loan_source_type'];
                    }
                    
                    if (empty($update)) {
                        $response->message('Loan Source not changed');
                    } else {
                        $model->updateById($update, $id);
                        $this->setFlashMessage('Loan Source updated');
                        $response->valid()
                                 ->message('success');
                    }
                }
            } else {
                if ($model->checkByColumn('loan_source', $values['loan_source'])) {
                    $response->message('Loan Source exists');
                } else {
                    $model->insert(['loan_source' => $values['loan_source'], 'loan_source_type' => $values['loan_source_type']]);
                    $this->setFlashMessage('Loan Source "' . $this->view->escapeHtml($values['loan_source']) . '" created');
                    $response->valid()
                             ->message('success');
                }
            }
            
            return $this->getViewModel();
        }
        
        $loanModel = new Loan();
        if ($loanModel->checkByColumnId('loan_source_id', $id)) {
            $response->message('Loan Source already in use');
        } else {
            $loanSource = $model->selectColumnById('loan_source', $id);
            $model->deleteById($id);
            $this->setFlashMessage('Loan Source "' . $this->view->escapeHtml($loanSource) . '" deleted');
            $response->valid()
                     ->message('success');
        }
        
        return $this->getViewModel();
    }
    
    /**
     * ceo action
     * 
     * @return \Web\View\ViewModel
     */
    public function ceoAction()
    {
        $this->_userList(ManagerType::CEO);
        $this->view->setVariable('userTitle', 'CEO');
        
        $this->_breadcrumb('CEO');
        
        return $this->getViewModel();
    }
    
    /**
     * finance action
     * 
     * @return \Web\View\ViewModel
     */
    public function financeAction()
    {
        $this->_userList(ManagerType::FINANCE);
        $this->view->setVariable('userTitle', 'Finance Manager');
        
        $this->_breadcrumb('Finance Managers');
        
        return $this->getViewModel();
    }
    
    /**
     * auditor action
     * 
     * @return \Web\View\ViewModel
     */
    public function auditorAction()
    {
        $this->_userList(ManagerType::AUDITOR);
        $this->view->setVariable('userTitle', 'Auditor');
        
        $this->_breadcrumb('Auditors');
        
        return $this->getViewModel();
    }
    
    /**
     * manage user action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageUserAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }

        $response = $this->getAjaxResponse();
        $response->message('Invalid input');
        $form = new AjaxForm();
        $form->addFields(['action', 'username', 'name', 'password', 'type']);
        if (! $form->isPost()) {
            return $this->getViewModel();
        }
        
        $values = $form->run();
        $action = (int) $values['action'];
        if (! in_array($action, [1, 2, 3])) {
            return $this->getViewModel();
        }
        
        $allowed = [
            ManagerType::CEO => 'CEO',
            ManagerType::FINANCE => 'Finance Manager',
            ManagerType::AUDITOR => 'Auditor',
        ];
        
        $model = new Administrator();
        $id = (int) $values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return $this->getViewModel();
            }
            
            $type = $model->selectColumnById('manager_type', $id);
            if (! isset($allowed[$type])) {
                return $this->getViewModel();
            }
        }
        
        if (1 == $action) {
            $user = $model->selectFieldsById(['name', 'username', 'manager_type'], $id);
            $response->valid()
                     ->addData($user, 'user');
            
            return $this->getViewModel();
        }
        
        if (2 == $action) {
            $valid = true;
            if (! $this->_validateUsername($values['username'])) {
                $response->message('Invalid Username');
                $valid = false;
            } elseif ($model->checkByColumnExceptId('username', $values['username'], $id)) {
                $response->message('Username exists');
                $valid = false;
            }
            
            if (! $valid) {
                return $this->getViewModel();
            }
            
            if ($id > 0) {
                if ('' != $values['password']) {
                    if (! $this->_validatePassword($values['password'])) {
                        $response->message('Invalid Password');
                        $valid = false;
                    }
                }
            } else {
                if (! $this->_validatePassword($values['password'])) {
                    $response->message('Invalid Password');
                    $valid = false;
                }
            }
            
            if (! $valid) {
                return $this->getViewModel();
            }
            
            if ($id > 0) {
                $manager = $model->find($id);
                $update = [];
                if ('' != $values['password']) {
                    $update['access_key'] = Password::encrypt($values['password']);
                }
                
                $fields = ['username', 'name'];
                foreach ($fields as $item) {
                    if ($values[$item] != $manager->{$item}) {
                        $update[$item] = $values[$item];
                    }
                }
                
                if (empty($update)) {
                    $response->message($allowed[$values['type']] .  ' not changed');
                } else {
                    $model->updateById($update, $id);
                    $this->setFlashMessage($allowed[$values['type']] . ' updated');
                    $response->valid()
                             ->message('success');
                }
                
                return $this->getViewModel();
            }
            
            if (! isset($allowed[$values['type']])) {
                return $this->getViewModel();
            }
            
            $insert = [
                'name' => $values['name'],
                'username' => $values['username'],
                'manager_type' => $values['type'],
                'access_key' => Password::encrypt($values['password']),
                'created_at' => DateHelper::timestamp(),
            ];
            $model->insert($insert);
            $this->setFlashMessage($allowed[$values['type']] . ' created');
            $response->valid()
                     ->message('success');

            return $this->getViewModel();
        }
        
        $model->deleteById($id);
        $this->setFlashMessage($allowed[$values['type']] . ' deleted');
        $response->valid()
                 ->message('success');
        
        return $this->getViewModel();
    }
    
    
    /**
     * get user list by manager type
     * 
     * @param int $type
     */
    private function _userList($type)
    {
        $model = new Administrator();
        $sql = $model->getSql();
        $sql->whereId('manager_type', $type);
        $where = $sql->getWhere();
        $total = $model->selectTotal($where);
        if ($total > 0) {
            $this->view->setVariable('list', $model->select($where, 'name'));
        }
        
        $this->view->setVariable('total', $total);
        $this->addDatatableScripts();
        $this->addModuleScript('settings/user');
        $this->layout->addModal('settings/user');
        $this->view->setVariable('userType', $type);
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page)
    {
        $this->setHeadTitle('Settings - ' . $page);
        $this->setPageHeading('Settings');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Settings', $this->view->actionUrl('settings'));
        $breadrumb->add($page);
    }
    
    /**
     * validate username
     * 
     * @param string $value
     * @return boolean
     */
    private function _validateUsername($value)
    {
        if (strlen($value) < 6) {
            return false;
        }
        
        if (strlen($value) > 20) {
            return false;
        }
        
        return 1 == preg_match('/^[a-z\d]+$/', $value);
    }
    
    /**
     * validate password
     * 
     * @param string $value
     * @return boolean
     */
    private function _validatePassword($value)
    {
        if (strlen($value) < 6) {
            return false;
        }
        
        if (strlen($value) > 20) {
            return false;
        }
        
        return 1 == preg_match('/^[^[:space:]]+$/', $value);
    }
    
}
