<?php
/**
 * Shared Membership Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Beneficiary, Fmc, Boat, PanchayathBrand, MemberCard, BeneficiaryDocument, BeneficiaryPhoto, Account, BeneficiaryEntry};
use Model\Bank;

use Shared\Model\PairModel;
use Shared\Constant\{MemberType, EducationType };
use Shared\Constant\MembershipType;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;
use Shared\Constant\GenderType;
use Shared\Constant\{DiseaseType, MemberStatus};
use Shared\Constant\HouseType;
use Shared\Constant\OwnershipType;

class BeneficiaryAccount
{
    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\Member
     */
    private $_model;
    
    /**
     * 
     * @var \Model\MemberPhoto
     */
    private $_beneficiaryPhotoModel;
    
    /**
     * 
     * @var \Model\MemberCard
     */
    private $_memberCardModel;
    private $_panchayath;
    /**
     * 
     * @var \Model\MemberDocument
     */
    private $_beneficiaryDocumentModel;
    
    /**
     * 
     * @var \Model\Boat
     */
    private $_boatModel;
    
    /**
     * 
     * @var \Model\Account
     */
    private $_accountModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_member;
    
    /**
     * 
     * @var \stdClass
     */
    private $_fmc;
    
    /**
     * 
     * @var \stdClass
     */
    private $_photo;
    
    /**
     * 
     * @var array
     */
    private $_accounts = [];
    
    /**
     * 
     * @var array
     */
    private $_cards = [];
    
    /**
     * 
     * @var array
     */
    private $_boats = [];
    
    /**
     * 
     * @var array
     */
    private $_documents = [];
    
    /**
     * 
     * @var string
     */
    private $_where;
    
    /**
     * 
     * @var boolean
     */
    private $_manager = true;
    private $_sql;
    private $_entries;
    /**
     * constructor
     * 
     * @param int $id member id
     */
    public function __construct($id, $manager = true)
    {
        $this->_id = $id;
        $this->_manager = $manager;
        $this->_model = new Beneficiary();
        $this->_member = $this->_model->find($this->_id);
        $this->_memberEntry = new BeneficiaryEntry();
        $panchayathFields = ['id', 'panchayath_brand'];
        $panchayathModel = new PanchayathBrand();
        $this->_panchayath = $panchayathModel->selectFieldsById($panchayathFields, $this->_member->panchayath);
        
        $this->_beneficiaryDocumentModel = new BeneficiaryDocument();
        $this->_beneficiaryPhotoModel = new BeneficiaryPhoto();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load membership
     */
    public function load()
    {
        $this->_sql->whereId('beneficiary_id', $this->_id);
        $this->_entries = $this->_memberEntry->select($this->_sql->getWhere());
        
        $fmcModel = new Fmc();
        $this->fmc_id = 1;
        $this->_fmc = $fmcModel->find($this->fmc_id);
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id);
        $this->_where = $sql->getWhere();
        
        $this->_loadPhoto();
        $this->_loadDocuments();
        $this->_loadLookups();
    }
    
    /**
     * get member id
     * 
     * @return int
     */
    public function id()
    {
        return $this->_id;
    }
    
    /**
     * get member record
     * 
     * @return \stdClass
     */
    public function member()
    {
        return $this->_member;
    }
    public function memberName()
    {
        return $this->_member->name;
    }
    /**
     * get member photo record
     * 
     * @return \stdClass
     */
    public function photo()
    {
        return $this->_photo;
    }
    public function panchayath()
    {
        return $this->_panchayath;
    }

    public function entries()
    {
        return $this->_entries;
    }
    
     /**
     * get member fmc record
     * 
     * @return \stdClass
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    /**
     * get member type name
     * 
     * @return string
     */
    public function memberType()
    {
        $types = [
            MemberType::MEMBER => 'Member',
            MemberType::DONOR => 'Donor',
        ];
        
        return $types[$this->_member->member_type_id];
    }
    
     public function educationType()
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
        
        return $types[$this->_member->beducation];
    }
    public function disabilityTypes()
    {
        $types = [
            DiseaseType::CONGENITAL => 'Congenital',
            DiseaseType::DISEASE => 'Disease',
            DiseaseType::ACCIDENT => 'Accident',
            DiseaseType::OLDAGE => 'Old Age',
            DiseaseType::OTHERCAUSES => 'Other Causes',
        ];
        
        return $types[$this->_member->disability_reason] ?? '';
    }
    public function houseType()
    {
        $types = [
            HouseType::TERRACE => 'Terrace',
            HouseType::TILED => 'Tiled',
            HouseType::THATCHED => 'Thatched',
            HouseType::HUT => 'Hut',
        ];
        
        return $types[$this->_member->house_type] ?? '';
    }
    public function gender()
    {
         $types = [
            GenderType::MALE => 'Male',
            GenderType::FEMALE => 'Female',
            GenderType::OTHER => 'Other',
        ];
        
        return $types[$this->_member->gender] ?? '';
    }
    public function houseOwnership()
    {
         $types = [
            OwnershipType::OWNED => 'Owned',
            OwnershipType::PURAMBOKE => 'Government Land',
            OwnershipType::RENTED => 'Rented',
        ];
        
        return $types[$this->_member->house_ownership] ?? '';
    }

    /**
     * get cards
     * 
     * @return array
     */
    public function cards()
    {
        return $this->_cards;
    }
    
       
    /**
     * get documents
     * 
     * @return array
     */
    public function documents()
    {
        return $this->_documents;
    }
    
    /**
     * get accounts
     * 
     * @return array
     */
    public function accounts()
    {
        return $this->_accounts;
    }
    
    /**
     * get date of birth
     * 
     * @return \stdClass
     */
    public function dateOfBirth()
    {
        if ('' != $this->_member->date_of_birth) {
            return DateHelper::datepicker($this->_member->date_of_birth);
        }
        
        return '';
    }
    
    public function dateOfMedicalExam()
    {
        if ('' != $this->_member->examination_date) {
            return DateHelper::datepicker($this->_member->examination_date);
        }
        
        return '';
    }
     public function approvedDate()
    {
        if ('' != $this->_member->approved_date) {
            return DateHelper::datepicker($this->_member->approved_date);
        }
        
        return '';
    }
     public function proposedDate()
    {
        if ('' != $this->_member->proposed_date) {
            return DateHelper::datepicker($this->_member->proposed_date);
        }
        
        return '';
    }
    /**
     * get age
     * 
     * @return \stdClass
     */
    public function age()
    {
        if (!DateHelper::isDate($this->_member->date_of_birth)) {
            return '';
        }
        
        return DateHelper::year() - DateHelper::year($this->_member->date_of_birth);
    }
    
    public function active()
    {
        return 0 == $this->_member->disabled;
    }
    
    
    /**
     * load photo
     */
    private function _loadPhoto()
    {
        if ($this->_beneficiaryPhotoModel->selectTotal($this->_where) > 0) {
            $this->_photo = $this->_beneficiaryPhotoModel->selectRow($this->_where);
        }
    }
    
    /**
     * load accounts
     */
    private function _loadAccounts()
    {
        if ($this->_accountModel->selectTotal($this->_where) > 0) {
            $this->_accounts = $this->_accountModel->select($this->_where);
        }
    }
    
       
    /**
     * load documents
     */
    private function _loadDocuments()
    {
        if ($this->_beneficiaryDocumentModel->selectTotal($this->_where) > 0) {
            $this->_documents = $this->_beneficiaryDocumentModel->select($this->_where);
        }
    }
    
    /**
     * load lookups
     */
    private function _loadLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('designationType', $pairModel->designationType());
        LookupStore::register('panchayathBrand', $pairModel->panchayathBrand());
        LookupStore::register('documentType', $pairModel->documentType());
        LookupStore::register('accountType', $pairModel->accountType());
        
        $bankModel = new Bank();
        LookupStore::register('banks', $bankModel->selectIndexed(null, 'bank'));
    }
    
    
}
