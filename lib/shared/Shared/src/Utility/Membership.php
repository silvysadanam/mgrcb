<?php
/**
 * Shared Membership Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Member, Fmc, Boat, MemberCard, MemberDocument, MemberPhoto, Account};
use Model\Bank;

use Shared\Model\PairModel;
use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class Membership
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
    private $_memberPhotoModel;
    
    /**
     * 
     * @var \Model\MemberCard
     */
    private $_memberCardModel;
    
    /**
     * 
     * @var \Model\MemberDocument
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
    
    /**
     * constructor
     * 
     * @param int $id member id
     */
    public function __construct($id, $manager = true)
    {
        $this->_id = $id;
        $this->_manager = $manager;
        $this->_model = new Member();
        $this->_member = $this->_model->find($this->_id);
        $this->_memberCardModel = new MemberCard();
        $this->_memberDocumentModel = new MemberDocument();
        $this->_boatModel = new Boat();
        $this->_memberPhotoModel = new MemberPhoto();
        $this->_accountModel = new Account();
    }
    
    /**
     * load membership
     */
    public function load()
    {
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->find($this->_member->fmc_id);
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id);
        $this->_where = $sql->getWhere();
        
        $this->_loadPhoto();
        $this->_loadAccounts();
        $this->_loadCards();
        $this->_loadBoats();
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
    
    public function membershipType()
    {
        $types = [
            MembershipType::LIFEMEMBER => 'Life Membership',
            MembershipType::ORDMEMBER => 'Ordinary membership',
        ];
        
        return $types[$this->_member->membership_type_id] ?? '';
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
     * get boats
     * 
     * @return array
     */
    public function boats()
    {
        return $this->_boats;
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
     * load accounts
     */
    private function _loadAccounts()
    {
        if ($this->_accountModel->selectTotal($this->_where) > 0) {
            $this->_accounts = $this->_accountModel->select($this->_where);
        }
    }
    
    /**
     * load cards
     */
    private function _loadCards()
    {
        if ($this->_memberCardModel->selectTotal($this->_where) > 0) {
            $this->_cards = $this->_memberCardModel->select($this->_where);
        }
    }
    
    /**
     * load boats
     */
    private function _loadBoats()
    {
        if ($this->_boatModel->selectTotal($this->_where) > 0) {
            $this->_boats = $this->_boatModel->select($this->_where);
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
        LookupStore::register('cardType', $pairModel->cardType());
        LookupStore::register('panchayathBrand', $pairModel->panchayathBrand());
        LookupStore::register('documentType', $pairModel->documentType());
        LookupStore::register('accountType', $pairModel->accountType());
        
        $bankModel = new Bank();
        LookupStore::register('banks', $bankModel->selectIndexed(null, 'bank'));
    }
    
    
}
