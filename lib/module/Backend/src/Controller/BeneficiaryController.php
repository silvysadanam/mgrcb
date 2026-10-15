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


use Model\{Beneficiary, Fmc, Bank, BeneficiaryEntry, BeneficiaryMedicine, BeneficiaryEquipment};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;

use Shared\Constant\GenderType;
use Shared\Constant\{DiseaseType, MemberStatus, EducationType, PrescriptionType, DepartmentType, HospitalType};
use Shared\Constant\HouseType;
use Shared\Constant\OwnershipType;
use Shared\Form\FilterForm;
use Shared\Model\PairModel;
use Shared\Utility\{BeneficiaryAccount, ManageBeneficiary, BeneficiaryrReport, MedicineAccount, EquipmentAccount};


class BeneficiaryController extends BackendController
{  
    const REPORT_PRINT = 0;
    const REPORT_DOWNLOAD = 1;
    const REPORT_INLINE = 2;
    private $_filters = [];
    /**
     *
     * @var \Model\Member 
     */
    private $_model;
    private $_medicineModel;
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
        $this->_medicineModel = new BeneficiaryMedicine();
        $this->_equipmentModel = new BeneficiaryEquipment();
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
        $this->registerPairs(['panchayathBrand'], true);
         $this->_loadDisabilityTypes();
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
        $this->registerPairs(['panchayathBrand'], true);
         $this->_loadDisabilityTypes();
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
    public function medicineviewAction()
    {
        
        $id = $this->getParamId();
        $medicineData = $this->_medicineModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        $beneficiaryMedicine = new MedicineAccount($id);
        $beneficiaryMedicine->load();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->view->setVariable('beneficiaryMedicine', $beneficiaryMedicine);
        $this->addModuleScript('beneficiary-view');
        $this->layout->addModal('beneficiary/medicineview');
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }
    
    public function medicinechangeAction()
    {
        
        $id = $this->getParamId();
        $medicineData = $this->_medicineModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        $beneficiaryMedicine = new MedicineAccount($id);
        $beneficiaryMedicine->load();
        $this->view->setVariable('beneficiaryMedicine', $beneficiaryMedicine);
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        
        $this->addModuleScript('document-validator');
        $this->addModuleScript('medicine-change');
        $this->layout->addModal('beneficiary/medicine');
        $this->_breadcrumb('View');
        
        return $this->getViewModel();
    }

    
    public function equipmentviewAction()
    {
        
        $id = $this->getParamId();
        $medicineData = $this->_equipmentModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        $beneficiaryEquipment = new EquipmentAccount($id);
        $beneficiaryEquipment->load();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->view->setVariable('beneficiaryEquipment', $beneficiaryEquipment);
        $this->addModuleScript('beneficiary-view');
        $this->layout->addModal('beneficiary/equipmentview');
        $this->_breadcrumb('View Aids & Equipment to Beneficiaries');
        
        return $this->getViewModel();
    }
    
    public function equipmentchangeAction()
    {
        
        $id = $this->getParamId();
        $medicineData = $this->_equipmentModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        $this->view->setVariable('beneficiary', $beneficiary);
        $beneficiaryEquipment = new EquipmentAccount($id);
        $beneficiaryEquipment->load();
        $this->view->setVariable('beneficiaryEquipment', $beneficiaryEquipment);
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        
        $this->addModuleScript('document-validator');
        $this->addModuleScript('equipment-change');
        $this->layout->addModal('beneficiary/equipment');
        $this->_breadcrumb('View');
        
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
    
    public function medicineAction($list = 0)
    {
        $id = $this->_getId();
        $filters = new FilterForm();
        $fields = ['fmc'];
        if (0 == $list) {
            $fields[] = 'type';
        }
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        $beneficiary = new BeneficiaryAccount($id);
        $this->view->setVariable('beneficiary', $beneficiary);
        $sql = $this->_medicineModel->getSql();
        if ($id > 0) {
                $sql->whereId('m.beneficiary_id', $id);
        }
        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_medicineModel->getTableQuoted() . ' `m` ';
             
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_medicineModel->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`assessment_date`,`m`.`hospital`,  `m`.`last_bill_date`, `m`.`diagnosis`, `m`.`prescription_valid`, '
                    . '`m`.`last_bill_amount` ' . $query 
                    . ' ORDER BY `m`.`assessment_date` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_medicineModel->queryRows($query2));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('benID', $id);        
        $this->view->setVariable('filters', $filters);
        $this->registerPairs();
        $this->addModuleScript('list');
        $this->addModuleScript('medicine');
        $this->_loadMemberStatus();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->_breadcrumb('All');
        $this->setPageHeading('Medicines to Beneficiary');
        $this->layout->addModal('beneficiary/medicine');
        
        return $this->getViewModel();
    }
    
    public function equipmentAction($list = 0)
    {
        $id = $this->_getId();
        $filters = new FilterForm();
        $fields = ['fmc'];
        if (0 == $list) {
            $fields[] = 'type';
        }
        $filters->addFields($fields);
        $filters->numeric($fields);
        $values = $filters->getFilteredValues();
        $beneficiary = new BeneficiaryAccount($id);
        $this->view->setVariable('beneficiary', $beneficiary);
        $sql = $this->_equipmentModel->getSql();
        if ($id > 0) {
                $sql->whereId('m.beneficiary_id', $id);
        }
        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_equipmentModel->getTableQuoted() . ' `m` ';
             
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_equipmentModel->queryTotal($query1);
        if ($total > 0) {
            $paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`assessment_date`,`m`.`hospital`,  `m`.`last_bill_date`, `m`.`diagnosis`, `m`.`equipment`, '
                    . '`m`.`last_bill_amount` ' . $query 
                    . ' ORDER BY `m`.`assessment_date` LIMIT ' . $paging->sqlLimit;
            $this->view->setVariable('list', $this->_equipmentModel->queryRows($query2));
        }
        
        $this->view->setVariable('total', $total);
        $this->view->setVariable('benID', $id);        
        $this->view->setVariable('filters', $filters);
        $this->registerPairs();
        $this->addModuleScript('list');
        $this->addModuleScript('equipment');
        $this->_loadMemberStatus();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->_breadcrumb('All');
        $this->setPageHeading('Equipment to Beneficiary');
        $this->layout->addModal('beneficiary/equipment');
        
        return $this->getViewModel();
    }


    
    public function medicineprintAction()
    {
        $id = $this->getParamId();
        $medicineData = $this->_medicineModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        
        $beneficiaryMedicine = new MedicineAccount($id);
        $beneficiaryMedicine->load();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->view->setVariable('beneficiary', $beneficiary);
        $this->view->setVariable('beneficiaryMedicine', $beneficiaryMedicine);
        $this->layout->setLayout('');
        
        return $this->getViewModel();
    }
    
     public function equipmentprintAction()
    {
        $id = $this->getParamId();
        $medicineData = $this->_equipmentModel->find($id);
        $beneficiary = new BeneficiaryAccount($medicineData->beneficiary_id);
        $beneficiary->load();
        
        $beneficiaryEquipment = new EquipmentAccount($id);
        $beneficiaryEquipment->load();
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
        $this->view->setVariable('beneficiary', $beneficiary);
        $this->view->setVariable('beneficiaryEquipment', $beneficiaryEquipment);
        $this->layout->setLayout('');
        
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
        $beneficiaryMedicine = new MedicineAccount($id);
        $beneficiaryMedicine->loadMedicineData();        
        $this->view->setVariable('beneficiaryMedicine', $beneficiaryMedicine);
        
        $beneficiaryEquipment = new EquipmentAccount($id);
        $beneficiaryEquipment->loadEquipmentData();        
        $this->view->setVariable('beneficiaryEquipment', $beneficiaryEquipment);
        
        $this->layout->setLayout('');
        $this->_loadPrescriptionValidTypes();
        $this->_loadHospitalTypes();
        $this->_loadDepartmentTypes();
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
    public function managemedicineAction()
    {
        $id = $this->_getMedicineId();
        
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
     * manage action
     * 
     * @return \Web\View\ViewModel
     */
    public function manageequipmentAction()
    {
        $id = $this->_getEquipmentId();
        
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
        $fields = ['fmc','panchayath', 'disability_reason'];
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
        
        if ($values['panchayath'] > 0) {
                $sql->whereId('m.panchayath', $values['panchayath']);
        }
        if ($values['disability_reason'] > 0) {
                $sql->whereId('m.disability_reason', $values['disability_reason']);
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
    
    private function _loadPrescriptionValidTypes()
    {
        $types = [
            PrescriptionType::YEAR => 'Year',
            PrescriptionType::SIX_MONTHS => 'Six Months',
            PrescriptionType::OTHER => 'Others',
        ];
        
        LookupStore::register('prescType', $types);
    }

    private function _loadDepartmentTypes()
    {
        $types = [
            DepartmentType::PMR => 'PMR',
            DepartmentType::STROKE_CLINIC => 'Stroke Clinic',
            DepartmentType::CP_CLINIC => 'CP Clinic',
            DepartmentType::NEUROLOGY => 'Neurology',
            DepartmentType::NEPHROLOGY => 'Nephrology',
            DepartmentType::PSYCHIATRY => 'Psychiatry',
            DepartmentType::CARDIOLOGY => 'Cardiology',            
            DepartmentType::OTHER => 'Others',
        ];
        
        LookupStore::register('DepartmentType', $types);
    }
    
    private function _loadHospitalTypes()
    {
        $types = [
            HospitalType::MCH => 'MCH',
            HospitalType::SCT => 'SCT',
            HospitalType::RIO => 'RIO',
            HospitalType::GH => 'GH',
            HospitalType::OTHER => 'Others',
        ];
        
        LookupStore::register('HospitalType', $types);
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
        LookupStore::register('designationType', $pairModel->designationType());
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
    
    private function _getMedicineId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_medicineModel->hasId($id);
        }
        
        if (! $valid) {
            $this->setErrorFlashMessage('Invalid request', 'beneficiary');
        }
        
        return $id;
    }
    
     private function _getEquipmentId()
    {
        $valid = false;
        $id = $this->getParamId();
        if ($id > 0) {
            $valid = $this->_equipmentModel->hasId($id);
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
    private function _breadcrumb($page = null)
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
    
    public function filterAction()
    {
        // Old pairs (mtype, status, type) are no longer used on this form
        $valid = [];
        $generator = null;
        $form = new SearchForm();
        $this->_loadMemberTypes();
        $this->_loadDisabilityTypes();
        $this->_loadFormOptions();
        $this->_loaHouseOwnership();
        $this->_loadHouseTypes();
        $this->_loadEducationTypes();
        $this->_loadReportOptions();
        $this->registerPairs(['panchayathBrand'], true);
        $this->registerPairs(['diseaseTypes'], true);    
        if ($form->isPost()) {
            if ($form->isFilterValid()) {
                $values = $form->getValues();

                // Search criteria from the form
                $filters = [
                    'disability_reason' => (int) ($values['disability_reason'] ?? 0),
                    'disability'        => $form->getDisabilities(),
                    'ration_card'       => (int) ($values['ration_card'] ?? 0),
                    'house_type'        => (int) ($values['house_type'] ?? 0),
                    'panchayath'        => (int) ($values['panchayath'] ?? 0),
                    'gender'            => (int) ($values['gender'] ?? 0),
                    'beducation'        => (int) ($values['beducation'] ?? 0),
                    'house_ownership'   => (int) ($values['house_ownership'] ?? 0),
                    'name'              => trim($values['name'] ?? ''),
                    'address'           => trim($values['address'] ?? ''),
                    'monthly_income'    => trim($values['monthly_income'] ?? ''),
                    'mobile'            => trim($values['mobile'] ?? ''),
                ];

                // "Show All" ignores every filter
                if (!empty($values['show_all'])) {
                    $filters = [];
                }

                $generator = new BeneficiaryrReport();
                $generator->setFmcId(1)
                          ->setFilters($filters);

                $valid = $generator->run();

                if (!$valid) {
                    $form->addError('No matching customers found');
                } else {
                    $option = $values['report_option'] ?? null;

                    if (self::REPORT_DOWNLOAD == $option) {
                        $export = new MemberExport($this->view);   // see note 2
                        $export->run($generator);
                    }

                    if (self::REPORT_PRINT == $option) {
                        $this->layout->setScript('beneficiary/view-member');
                        $this->layout->setLayout('');
                        $this->view->setVariable('list', $valid);
                        return $this->getViewModel();
                    }
                }
            }
        } else {
            $form->populate([
                'gender'          => 0,
                'ration_card'     => 0,
                'house_ownership' => 0,
            ]);
        }

        $this->view->setVariable('list', $valid);
        $this->registerForm($form);        
        $this->addModuleScript('print');
        $this->view->setVariable('generator', $generator);

        $this->_breadcrumb();

        return $this->getViewModel();
    }
    
   
    
     private function _loadReportOptions()
    {
        $options = [
            self::REPORT_INLINE => 'VIEW INLINE',
            //self::REPORT_DOWNLOAD => 'DOWNLOAD',
        ];
        $this->view->setVariable('reportOptions', $options);
    }
        
}
