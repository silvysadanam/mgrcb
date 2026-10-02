<?php
/**
 * Application Pqr Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Constant\Tab;
use Application\Form\{FilterForm, AjaxForm};
use Application\Form\Pqr\{LoanForm, MemberForm};
use Application\Utility\{LoanHelper, PqrLoanExport, PqrMemberExport};
use Application\Generator\{LoanReport, MemberReport};

use Shared\Model\PairModel;

use Web\View\Support\LookupStore;


class PqrController extends ApplicationController
{
    const REPORT_PRINT = 0;
    const REPORT_DOWNLOAD = 1;
    const REPORT_INLINE = 2;
    
    /**
     * 
     * @var boolean
     */
    private $_hasReport = false;
    
    /**
     * 
     * @var string
     */
    private $_level = 'Loans';
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->currentTab(Tab::INTERFACE_PQR);
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
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new LoanReport();
                if ($this->isManager()) {
                    $generator->branch($this->_branch);
                }
                $generator->values($values);
                if ($generator->check()) {
                    $generator->load();
                    if (self::REPORT_DOWNLOAD == $values['report_option']) {
                        $export = new PqrLoanExport($this->view);
                        $export->run($generator);
                    } 
                    
                    if (self::REPORT_PRINT == $values['report_option']) {
                        $this->layout->setScript('pqr/view-loan');
                        $this->layout->setLayout('');
                        $this->view->setVariable('generator', $generator);

                        return $this->getViewModel();
                    }
                    
                    $this->_hasReport = true;
                    $this->view->setVariable('generator', $generator);
                } else {
                    $form->addError('No records found');
                }
            }
        } else {
            $populate = [
                'fmc_id' => 0,
                'loan_source_id' => [0],
                'loan_type_id' => [0],
                'amount' => [0],
                'report_option' => 0,
                'reporting_date' => LoanHelper::reportingDateId(),
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $pairModel = new PairModel();
        LookupStore::register('type', [0 => 'ALL'] + $pairModel->loanType());
        LookupStore::register('source', [0 => 'ALL'] + $pairModel->loanSource());
        LookupStore::register('amount', [0 => 'ALL'] + LoanHelper::loanAmountOptions());
        $this->_loadReportOptions();
        $this->registerPairs(['fmc', 'date']);
        
        $this->addModuleScript('pqr');
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
        $this->_level = 'Members';
        $form = new MemberForm();
        if ($form->isPost()) {
            $fmcId = 0;
            if ($this->isManager()) {
                $fmcId = $this->_branch->id();
            }
            if ($form->isValid()) {
                $values = $form->getValues();
                $generator = new MemberReport();
                if ($this->isManager()) {
                    $generator->branch($this->_branch);
                }
                
                $generator->values($values);
                if ($generator->check()) {
                    $generator->load();
                    if (self::REPORT_DOWNLOAD == $values['report_option']) {
                        $export = new PqrMemberExport($this->view);
                        $export->run($generator);
                    }

                    if (self::REPORT_PRINT == $values['report_option']) {
                        $this->view->setVariable('generator', $generator);
                        $this->layout->setScript('pqr/view-member');
                        $this->layout->setLayout('');

                        return $this->getViewModel();
                    }
                    
                    $this->_hasReport = true;
                    $this->view->setVariable('generator', $generator);
                } else {
                    $form->addError('No records found');
                }
            }
        } else {
            $populate = [
                'fmc_id' => 0,
                'amount' => [0],
                'report_option' => 0,
                'reporting_date' => LoanHelper::reportingDateId(),
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        
        $this->registerPairs(['fmc', 'date']);
        LookupStore::register('amount', [0 => 'ALL'] + LoanHelper::totalLoanAmountOptions());
        $this->_loadReportOptions();
        $this->addModuleScript('pqr');
        
        $this->_breadcrumb();
        
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
            'fmc_id', 'loan_source_id', 'loan_type_id', 'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->numeric(['fmc_id', 'reporting_date']);
        $form->multiple(['loan_source_id', 'loan_type_id', 'amount']);
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new LoanReport();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqr/no-data');
            return $this->getViewModel();
        }
                       
        $generator->load();
        $generator->setDownloadUrl($this->view->actionUrl('pqr/download-loan'));
        $this->view->setVariable('generator', $generator);
        
        return $this->getViewModel();
    }
    
    /**
     * download loan action
     * 
     * @return \Web\View\ViewModel
     */
    public function downloadLoanAction()
    {
        $form = new FilterForm();
        $fields = [
            'fmc_id', 'loan_source_id', 'loan_type_id', 'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->numeric(['fmc_id', 'reporting_date']);
        $form->multiple(['loan_source_id', 'loan_type_id', 'amount']);
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new LoanReport();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqr/no-data');
            return $this->getViewModel();
        }
                       
        $generator->load();
        $export = new PqrLoanExport($this->view);
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
            'fmc_id', 'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->numeric(['fmc_id', 'reporting_date']);
        $form->multiple(['amount']);
        
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new MemberReport();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqr/no-data');
            
            return $this->getViewModel();
        }
                       
        $generator->load();
        $generator->setDownloadUrl($this->view->actionUrl('pqr/download-member'));
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
            'fmc_id', 'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->numeric(['fmc_id', 'reporting_date']);
        $form->multiple(['amount']);
        
        $values = $form->load();
        $this->layout->setLayout('');
        
        $generator = new MemberReport();
        if ($this->isManager()) {
            $generator->branch($this->_branch);
        }
        
        $generator->values($values);
        if (! $generator->check()) {
            $this->layout->setScript('pqr/no-data');
            
            return $this->getViewModel();
        }
                       
        $generator->load();
        $export = new PqrMemberExport($this->view);
        $export->run($generator);
        
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
            'fmc_id', 'loan_source_id', 'loan_type_id', 
            'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->multiselect(['loan_source_id', 'loan_type_id', 'amount']);
        if ($form->isPost()) {
            $response->valid();
            $message = 'success';
            $generator = new LoanReport();
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
            'fmc_id', 'member_code', 'reporting_date', 'amount',
        ];
        $form->addFields($fields);
        $form->multiselect(['amount']);
        if ($form->isPost()) {
            $response->valid();
            $message = 'success';
            $generator = new MemberReport();
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
                $response->addData('<strong>' . $count . '</strong> matching member' . $plural . ' found', 'result')
                         ->addData('alert-success', 'alert_class');
            } else {
                $response->addData('No matching members found', 'result')
                         ->addData('alert-danger', 'alert_class');
            }
        }

        $response->message($message);
        
        return $this->getViewModel();
    }
    
    private function _loadReportOptions()
    {
        $options = [
            self::REPORT_DOWNLOAD => 'DOWNLOAD',
            self::REPORT_INLINE => 'VIEW INLINE',
        ];
        $this->view->setVariable('reportOptions', $options);
    }
    
    private function _breadcrumb()
    {
        $this->setHeadTitle('PQR - ' . $this->_level . ' Level');
        $this->setPageHeading('PQR');
        $this->setPageSubheading($this->_level . ' Level');
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('PQR', $this->view->actionUrl('pqr'));
        $breadrumb->add($this->_level . ' Level');
        
        $this->view->getVariable('level', $this->_level);
        $this->view->setVariable('hasReport', $this->_hasReport);
    }
   
}
