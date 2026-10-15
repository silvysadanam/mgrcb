<?php
/**
 * Shared Staffship Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Staff, Fmc, DesignationType, StaffCard, StaffDocument, StaffPhoto, Account};
use Model\Bank;

use Shared\Model\PairModel;
use Shared\Constant\StaffType;
use Shared\Constant\StaffshipType;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class StaffUtility
{
    /**
     *
     * @var int 
     */
    private $_id = 0;
    
    /**
     * 
     * @var \Model\Staff
     */
    private $_model;
    
    /**
     * 
     * @var \Model\StaffPhoto
     */
    private $_memberPhotoModel;
    
    /**
     * 
     * @var \Model\StaffCard
     */
    private $_memberCardModel;
    
    /**
     * 
     * @var \Model\StaffDocument
     */
    private $_memberDocumentModel;
    
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
    private $_designation;
    /**
     * constructor
     * 
     * @param int $id member id
     */
    public function __construct($id, $manager = true)
    {
        $this->_id = $id;
        $this->_manager = $manager;
        $this->_model = new Staff();
        $this->_member = $this->_model->find($this->_id);
        $this->_memberDocumentModel = new StaffDocument();
        $this->_memberPhotoModel = new StaffPhoto();
        $designationFields = ['id', 'designation_type'];
        $designationModel = new DesignationType();
        $this->_designation = $designationModel->selectFieldsById($designationFields, $this->_member->designation_type_id);
    }
    
    /**
     * load membership
     */
    public function load()
    {
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        $sql = $this->_model->getSql();
        $sql->whereId('staff_id', $this->_id);
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
    public function designationType()
    {
        return $this->_designation;
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
    
    /**
     * get member photo record
     * 
     * @return \stdClass
     */
    public function photo()
    {
        return $this->_photo;
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
     public function approvedDate()
    {
        if ('' != $this->_member->approved_date) {
            return DateHelper::datepicker($this->_member->approved_date);
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
        if ($this->_memberPhotoModel->selectTotal($this->_where) > 0) { 
            $this->_photo = $this->_memberPhotoModel->selectRow($this->_where);
        }
    }
    
  
    
    /**
     * load documents
     */
    private function _loadDocuments()
    {
        if ($this->_memberDocumentModel->selectTotal($this->_where) > 0) {
            $this->_documents = $this->_memberDocumentModel->select($this->_where);
        }
    }
    
    /**
     * load lookups
     */
    private function _loadLookups()
    {
        $pairModel = new PairModel();
        LookupStore::register('designationType', $pairModel->designationType());
        LookupStore::register('documentType', $pairModel->documentType());
        
        $bankModel = new Bank();
        LookupStore::register('banks', $bankModel->selectIndexed(null, 'bank'));
    }
    
    
}
