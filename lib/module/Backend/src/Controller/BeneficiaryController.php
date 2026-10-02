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
use Backend\Form\Beneficiary\{CreateForm, SearchForm};


use Model\{Beneficiary, Fmc, Bank, BeneficiaryEntry};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;

use Shared\Constant\GenderType;
use Shared\Constant\{DiseaseType, MemberStatus, EducationType};
use Shared\Constant\HouseType;
use Shared\Constant\OwnershipType;
use Shared\Form\FilterForm;
use Shared\Model\PairModel;
use Shared\Utility\{BeneficiaryAccount, ManageBeneficiary};


class BeneficiaryController extends BackendController
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
    
    public function __construct()
    {
        parent::__construct();
        $this->loginRequired();
        $this->_model = new Beneficiary();
        $this->_fmcModel = new Fmc();
        $this->currentTab(Tab::BENEFICIARY);
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
        $this->_loadMemberStatus();
        //$this->_loadMemberTypes();
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
        $this->_list();
        $this->_loadMemberStatus();
        $this->_breadcrumb('Beneficiaries');
        
        return $this->getViewModel();
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
                    $this->redirect('beneficiary/view/id/' . $id);
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
                $this->redirect('member');
            }
        } else {
            $populate = [
                'family_name' => [''],
                'relation' => [''],
                'gender' => [''],
                'age' => [0],
                'education' => [''],
                'disability' => [''],
                'occupation' => [''],
                'income' => [''],
            ];
            $populate['disability'] = [];
            $form->populate($populate);
        }
        
        $this->registerForm($form);
        $this->registerPairs(['panchayathBrand'], true);
        $this->registerPairs(['diseaseTypes'], true);        
        $this->_loadMemberTypes();
        $this->_loadDisabilityTypes();
        $this->_loadFormOptions();
        $this->_loaHouseOwnership();
        $this->_loadHouseTypes();
        $this->_loadEducationTypes();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('beneficiary-create');
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
        $this->addModuleScript('beneficiary-search');
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
        $beneficiary = new BeneficiaryAccount($id);
        $beneficiary->load();
        $this->_loadEducationTypes();
        $this->_loadMemberTypes();
        $this->registerPairs(['diseaseTypes'], true);
        $this->view->setVariable('beneficiary', $beneficiary);
        $this->addModuleScript('beneficiary-view');
        $this->layout->addModal('beneficiary/view');
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
  
    
    /**
     * print action
     * 
     * @return \Web\View\ViewModel
     */
    public function printAction()
    {
        $id = $this->_getId();
        $beneficiary = new BeneficiaryAccount($id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        
        $this->layout->setLayout('');
        
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
        $beneficiary = new BeneficiaryAccount($id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        $this->registerPairs();
        $this->_loadMemberTypes();
        $this->_loadDisabilityTypes();
        $this->registerPairs(['diseaseTypes'], true);
        $this->_loadHouseTypes();
        $this->_loaHouseOwnership();
        $this->_loadEducationTypes();
        $this->_setDateLimit();
        $this->_loadBanks();
        $this->addModuleScript('document-validator');
        $this->addModuleScript('photo-validator');
        $this->addModuleScript('beneficiary-change');
        $this->layout->addModal('beneficiary/change');
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

        $beneficiary = new ManageBeneficiary($id);
        $result = $beneficiary->run($this->getAjaxResponse(), $this->getParamId('action'));
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
        $fields = ['fmc'];
        if (0 == $list) {
            $fields[] = 'type';
        }
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        
        $sql = $this->_model->getSql();
        if ($values['fmc'] > 0) {
            if ($this->_fmcModel->hasId($values['fmc'])) {
                $sql->whereId('m.fmc_id', $values['fmc']);
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
            $query2 = 'SELECT `m`.`id`, `m`.`name`, `m`.`mobile`, `m`.`fmc_id`, '
                    . '`m`.`address` ' . $query 
                    . ' ORDER BY `f`.`name`, `m`.`name` LIMIT ' . $paging->sqlLimit;
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
            GenderType::MALE => 'Male',
            GenderType::FEMALE => 'Female',
            GenderType::OTHER => 'Other',
        ];
        
        LookupStore::register('type', $types);
    }
    
    /**
     * load education types
     */
    private function _loadEducationTypes()
    {
        $types = [
            EducationType::CLASS_1 => 'Class-1',
            EducationType::CLASS_2 => 'Class-2',
            EducationType::CLASS_3 => 'Class-3',
            EducationType::CLASS_4 => 'Class-4',
            EducationType::CLASS_5 => 'Class-5',
            EducationType::CLASS_6 => 'Class-6',
            EducationType::CLASS_7 => 'Class-7',
            EducationType::CLASS_8 => 'Class-8',
            EducationType::CLASS_9 => 'Class-9',
            EducationType::CLASS_10 => 'Class-10',
            EducationType::DIPLOMA => 'Diploma',
            EducationType::DEGREE => 'Degree',
            EducationType::ENG => 'Engineering',
            EducationType::ITI => 'ITI',
            EducationType::PLUS_1 => 'Plus-1',
            EducationType::PLUS_2 => 'Plus-2',          
            EducationType::PG => 'PG',   
            EducationType::NIL => 'Nil',  
        ];
        
        LookupStore::register('edutype', $types);
    }
    
    private function _loadDisabilityTypes()
    {
        $types = [
            DiseaseType::CONGENITAL => 'Congenital',
            DiseaseType::DISEASE => 'Disease',
            DiseaseType::ACCIDENT => 'Accident',
            DiseaseType::OLDAGE => 'Old Age',
            DiseaseType::OTHERCAUSES => 'Other Causes',
        ];
        
        LookupStore::register('disability', $types);
    }
    
    private function _loadHouseTypes()
    {
        $types = [
            HouseType::TERRACE => 'Terrace',
            HouseType::TILED => 'Tiled',
            HouseType::THATCHED => 'Thatched',
            HouseType::HUT => 'Hut',
        ];
        
        LookupStore::register('housetypes', $types);
    }
    
    private function _loaHouseOwnership()
    {
        $types = [
            OwnershipType::OWNED => 'Owned',
            OwnershipType::PURAMBOKE => 'Government Land',
            OwnershipType::RENTED => 'Rented',
        ];
        
        LookupStore::register('houseownership', $types);
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
        LookupStore::register('diseaseTypes', $pairModel->diseaseTypes());        
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
            $this->setErrorFlashMessage('Invalid request', 'beneficiary');
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
        $this->setHeadTitle('Beneficiary - ' . $page);
        $this->setPageHeading('Beneficiary');
        $this->setPageSubheading($page);
        $breadrumb = $this->plugin('breadcrumb');
        $breadrumb->add('Beneficiary', $this->view->actionUrl('beneficiary'));
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
            if (in_array($values['type'], [MemberType::MEMBER, MemberType::AUCTIONEER])) {
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
        $this->addModuleScript('list');
    }
        
    private function _getPopulate($type = 'loan')
    {
        $multiselect = [0];
        if ('loan' == $type) {
            $populate['loan_source_id'] = $multiselect;
            $populate['loan_type_id'] = $multiselect;
        }
        
        return $populate;
    }
}
