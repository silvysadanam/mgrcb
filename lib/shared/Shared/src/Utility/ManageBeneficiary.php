<?php
/**
 * Shared ManageMembership Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Member, Beneficiary, BeneficiaryEntry, MemberCard, BeneficiaryDocument, BeneficiaryPhoto, Account};

use Shared\Form\AjaxForm;
use Shared\Utility\{MembershipValidator, DocumentDirectory, FileTypeDetector};

use Web\Form\{Form, FileUpload};
use Web\Utility\DateHelper;
use Web\Security\Password;
use Shared\Model\PairModel;
use Web\View\Support\LookupStore;

class ManageBeneficiary
{
    const GET_MEMBER = 1;
    const SAVE_MEMBER = 2;
    const GET_CARD = 3;
    const SAVE_CARD = 4;   
    const GET_ENTRY = 5;
    const SAVE_ENTRY = 6;
    const DELETE_ENTRY = 7;
    const SAVE_ADVANCE = 8;
    const GET_DOCUMENT = 9;
    const SAVE_DOCUMENT_WITH_FILE = 10;
    const SAVE_DOCUMENT_WITHOUT_FILE = 11;
    const REMOVE_DOCUMENT = 12;
    const SAVE_PHOTO = 13;
    const GET_ACCOUNT = 14;
    const SAVE_ACCOUNT = 15;
    const REMOVE_ACCOUNT = 16;
    const SET_PASSWORD = 17;
    
    const DISABLE = 18;
    const ACTIVATE = 19;

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
     * @var \Shared\Utility\MembershipValidator
     */
    private $_membershipValidator;
    
    /**
     * 
     * @var array
     */
    private $_fields;
    
    /**
     * 
     * @var \Web\Mvc\AjaxResponse
     */
    private $_response;
    
    /**
     * 
     * @var array
     */
    private $_result = [];
    
    /**
     * 
     * @var \Shared\Form\AjaxForm
     */
    private $_form;
    
    /**
     * 
     * @var array
     */
    private $_formValues = [];
    
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
        $this->_model = new Beneficiary();
        $this->_memberCardModel = new MemberCard();
        $this->_beneficiaryDocumentModel = new BeneficiaryDocument();
        $this->_member = $this->_model->find($this->_id);
        $this->_memberEntry = new BeneficiaryEntry();
        $this->_beneficiaryPhotoModel = new BeneficiaryPhoto();
        $this->_accountModel = new Account();
        $this->_membershipValidator = new MembershipValidator();
    }
    
    /**
     * manage membership
     * 
     * @param \Web\Mvc\AjaxResponse $response
     * @param int $action
     * @return array
     */
    public function run($response, $action)
    {
        $this->_response = $response;
        $this->_response->message('Invalid request');
        
        switch ($action) {
            case self::GET_MEMBER:
                $this->_getMember();
                break;
            
            case self::SAVE_MEMBER:
                $this->_saveMember();
                break;
            
            case self::GET_CARD:
                $this->_getCard();
                break;
            
            case self::SAVE_CARD:
                $this->_saveCard();
                break;            
                       
            case self::GET_ENTRY:
                $this->_getEntry();
                break;
            
            case self::SAVE_ENTRY:
                $this->_saveEntry();
                break;
            
            case self::DELETE_ENTRY:
                $this->_deleteEntry();
                break;
            
            case self::GET_DOCUMENT:
                $this->_getDocument();
                break;
            
            case self::SAVE_DOCUMENT_WITH_FILE:
                $this->_saveDocumentWithFile();
                break;
            
            case self::SAVE_DOCUMENT_WITHOUT_FILE:
                $this->_saveDocumentWithoutFile();
                break;
            
            case self::REMOVE_DOCUMENT:
                $this->_removeDocument();
                break;
            
            case self::SAVE_PHOTO:
                $this->_savePhoto();
                break;
            
            case self::GET_ACCOUNT:
                $this->_getAccount();
                break;
            
            case self::SAVE_ACCOUNT:
                $this->_saveAccount();
                break;
            
            case self::REMOVE_ACCOUNT:
                $this->_removeAccount();
                break;
            
            case self::SET_PASSWORD:
                $this->_setPassword();
                break;
            
            case self::DISABLE:
                $this->_inactive();
                break;
            
            case self::ACTIVATE:
                $this->_active();
                break;
            
            default:
                break;
        }
        
        return $this->_result;
    }
    
    /**
     * get member details
     */
    private function _getMember()
    {
        $fields = [
            'name', 'fmc_id', 'gender', 'email',
            'mobile', 'aadhar_number', 'beducation', 'monthly_income', 'address', 
            'ration_card', 'disability_occurred', 'pensions',
            'approved_date', 'date_of_birth', 'disability_reason', 'examination_date', 'disability', 'land_area', 'house_type', 
            'rented_amount', 'house_ownership', 'worker_opinion', 'worker_name', 'executive_member', 'panchayath', 'proposed_date',
        ];
        
        $member = (array) $this->_model->selectFieldsById($fields, $this->_id);
        $diseaseTypes = $this->registerPairs(['diseaseTypes'], true);
       // $diseaseTypes = $this->view->lookupStore('diseaseTypes');
        if (DateHelper::isDate($member['date_of_birth'])) {
            $member['date_of_birth'] = DateHelper::datepicker($member['date_of_birth']);
        }
        
        if (DateHelper::isDate($member['examination_date'])) {
            $member['examination_date'] = DateHelper::datepicker($member['examination_date']);
        }
        if (DateHelper::isDate($member['proposed_date'])) {
            $member['proposed_date'] = DateHelper::datepicker($member['proposed_date']);
        }
        
         if (DateHelper::isDate($member['approved_date'])) {
            $member['approved_date'] = DateHelper::datepicker($member['approved_date']);
        }
        $ids = ($member['disability'] !== null && $member['disability'] !== '')
            ? array_filter(array_map('trim', explode(',', $member['disability'])))
            : [];

        $names = [];

        foreach ($ids as $id) {
            if (isset($diseaseTypes[$id])) {
                $names[] = $diseaseTypes[$id];
            }
        }

        // For edit modal - return option values/IDs
        $member['disability'] = array_values($ids);

        $this->_setResult($member);
        $this->_valid();
    }
    
    /**
     * save member
     * 
     * @return null
     */
    private function _saveMember()
    {
        $fields = [
            'name', 'fmc_id', 'gender', 'email',
            'mobile', 'aadhar_number', 'beducation', 'monthly_income', 'address', 
            'ration_card', 'disability_occurred', 'pensions',
            'approved_date', 'date_of_birth', 'disability_reason', 'disability', 'examination_date', 'land_area', 'house_type', 
            'rented_amount', 'house_ownership', 'worker_opinion', 'worker_name', 'executive_member', 'panchayath', 'proposed_date',
        ];
        
        if ($this->_manager) {
            $fields[] = 'fmc_id';
        }
        
        $this->_loadAjaxForm($fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        if (! $this->_validateMember()) {
            return;
        }
        $this->_formValues['disability'] = $_POST['disability'] ?? [];
        $update = [];
        $setNull = [];
        $member = $this->_model->selectFieldsById($fields, $this->_id);
        foreach ($fields as $field) {
            if ('date_of_birth' == $field) {
                if ('' == $this->_formValues['date_of_birth']) {
                    if ('' != $member->date_of_birth) {
                        $setNull[] = 'date_of_birth';
                    }
                } else {
                    $date = DateHelper::fromDatepicker($this->_formValues['date_of_birth']);
                    if ($date != $member->date_of_birth) {
                        $update['date_of_birth'] = $date;
                    }
                }
            }
            else if ('approved_date' == $field) {
                if ('' == $this->_formValues['approved_date']) {
                    if ('' != $member->approved_date) {
                        $setNull[] = 'approved_date';
                    }
                } else {
                    $date = DateHelper::fromDatepicker($this->_formValues['approved_date']);
                    if ($date != $member->approved_date) {
                        $update['approved_date'] = $date;
                    }
                }
            }
            else if ('examination_date' == $field) {
                if ('' == $this->_formValues['examination_date']) {
                    if ('' != $member->examination_date) {
                        $setNull[] = 'examination_date';
                    }
                } else {
                    $date = DateHelper::fromDatepicker($this->_formValues['examination_date']);
                    if ($date != $member->examination_date) {
                        $update['examination_date'] = $date;
                    }
                }
            }
            else if ('proposed_date' == $field) {
                if ('' == $this->_formValues['proposed_date']) {
                    if ('' != $member->proposed_date) {
                        $setNull[] = 'proposed_date';
                    }
                } else {
                    $date = DateHelper::fromDatepicker($this->_formValues['proposed_date']);
                    if ($date != $member->proposed_date) {
                        $update['proposed_date'] = $date;
                    }
                }
            }
            else if ('disability' == $field) {
                $raw = $this->_formValues['disability'] ?? [];
                if (is_string($raw)) {
                    $raw = ('' === trim($raw)) ? [] : explode(',', $raw);
                } elseif (! is_array($raw)) {
                    $raw = [];
                }
                $ids = array_unique(array_filter(array_map('intval', $raw)));
                $newValue = implode(',', $ids);

                if ($newValue != $member->disability) {
                    $update['disability'] = $newValue;
                }
            }
            else {
                if ($this->_formValues[$field] != $member->{$field}) {
                    $update[$field] = $this->_formValues[$field];
                }
            }
        }
        $update['fmc_id'] = 1;
        if (empty($update) && empty($setNull)) {
            $this->_noChange();
            return;
        }
        if (! empty($update)) {
            $this->_model->updateById($update, $this->_id);
        }
        
        if (! empty($setNull)) {
            $this->_model->setNullById($setNull, $this->_id);
        }
        
        $this->_saved('Member details', true);
    }
    
    /**
     * validate member
     * 
     * @return boolean
     */
    private function _validateMember()
    {
        $errors = [];

        if ($this->_membershipValidator->isNull($this->_formValues['name'])) {
            $errors[] = 'Name is required';
        }        
        
        
        if (! $this->_membershipValidator->mobile($this->_formValues['mobile'])) {
            $errors[] = 'Invalid Mobile';
        }
        
        if (! $this->_membershipValidator->aadhar($this->_formValues['aadhar_number'])) {
            $errors[] = 'Invalid Aadhar Number';
        }
              
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * get card
     * 
     * @return null
     */
    private function _getCard()
    {
        if (! $this->_checkId($this->_memberCardModel)) {
            return;
        }
        
        $this->_setResult($this->_memberCardModel->find($this->_formValues['id']));
        $this->_valid();
    }
    
    /**
     * save card
     * 
     * @return null
     */
    private function _saveCard()
    {
        $this->_fields = [ 'card_type_id', 'card_number', 'issue_date', 'expiry_date' ];
        
        $this->_loadAjaxForm($this->_fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        if ($this->_formValues['id'] > 0) {
            $this->_updateCard();
            return;
        }
        
        if (! $this->_validateCard()) {
            return;
        }
        
        $insert = [ 'member_id' => $this->_id ];
        foreach ($this->_fields as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        $this->_memberCardModel->insert($insert);
        $this->_saved('Card Number');
    }
    
    /**
     * validate card
     * 
     * @return boolean
     */
    private function _validateCard()
    {
        $errors = [];

        if ($this->_membershipValidator->notSelected($this->_formValues['card_type_id'])) {
            $errors[] = 'Type not selected';
        }
        
        if ($this->_membershipValidator->isNull($this->_formValues['card_number'])) {
            $errors[] = 'Number is required';
        }
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * update card
     * 
     * @return null
     */
    private function _updateCard()
    {
        if (! $this->_checkMemberId($this->_memberCardModel)) {
            return;
        }
            
        if (! $this->_validateCard()) {
            return;
        }
        
        $card = $this->_memberCardModel->find($this->_formValues['id']);
        $update = [];
        foreach ($this->_fields as $field) {
            if ($this->_formValues[$field] != $card->{$field}) {
                $update[$field] = $this->_formValues[$field];
            }
        }

        if (empty($update)) {
            $this->_noChange();
            return;
        }

        $this->_memberCardModel->updateById($update, $this->_formValues['id']);
        $this->_saved('Card Number', true);
    }
    
    /**
     * remove card
     * 
     * @return null
     */
    private function _removeCard()
    {
        $this->_remove($this->_memberCardModel, 'Card Number');
    }
   
    
      
    /**
     * get document
     * 
     * @return null
     */
    private function _getDocument()
{
    $this->_loadAjaxForm([
        'id'
    ]);

    if (! $this->_checkId()) {
        return;
    }

    $fields = ['document_type_id', 'description'];

    $this->_setResult(
        $this->_beneficiaryDocumentModel->selectFieldsById(
            $fields,
            $this->_formValues['id']
        )
    );

    $this->_valid();
}

    
    /**
     * save document with file
     * 
     * @return null
     */
    private function _saveDocumentWithFile()
    {
        $form = new Form();
        if (! $form->isPost()) {
            return;
        }
        
        $documentId = $form->post('document_id');
        $documentTypeId = $form->post('document_type_id');
        $description = $form->post('description');
        
        $options = [
            'required' => true,
            'custom1' => true,
            'size' => 10485760,
        ];
        
        if ($documentId > 0) {
            if (! $this->_beneficiaryDocumentModel->hasId($documentId)) {
                return;
            }
            
            if ($this->_id != $this->_beneficiaryDocumentModel->selectColumnById('member_id', $documentId)) {
                return;
            }
        }
        
        if ($this->_membershipValidator->notSelected($documentTypeId)) {
            $this->_response->message('Document Type not selected');
            return;
        }
        
        
        $document = [];
        $upload = new FileUpload('document', 'Document');
        if ($upload->validate($options)) {
            if ($upload->hasFile()) {
                $directory = new DocumentDirectory();
                $path = $directory->run($this->_id);
                $upload->setUploadPath(UPLOAD_DIR . '/documents');
                if ($upload->upload($path)) {
                    $fileType = new FileTypeDetector();
                    $document['filename'] = $upload->getUploadedFilename();
                    $document['original_filename'] = $upload->getRawFilename();
                    $document['file_size'] = $upload->getUploadedFileSize();
                    $document['file_type_id'] = $fileType->getFromFilename($document['filename']);
                    $document['file_path'] = $path;
                } else {
                    $this->_response->message('File upload failed');
                    return;
                }
            } else {
                $this->_response->message('No file uploaded');
                return;
            }
        } else {
            $this->_response->message($upload->getError());
            return;
        }
        
        if ($documentId > 0) {
            $current = $this->_beneficiaryDocumentModel->find($documentId);
            if ($current->document_type_id != $documentTypeId) {
                $document['document_type_id'] = $documentTypeId;
            }
            if ($current->description != $description) {
                $document['description'] = $description;
            }
            
            if (empty($document)) {
                $this->_noChange();
                return;
            }
            
            $this->_beneficiaryDocumentModel->updateById($document, $documentId);
            $this->_saved('Document', true);
            
            return;
        }
        
        $document['member_id'] = $this->_id;
        $document['document_type_id'] = $documentTypeId;
        $document['description'] = $description;
        $document['created_at'] = DateHelper::timestamp();
        $this->_beneficiaryDocumentModel->insert($document);
        $this->_saved('Document');
    }
    
    /**
     * save document without file
     * 
     * @return null
     */
    private function _saveDocumentWithoutFile()
    {
        $fields = ['document_type_id', 'description'];
        $this->_loadAjaxForm($fields);
        
        if (empty($this->_formValues)) {
            return;
        }
        
        if (! $this->_checkFormId()) {
            return;
        }
        
        if (! $this->_checkMemberId($this->_beneficiaryDocumentModel)) {
            return;
        }

        if ($this->_membershipValidator->notSelected($this->_formValues['document_type_id'])) {
            $this->_response->message('Document Type not selected');
            return;
        }
        
        $update = [];
        $current = $this->_beneficiaryDocumentModel->find($this->_formValues['id']);
        foreach ($fields as $field) {
            if ($this->_formValues[$field] != $current->{$field}) {
                $update[$field] = $this->_formValues[$field];
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $this->_beneficiaryDocumentModel->updateById($update, $this->_formValues['id']);
        $this->_saved('Document', true);
    }
    
    /**
     * remove document
     * 
     * @return null
     */
    private function _removeDocument()
    {
        $this->_remove($this->_beneficiaryDocumentModel, 'Document');
    }
    
    /**
     * save photo
     * 
     * @return null
     */
    private function _savePhoto()
    {
        $form = new Form();
        if (! $form->isPost()) {
            return;
        }
        
        $options = [
            'required' => true,
            'custom2' => true,
            'size' => 10485760,
        ];
        
        
        $upload = new FileUpload('photo', 'Photo');
        if ($upload->validate($options)) {
            if ($upload->hasFile()) {
                $directory = new DocumentDirectory();
                $path = $directory->run($this->_id);
                $upload->setUploadPath(UPLOAD_DIR . '/documents');
                if ($upload->upload($path)) {
                    if ($this->_beneficiaryPhotoModel->checkByColumnId('member_id', $this->_id)) {
                        $currentId = $this->_beneficiaryPhotoModel->selectIdByColumn('member_id', $this->_id);
                        $update = [
                            'file_path' => $path,
                            'filename' => $upload->getUploadedFilename(),
                        ];
                        $this->_beneficiaryPhotoModel->updateById($update, $currentId);
                    } else {
                        $record = [
                            'member_id' => $this->_id,
                            'file_path' => $path,
                            'filename' => $upload->getUploadedFilename(),
                        ];
                        $this->_beneficiaryPhotoModel->insert($record);
                    }
                    
                    $fileType = new FileTypeDetector();
                    $document['filename'] = $upload->getUploadedFilename();
                    $document['original_filename'] = $upload->getRawFilename();
                    $document['file_size'] = $upload->getUploadedFileSize();
                    $document['file_type_id'] = $fileType->getFromFilename($document['filename']);
                } else {
                    $this->_response->message('File upload failed');
                    return;
                }
            } else {
                $this->_response->message('No file uploaded');
                return;
            }
        } else {
            $this->_response->message($upload->getError());
            return;
        }
       
        $this->_valid('Photo uploaded', true);
    }
    
    /**
     * get account
     * 
     * @return null
     */
    private function _getAccount()
    {
        if (! $this->_checkId($this->_accountModel)) {
            return;
        }
        
        $this->_setResult($this->_accountModel->find($this->_formValues['id']));
        $this->_valid();
    }
    
    /**
     * save account
     * 
     * @return null
     */
    private function _saveAccount()
    {
        $this->_fields = [ 'bank_id', 'account_number', 'account_type_id', 'description' ];
        $this->_loadAjaxForm($this->_fields);
        
        if (empty($this->_formValues)) {
            return;
        }

        if ($this->_formValues['id'] > 0) {
            $this->_updateAccount();
            return;
        }
 
        if (! $this->_validateAccount()) {
            return;
        }
        
        $insert = [ 'member_id' => $this->_id ];
        foreach ($this->_fields as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        
        $this->_accountModel->insert($insert);
        $this->_saved('Bank Account');
    }
    
    /**
     * update account
     * 
     * @return null
     */
    private function _updateAccount()
    {
        if (! $this->_checkMemberId($this->_accountModel)) {
            return;
        }
        
        if (! $this->_validateAccount()) {
            return;
        }
        
        $account = $this->_accountModel->find($this->_formValues['id']);
        $update = [];
        foreach ($this->_fields as $field) {
            if ($this->_formValues[$field] != $account->{$field}) {
                $update[$field] = $this->_formValues[$field];
            }
        }

        if (empty($update)) {
            $this->_noChange();
            return;
        }

        $this->_accountModel->updateById($update, $this->_formValues['id']);
        $this->_saved('Bank Account', true);
    }
    
    /**
     * remove account
     * 
     * @return null
     */
    private function _removeAccount()
    {
        $this->_remove($this->_accountModel, 'Bank Account');
    }
    
    /**
     * validate account
     * 
     * @return boolean
     */
    private function _validateAccount()
    {
        $errors = [];
        
        if ($this->_membershipValidator->isNull($this->_formValues['account_number'])) {
            $errors[] = 'Account Number is required';
        } elseif (! $this->_membershipValidator->accountNumber($this->_formValues['account_number'])) {
            $errors[] = 'Account Number is invalid';
        }
        
        if ($this->_membershipValidator->notSelected($this->_formValues['bank_id'])) {
            $errors[] = 'Bank not selected';
        }
        
        if ($this->_membershipValidator->notSelected($this->_formValues['account_type_id'])) {
            $errors[] = 'Account type not selected';
        }
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * set password
     * 
     * @return null
     */
    private function _setPassword()
    {
        $fields = ['password1', 'password2'];
        
        $this->_loadAjaxForm($fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        if (! $this->_validatePassword()) {
            return;
        }
        
        $update = [
            'access_key' => Password::encrypt($this->_formValues['password1']),
        ];
        
        $this->_model->updateById($update, $this->_id);
        $this->_saved('Password', true);
    }
    
    /**
     * validate password
     * @return boolean
     */
    private function _validatePassword()
    {
        if ($this->_membershipValidator->isNull($this->_formValues['password1'])) {
            $this->_setError(['Password is required']);
            return false;
        }
        
        if ($this->_membershipValidator->isNull($this->_formValues['password2'])) {
            $this->_setError(['Repeat Password is required']);
            return false;
        }
        
        if ($this->_formValues['password2'] != $this->_formValues['password1']) {
            $this->_setError(['Password and Repeat Password are different']);
            return false;
        }
        
        if (! $this->_membershipValidator->password($this->_formValues['password1'])) {
            $this->_setError(['Invalid Password format']);
            return false;
        }
        
        return true;
    }
    
    /**
     * check form id
     * 
     * @return boolean
     */
    private function _checkFormId()
    {
        if (empty($this->_formValues)) {
            return false;
        }
        
        return $this->_formValues['id'] > 0;
    }
    
    /**
     * check member id of item
     * 
     * @param  \Web\Database\TableGateway $model
     * @return boolean
     */
    private function _checkMemberId($model)
    {
        if (! $model->hasId($this->_formValues['id'])) {
            return false;
        }
        
        return $this->_id == $model->selectColumnById('member_id', $this->_formValues['id']);
    }
    
    
    /**
     * remove membership item
     * 
     * @param \Web\Database\TableGateway $model
     * @param string $title
     */
    private function _remove($model, $title)
    {
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return;
        }
        
        if (! $this->_checkMemberId($model)) {
            return;
        }
        
        $model->deleteById($this->_formValues['id']);
        $this->_deleted($title);
    }
    
   private function _checkId()
    {
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return false;
        }

        if (! $this->_checkEntryId()) {
           // return false;
        }
        
        return true;
    }
    
    /**
     * load ajax form
     * 
     * @param array $fields
     */
    private function _loadAjaxForm($fields = [])
    {
        $this->_form = new AjaxForm();
        if (! empty($fields)) {
            $this->_form->addFields($fields);
        }
        
        if ($this->_form->isPost()) {
            $this->_formValues = $this->_form->run();
        }
    }
    
    /**
     * set response valid
     * 
     * @param string $message
     * @param boolean $flash
     */
    private function _valid($message = 'success', $flash = false)
    {
        $this->_response->valid()
                        ->message($message);
        if ($flash) {
            $this->_result['flash'] = $message;
        }
    }
    
    /**
     * set result data
     * 
     * @param \stdClass $result
     */
    private function _setResult($result)
    {
        $this->_response->addData($result, 'result');
    }
    
    /**
     * set response error
     * 
     * @param array $errors
     */
    private function _setError($errors)
    {
        $this->_response->message(implode('<br>', $errors));
    }
    
    /**
     * set response no changes
     */
    private function _noChange()
    {
        $this->_response->message('No changes');
    }
    
    /**
     * set saved message
     * 
     * @param string $title
     * @param boolean $update
     */
    private function _saved($title, $update = false)
    {
        $message = $title . ' ';
        if ($update) {
            $message .= 'updated';
        } else {
            $message .= 'added';
        }
        
        $this->_valid($message, true);
    }
    
    /**
     * set deleted message
     * 
     * @param string $title
     */
    private function _deleted($title)
    {
        $this->_valid($title . ' deleted', true);
    }
    
    /**
     * set member inactive
     * 
     * @return null
     */
    private function _inactive()
    {
        $fields = ['note'];
        
        $this->_loadAjaxForm($fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        $member = $this->_model->find($this->_id);
        $log = [];
        if (! empty($member->inactive_log)) {
            $log = unserialize($member->inactive_log);
        }
        
        $log[] = [
            'disabled' => 1,
            'date' => $this->_formValues['id'],
            'note' => $this->_formValues['note'],
            'timestamp' => DateHelper::timestamp(),
        ];
        
        $update = [
            'disabled' => 1,
            'inactive_date' => DateHelper::fromDatepicker($this->_formValues['id']),
            'inactive_note' => $this->_formValues['note'],
            'inactive_log' => serialize($log),
        ];
        
        $this->_model->updateById($update, $this->_id);
        $this->_valid('Member status changed to "Inactive"', true);
    }
    
    /**
     * set member active
     * 
     * @return null
     */
    private function _active()
    {
        $this->_loadAjaxForm();
        if (empty($this->_formValues)) {
            return;
        }
        
        $member = $this->_model->find($this->_id);
        $log = [];
        if (! empty($member->inactive_log)) {
            $log = unserialize($member->inactive_log);
        }
        
        $log[] = [
            'disabled' => 0,
            'date' => DateHelper::datepicker(),
            'timestamp' => DateHelper::timestamp(),
        ];
        
        $update = [
            'disabled' => 0,
            'inactive_note' => '',
            'inactive_log' => serialize($log),
        ];
        
        $this->_model->updateById($update, $this->_id);
        $this->_valid('Member status changed to "Active"', true);
    }
    
     
    /**
     * get entry details
     */
    private function _getEntry()
    {
        if (! $this->_checkId()) {
            return;
        }
        
        $fields = [ 'family_name', 'family_relation', 'family_category', 'family_age', 'family_education', 'family_disability', 'family_occupation', 'family_income' ];
        $this->_setResult($this->_memberEntry->selectFieldsById($fields, $this->_formValues['id']));
        $this->_valid();
    }
    
    /**
     * save entry
     * 
     * @return null
     */
    private function _saveEntry()
    {
        $this->_fields = [ 'family_name', 'family_relation', 'family_category', 'family_age', 'family_education', 'family_disability', 'family_occupation', 'family_income' ];
        
        $this->_loadAjaxForm($this->_fields);
        if (empty($this->_formValues)) {
            return;
        }
        
        if ($this->_formValues['id'] > 0) {
            $this->_updateEntry();
            return;
        }
        
        $this->_addEntry();
    }
    
    /**
     * update entry
     * 
     * @return null
     */
    private function _updateEntry()
    {
        
        if (! $this->_checkEntryId()) {
            return;
        }
            
        if (! $this->_validateEntry()) {
            return;
        }
        
        $entry = $this->_memberEntry->find($this->_formValues['id']);
        $auctionChit = $this->_model->find($this->_id);
       
        $update = [];
        foreach ($this->_fields as $field) {
            $value =  $this->_formValues[$field];
            if ($value != $entry->{$field}) {
                $update[$field] = $value;
            }
        }
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $update['updated_at'] = DateHelper::timestamp();       

        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_memberEntry->updateById($update, $this->_formValues['id']);
            $this->_model->getAdapter()->commit();
            $this->_saved('Entry', true);
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    private function _addEntry()
    {
        if (! $this->_validateEntry()) {
            return;
        }
        
        $now = DateHelper::timestamp();        
        $insert = [
            'beneficiary_id' => $this->_id,
            'family_name' => $this->_formValues['family_name'],
            'family_relation' => $this->_formValues['family_relation'],
            'family_category' => $this->_formValues['family_category'],
            'family_age' => $this->_formValues['family_age'],
            'family_education' => $this->_formValues['family_education'],
            'family_disability' => $this->_formValues['family_disability'],
            'family_income' => $this->_formValues['family_income'],
            'created_at' => $now,
            'updated_at' => $now,
        ];
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_memberEntry->insert($insert);
            $this->_model->getAdapter()->commit();
            $this->_saved('Entry', false);
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * validate entry
     * 
     * @return boolean
     */
    private function _validateEntry()
    {
        $errors = [];

        if ('' == $this->_formValues['family_name']) {
            $errors[] = 'Please provide Name';
        }
        if ('' == $this->_formValues['family_relation']) {
            $errors[] = 'Please provide Name';
        }
        if ('' == $this->_formValues['family_category']) {
            $errors[] = 'Please provide Gender';
        }
       if ('' == $this->_formValues['family_age']) {
            $errors[] = 'Please provide Age';
        }
        
        if (empty($errors)) {
            return true;
        }
        
        $this->_setError($errors);
        
        return false;
    }
    
    /**
     * delete entry
     * 
     * @return null
     */
    private function _deleteEntry()
    {
        if (! $this->_checkId()) {
            return;
        }
        
       /* if ($this->_memberEntry->selectTotal(['beneficiary_id' => $this->_id]) < 2) {
            $this->_setError(['Single entry cannot be deleted']);
            return;
        }
        */
        $entry = $this->_memberEntry->find($this->_formValues['id']);
        $auctionChit = $this->_model->find($this->_id);        
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            $this->_memberEntry->deleteById($this->_formValues['id']);
            $this->_model->getAdapter()->commit();
            $this->_deleted('Entry');
        } catch (\PDOException $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Web\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        } catch (\Exception $ex) {
            $this->_model->getAdapter()->rollBack();
            $this->_response->addData($ex->getMessage(), 'debug')
                            ->message('Action failed');
        }
    }
    
    /**
     * check entry id
     * 
     * @return boolean
     */
    private function _checkEntryId()
    {
        if (! $this->_memberEntry->hasId($this->_formValues['id'])) {
            return false;
        }
        
        return $this->_id == $this->_memberEntry->selectColumnById('beneficiary_id', $this->_formValues['id']);
    }
    
    protected function registerPairs($list = ['fmc'])
    {
        $pairModel = new PairModel;
        // fmc        
        if (in_array('diseaseTypes', $list)) {
            $pairModel = new PairModel();
            LookupStore::register('diseaseTypes', $pairModel->diseaseTypes());
        }

        
    }
}
