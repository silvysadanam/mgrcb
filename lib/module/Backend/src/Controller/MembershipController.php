<?php
/**
 * Backend Membership Controller
 * 
 * @category Backend
 * @package  Backend\Controller
 */

namespace Backend\Controller;

use Backend\BackendController;
use Backend\Constant\{Tab, FlashMessage};
use Backend\Form\Membership\{CreateForm, SearchForm};


use Model\{Member, Fmc, Bank};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;

use Shared\Constant\MemberType;
use Shared\Constant\{MembershipType, MemberStatus};
use Shared\Form\FilterForm;
use Shared\Model\PairModel;
use Shared\Utility\{Membership, ManageMembership, MemberReport};


class MembershipController extends BackendController
{  
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    const REPORT_PRINT = 0;
    const REPORT_DOWNLOAD = 1;
    const REPORT_INLINE = 2;
    /**
     *
     * @var \Model\Fmc 
     */
    private $_fmcModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Member();
        $this->_fmcModel = new Fmc();
        $this->currentTab(Tab::MEMBERSHIP);
        $this->setFlashMessageTitle('Member');
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->_list();
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->_loadMemberStatus();
        $this->_breadcrumb('All');
        
        return $this->getViewModel();
    }
    
    /**
     * member action
     * 
     * @return \Web\View\ViewModel
     */
    public function memberAction()
    {
        $this->_list(MemberType::MEMBER);
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->_loadMemberStatus();
        $this->_breadcrumb('Members');
        
        return $this->getViewModel();
    }
    
    /**
     * donor action
     * 
     * @return \Web\View\ViewModel
     */
    public function donorAction()
    {
        $this->_list(MemberType::DONOR);
        $this->_breadcrumb('Donors');
        
        return $this->getViewModel();
    }
    
    /**
     * create action
     * 
     * @return \Web\View\ViewModel
     */
    public function createAction()
    {
        $form = new CreateForm();
        $form->setModel($this->_model);
        if ($form->isPost()) {
            if ($form->isValid()) {
                try {
                    $this->_model->getAdapter()->beginTransaction();
                    $id = $form->create();
                    $this->_model->getAdapter()->commit();
                    $this->setActionFlashMessage(FlashMessage::CREATE);
                    $this->redirect('membership/view/id/' . $id);
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
                $this->redirect('membership');
            }
        } else {
            $populate = [
                'approved_date' => DateHelper::datepicker(),
                'member_type_id' => MemberType::MEMBER,
                'membership_type_id' => MembershipType::LIFEMEMBER,
            ];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->_loadFormOptions();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('membership-create');
        $this->_breadcrumb('New');
        
        return $this->getViewModel();
    }
    
    /**
     * search action
     * 
     * @return \Web\View\ViewModel
     */
    public function searchAction()
    {
        $form = new SearchForm(true);
        if ($form->isPost()) {
            if ($form->isValid()) {
                
            }
        } else {
            $form->populate(['match' => 0, 'search' => '']);
        }
        
        $this->registerForm($form);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->addModuleScript('membership-search');
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
        $membership = new Membership($id);
        $membership->load();
        $this->view->setVariable('membership', $membership);
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    /**
     * change action
     * 
     * @return \Web\View\ViewModel
     */
    public function changeAction()
    {
        $id = $this->_getId();
        $membership = new Membership($id);
        $membership->load();
        $this->view->setVariable('membership', $membership);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('document-validator');
        $this->addModuleScript('photo-validator');
        $this->addModuleScript('membership-change-disable');
        $this->layout->addModal('membership/change');
        $this->_breadcrumb('Edit');
        
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

        $membership = new ManageMembership($id);
        $result = $membership->run($this->getAjaxResponse(), $this->getParamId('action'));
        if (isset($result['flash'])) {
            $this->setFlashMessage($result['flash']);
        }
        
        return $this->getViewModel();
    }
    
    /**
     * active action
     * 
     * @return \Web\View\ViewModel
     */
    public function activeAction()
    {
        $this->_statusList();
        $this->_breadcrumb('Active');
        
        return $this->getViewModel();
    }
    
    /**
     * inactive action
     * 
     * @return \Web\View\ViewModel
     */
    public function inactiveAction()
    {
        $this->_statusList(1);
        $this->_breadcrumb('Active');
        
        return $this->getViewModel();
    }
    
    
    /**
     * load list
     * 
     * @param int $list
     */
    private function _list($list = 0)
    {
        $filters = new FilterForm();
        $fields = ['status', 'mtype'];
        if (0 == $list) {
            $fields[] = 'type';
        }
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        
        $sql = $this->_model->getSql();
        if ($values['status'] > 0) {
                $sql->whereId('m.disabled', $values['status']);
        }
        
        if (MemberType::MEMBER == $list) {
            $sql->whereId('m.member_type_id', MemberType::MEMBER);
        } elseif (MemberType::DONOR == $list) {
            $sql->whereId('m.member_type_id', MemberType::DONOR);
        } 
        if ($values['mtype'] > 0) {
            if (in_array($values['mtype'], [MembershipType::LIFEMEMBER, MembershipType::ORDMEMBER])) {
                $sql->whereId('m.membership_type_id', $values['mtype']);
            }
        }
        if (isset($values['type']) > 0) {
            if (in_array($values['type'], [MemberType::MEMBER, MemberType::DONOR])) {
                $sql->whereId('m.member_type_id', $values['type']);
            }
        }
        
        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_model->getTableQuoted() . ' `m` '
               . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() 
               . ' `f` ON `f`.`id` = `m`.`fmc_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`,`m`.`mobile`, `m`.`location`, '
                    . '`m`.`member_type_id` ' . $query 
                    . ' ORDER BY `m`.`code`, `m`.`name` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        $this->view->setVariable('total', $total);
        $this->view->setVariable('filters', $filters);
        $this->registerPairs();
        $this->addModuleScript('list');
    }
    
    /**
     * load member types
     */
    private function _loadMemberTypes()
    {
        $types = [
            MemberType::MEMBER => 'Member',
            MemberType::DONOR => 'Donor',
        ];
        
        LookupStore::register('type', $types);
    }
    
        /**
     * load member types
     */
    private function _loadMembershipTypes()
    {
        $types = [
            MembershipType::LIFEMEMBER => 'Life Membership',
            MembershipType::ORDMEMBER => 'Ordinary membership',
        ];
        
        LookupStore::register('mtype', $types);
    }
    
     private function _loadMemberStatus()
    {
        $types = [
            MemberStatus::MEMBACTIVE => 'Active',
            MemberStatus::MEMBINACTIVE => 'InActive',
        ];
        
        LookupStore::register('status', $types);
    }
    
    /**
     * load form options
     */
    private function _loadFormOptions()
    {
        $pairModel = new PairModel();
        LookupStore::register('cardType', $pairModel->cardType());
        LookupStore::register('documentType', $pairModel->documentType());
        LookupStore::register('panchayathBrand', $pairModel->panchayathBrand());
        LookupStore::register('accountType', $pairModel->accountType());
    }
    
    /**
     * set date limit
     */
    private function _setDateLimit()
    {
        $this->view->setVariable('startDate', '01/01/1941');
        $year = DateHelper::year() - 17;
        $this->view->setVariable('endDate', '31/12/' . $year);
    }
    
    /**
     * load banks
     */
    private function _loadBanks()
    {
        $bankModel = new Bank();
        if ($bankModel->selectTotal() > 0) {
            $list = $bankModel->select(null, 'bank');
            $banks = [];
            foreach ($list as $item) {
                $banks[$item->id] = $item->bank . ', ' . $item->branch . ' / ' . $item->ifsc;
            }
            LookupStore::register('bank', $banks);
            $this->view->setVariable('hasBanks', true);
            
        } else {
            $this->view->setVariable('hasBanks', false);
        }
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
            $valid = $this->_model->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'membership');
        }
        
        return $id;
    }
    
    /**
     * set breadcrumb
     * 
     * @param string $page
     */
    private function _breadcrumb($page = null)
    {
        $this->setHeadTitle('Membership - ' . $page);
        $this->setPageHeading('Membership');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Membership', $this->view->actionUrl('membership'));
        $breadrumb->add($page);
    }
    
    /**
     * load status wise list
     * 
     * @param int $disabled
     */
    private function _statusList($disabled = 0)
    {
        $filters = new FilterForm();
        $fields = ['fmc', 'type'];
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        
        $sql = $this->_model->getSql();
        $sql->whereId('disabled', $disabled);
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $sql->whereId('m.fmc_id', $values['fmc']);
            }
        }
        
        if ($values['type'] > 0) {
            if (in_array($values['type'], [MembershipType::LIFEMEMBER, MembershipType::ORDMEMBER])) {
                $sql->whereId('m.member_type_id', $values['type']);
            }
        }
        
        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_model->getTableQuoted() . ' `m` '
               . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() 
               . ' `f` ON `f`.`id` = `m`.`fmc_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`, `m`.`fmc_id`, '
                    . '`m`.`member_type_id`, `m`.`inactive_date` ' . $query 
                    . ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_model->queryRows($query2));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('filters', $filters);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->addModuleScript('list');
    }
    public function filterAction()
    {
        $this->registerPairs(['mtype', 'status', 'type']);
        $fmcId = 0;
        $name = '';
        $parishName = '';
        $location = '';
        $mtypeId = 0;
        $mobile = 0;
        $statusID = 0;
        $typeId = 0;

        // Initialize variables
        $valid = [];
        $generator = null;
        $form = new SearchForm();
        if ($form->isPost()) {
            if ($form->isFilterValid()) {
                $values = $form->getValues();
                if ($values['mtype'] > 0) {
                    $mtypeId = $values['mtype'];
                }
                if ($values['type'] > 0) {
                    $typeId = $values['type'];
                }
                if ($values['mobile'] > 0) {
                    $mobile = $values['mobile'];
                }
                $statusID = $values['status'];
                $name = $values['name'];
                $parishName = $values['parish'];
                $location = $values['location'];
                $fmcId = 1;
                $generator = new MemberReport();
                $generator->setFmcId($fmcId)
                          ->setMembershipTypeId($mtypeId)
                          ->setTypeId($typeId)
                          ->setName($name)
                          ->setLocation($location)
                          ->setMobile($mobile)
                          ->setParishName($parishName)
                          ->setStatus($statusID);

                $valid = $generator->run();
                if (self::REPORT_DOWNLOAD == $values['report_option']) {
                        $export = new PqrLoanExport($this->view);
                        $export->run($generator);
                } 

                if (self::REPORT_PRINT == $values['report_option']) {
                    $this->layout->setScript('membership/view-member');
                    $this->layout->setLayout('');
                    $this->view->setVariable('list', $valid);
                    return $this->getViewModel();
                }
                if (!$valid) {
                    $form->addError('No matching customers found');
                }
            }

        } else {
            $populate = [
                'source' => 0,
                'type' => 0,
            ];

            $form->populate($populate);
        }

        $this->view->setVariable('list', $valid);
        $this->registerForm($form);
        $this->_loadMemberTypes();
        $this->_loadMembershipTypes();
        $this->_loadMemberStatus();
        $this->_loadReportOptions();

        $this->view->setVariable('generator', $generator);

        $this->_breadcrumb();

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
}
