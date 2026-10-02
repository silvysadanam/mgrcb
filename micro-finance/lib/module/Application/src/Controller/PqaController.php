<?php
/**
 * Application Pqa Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\{FilterForm, AjaxForm};
use Application\Form\Pqa\{LoanForm, MemberForm};
use Application\Utility\{PqaLoanExport, PqaMemberExport, LoanHelper};
use Application\Generator\{LoanAnalysis, MemberAnalysis};

use Model\{Loan, Member, Fmc};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;


class PqaController extends ApplicationController
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
        
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Loan();
        $this->_fmcModel = new Fmc();
        $this->_memberModel = new Member();
        $this->currentTab(Tab::INTERFACE_PQA);
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $form = new LoanForm();
        if ($form->isPost()) {
            $fmcId = 0;
            if ($this->isManager()) {
                $fmcId = $this->_branch->id();
            }
            if ($form->isValid($fmcId)) {
                $values = $form->getValues();
                $generator = new LoanAnalysis();
                if ($this->isManager()) {
                    $generator->branch($this->_branch);
                }
                $generator->values($values);
                if ($generator->check()) {
                    $generator->load();
                    if ($values['report_option'] > 0) {
                        $export = new PqaLoanExport($this->view);
                        $export->run($generator);
                    } else {
                        $this->view->setVariable('generator', $generator);
                        $this->layout->setScript('pqa/view-loan');
                        $this->layout->setLayout('');

                        return $this->getViewModel();
                    }
                }
                
                $form->addError('No records found');
            }
        } else {
            $form->populate($this->_getPopulate('loan'));
        }
        
        $this->registerForm($form);
        $this->_loadFormOptions('loan');
        $this->addModuleScript('pqa');
        $this->_breadcrumb('Loans');
        
        return $this->getViewModel();
    }
    
    /**
     * member action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberAction()
    {
        $form = new MemberForm();
        if ($form->isPost()) {
            $fmcId = 0;
            if ($this->isManager()) {
                $fmcId = $this->_branch->id();
            }
            if ($form->isValid($fmcId)) {
                $values = $form->getValues();
                $generator = new MemberAnalysis();
                if ($this->isManager()) {
                    $generator->branch($this->_branch);
                }
                $generator->values($values);
                if ($generator->check()) {
                    $generator->load();
                    if ($values['report_option'] > 0) {
                        $export = new PqaMemberExport($this->view);
                        $export->run($generator);
                    } else {
                        $this->view->setVariable('generator', $generator);
                        $this->layout->setScript('pqa/view-member');
                        $this->layout->setLayout('');

                        return $this->getViewModel();
                    }
                }
                
                $form->addError('No records found');
            }
        } else {
            $form->populate($this->_getPopulate('member'));
        }
        
        $this->registerForm($form);
        $this->_loadFormOptions('member');
        $this->addModuleScript('pqa');
        $this->_breadcrumb('Members');
        
        return $this->getViewModel();
    }
    
    /**
     * check loan action
     * 
     * @return \Web\View\ViewModel
     */
    public function checkLoanAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $fields = [
            'fmc_id', 'member_code', 'loan_source_id', 'loan_type_id', 
            'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $form->multiselect(['loan_source_id', 'loan_type_id', 'amount']);
        if ($form->isPost()) {
            $response->valid();
            $message = 'success';
            $generator = new LoanAnalysis();
            if ($this->isManager()) {
                $generator->branch($this->_branch);
            }
            $generator->values($form->run());
            if ($generator->check()) {
                $count = $generator->records();
                $plural = '';
                if ($count > 1) {
                    $plural = 's';
                }
                $response->addData('<strong>' . $count . '</strong> matching loan' . $plural . ' found', 'result')
                         ->addData('alert-success', 'alert_class');
            } else {
                $response->addData('No matching loans found', 'result')
                         ->addData('alert-danger', 'alert_class');
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    /**
     * check member action
     * 
     * @return \Web\View\ViewModel
     */
    public function checkMemberAction()
    {
        if (! $this->isAjaxRequest()) {
            return $this->getViewModel();
        }
        
        $response = $this->getAjaxResponse();
        $message = 'Invalid request';
        $form = new AjaxForm();
        $fields = [
            'fmc_id', 'member_code', 'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $form->multiselect(['amount']);
        if ($form->isPost()) {
            $response->valid();
            $message = 'success';
            $hasRecords = false;
            $generator = new MemberAnalysis();
            if ($this->isManager()) {
                $generator->branch($this->_branch);
            }
            $generator->values($form->run());
            if ($generator->check()) {
                $count = $generator->records();
                if ($count > 0) {
                    $hasRecords = true;
                    $plural = '';
                    if ($count > 1) {
                        $plural = 's';
                    }
                    $response->addData('<strong>' . $count . '</strong> matching member' . $plural . ' found', 'result')
                             ->addData('alert-success', 'alert_class');
                }
            }
            
            if (! $hasRecords) {
                $response->addData('No matching members found', 'result')
                         ->addData('alert-danger', 'alert_class');
            }
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
        $form->addField('code');
        if ($form->isPost()) {
            $name = '';
            $values = $form->run();
            if ('' != $values['code']) {
                $sql = $this->_model->getSql();
                $sql->whereEqual('code', $values['code']);
                if ($this->isManager()) {
                    $sql->whereId('fmc_id', $this->_branch->id());
                } elseif ($values['id'] > 0) {
                    $sql->whereId('fmc_id', $values['id']);
                }
                $where = $sql->getWhere();
                if ($this->_memberModel->selectTotal($where) > 0) {
                    $name = $this->_memberModel->selectColumn('name', $where);
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
     * view loan action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewLoanAction()
    {
        $form = new FilterForm();
        $fields = [
            'fmc_id', 'member_code', 'loan_source_id', 'loan_type_id', 
            'report_option', 'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $numeric = ['fmc_id', 'report_option', 'reporting_date', 'ageing'];
        $form->numeric($numeric);
        $form->multiple(['loan_source_id', 'loan_type_id', 'amount']);
        $form->addFilter('report_option', $form->getYesOrNoFilter());
        $form->addFilter('ageing', $form->getMinMaxFilter(LoanHelper::AGEING_MAX_ID));
        
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new LoanAnalysis();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqa/no-data');
            
            return $this->getViewModel();
        }
        
        $generator->load();
        $generator->setDownloadUrl($this->view->actionUrl('pqa/download-loan'));
        $this->view->setVariable('generator', $generator);
        $this->layout->setScript('pqa/view-loan');

        return $this->getViewModel();
    }
    
    /**
     * down loan action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadLoanAction()
    {
        $form = new FilterForm();
        $fields = [
            'fmc_id', 'member_code', 'loan_source_id', 'loan_type_id', 
            'report_option', 'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $numeric = ['fmc_id', 'report_option', 'reporting_date', 'ageing'];
        $form->numeric($numeric);
        $form->multiple(['loan_source_id', 'loan_type_id', 'amount']);
        $form->addFilter('report_option', $form->getYesOrNoFilter());
        $form->addFilter('ageing', $form->getMinMaxFilter(LoanHelper::AGEING_MAX_ID));
        
        $values = $form->load();
        
        $generator = new LoanAnalysis();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqa/no-data');
            
            return $this->getViewModel();
        }
        
        $generator->load();
        $export = new PqaLoanExport($this->view);
        $export->run($generator);

        return $this->getViewModel();
    }
    
    /**
     * view member action
     * 
     * @return \Web\View\ViewModel
     */
    public function viewMemberAction()
    {
        $form = new FilterForm();
        $fields = [
            'fmc_id', 'member_code', 'report_option', 'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $numeric = ['fmc_id', 'report_option', 'reporting_date', 'ageing'];
        $form->numeric($numeric);
        $form->multiple(['amount']);
        $form->addFilter('report_option', $form->getYesOrNoFilter());
        
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new MemberAnalysis();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        $generator->values($values);
                  
        if (! $generator->check()) {
            $this->layout->setScript('pqa/no-data');
            return $this->getViewModel();
        }
        
        $generator->load();
        $generator->setDownloadUrl($this->view->actionUrl('pqa/download-member'));
        $this->view->setVariable('generator', $generator);

        return $this->getViewModel();
    }
    
    /**
     * download member action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadMemberAction()
    {
        $form = new FilterForm();
        $fields = [
            'fmc_id', 'member_code', 'report_option', 'reporting_date', 'amount', 'ageing',
        ];
        $form->addFields($fields);
        $numeric = ['fmc_id', 'report_option', 'reporting_date', 'ageing'];
        $form->numeric($numeric);
        $form->multiple(['amount']);
        $form->addFilter('report_option', $form->getYesOrNoFilter());
        
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new MemberAnalysis();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        $generator->values($values);
                  
        if (! $generator->check()) {
            $this->layout->setScript('pqa/no-data');
            return $this->getViewModel();
        }
        
        $generator->load();
        $export = new PqaMemberExport($this->view);
        $export->run($generator);

        return $this->getViewModel();
    }
    
    
    private function _loadFormOptions($type = 'loan')
    {
        $pairModel = new PairModel();
        $this->registerPairs(['fmc', 'date']);
        
        //LookupStore::register('date', LoanHelper::reportingDateOptions());
        LookupStore::register('ageing', LoanHelper::ageingList());
        
        if ('loan' == $type) {
            LookupStore::register('amount', [0 => 'ALL'] + LoanHelper::loanAmountOptions());
            LookupStore::register('type', [0 => 'ALL'] + $pairModel->loanType());
            LookupStore::register('source',  [0 => 'ALL'] + $pairModel->loanSource());
        } else {
            LookupStore::register('amount', [0 => 'ALL'] + LoanHelper::totalLoanAmountOptions());
        }
    }
    
    private function _breadcrumb($levelTitle)
    {
        $this->setHeadTitle('PQA - ' . $levelTitle . ' Level Analysis');
        $this->setPageHeading('PQA');
        $this->setPageSubheading($levelTitle . ' Level Analysis');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('PQA', $this->view->actionUrl('pqa'));
        $breadrumb->add($levelTitle . ' Level Analysis');
    }
    
    private function _getPopulate($type = 'loan')
    {
        $multiselect = [0];
        $populate = [
            'fmc_id' => 0,
            'member_code' => '',
            'member_name' => '',
            'report_option' => 0,
            'amount' => $multiselect,
            'ageing' => 0,
            'reporting_date' => LoanHelper::reportingDateId(),
        ];
        
        if ($this->isManager()) {
            $populate['fmc_id'] = $this->_branch->id();
        }
        
        if ('loan' == $type) {
            $populate['loan_source_id'] = $multiselect;
            $populate['loan_type_id'] = $multiselect;
        }
        
        return $populate;
    }
   
}
