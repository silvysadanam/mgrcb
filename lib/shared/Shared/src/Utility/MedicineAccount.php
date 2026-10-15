<?php
/**
 * Shared Membership Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Medicine, BeneficiaryMedicineDocument, Account, BeneficiaryMedicine, Beneficiary};
use Model\Bank;

use Shared\Model\PairModel;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;

class MedicineAccount
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
    private $__beneficiaryMedicineDocumentModel;
    
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
    private $_medicineData;
    private $_medicineEntries;
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

        $this->_medicineEntry = new BeneficiaryMedicine();
        $this->__beneficiaryMedicineDocumentModel =
            new BeneficiaryMedicineDocument();

        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * load membership
     */
    public function load()
    {
        $this->_sql->whereId('id', $this->_id);
       
        $this->_entries = $this->_medicineEntry->select($this->_sql->getWhere());
        $this->_medicineData = $this->_medicineEntry->find($this->_id);
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id);
        $this->_where = $sql->getWhere();
        
       // $this->_loadPhoto();
        $this->_loadDocuments();
        $this->_loadLookups();
    }
    
    public function loadMedicineData()
    {
        $this->_sql->whereId('beneficiary_id', $this->_id);
        $this->_medicineEntries = $this->_medicineEntry->select($this->_sql->getWhere());
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id);
        $this->_where = $sql->getWhere();
        
       // $this->_loadPhoto();
        $this->_loadDocuments();
        $this->_loadLookups();
    }
    public function medicineEntries()
    {
         return $this->_medicineEntries;
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
    public  function medicineData()
    {        
        return $this->_medicineData;
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
        if ($this->__beneficiaryMedicineDocumentModel->selectTotal($this->_where) > 0) {
            $this->_documents = $this->__beneficiaryMedicineDocumentModel->select($this->_where);
        }
    }
    
    /**
     * load lookups
     */
    private function _loadLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('documentType', $pairModel->documentType());
        
        $bankModel = new Bank();
        LookupStore::register('banks', $bankModel->selectIndexed(null, 'bank'));
    }
    
    
}
