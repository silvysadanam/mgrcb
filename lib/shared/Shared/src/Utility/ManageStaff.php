<?php
/**
 * Shared ManageMembership Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Member, Staff, MemberCard, StaffDocument, StaffPhoto, Account};

use Shared\Form\AjaxForm;
use Shared\Utility\{MembershipValidator, DocumentDirectory, FileTypeDetector};

use Web\Form\{Form, FileUpload};
use Web\Utility\DateHelper;
use Web\Security\Password;


class ManageStaff
{
    const GET_MEMBER = 1;
    const SAVE_MEMBER = 2;
    const GET_CARD = 3;
    const SAVE_CARD = 4;
    const REMOVE_CARD = 5;
    const GET_BOAT = 6;
    const SAVE_BOAT = 7;
    const REMOVE_BOAT = 8;
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
     * @var \Model\StaffPhoto
     */
    private $_memberPhotoModel;
    
    /**
     * 
     * @var \Model\MemberCard
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
        $this->_model = new Staff();
        $this->_memberDocumentModel = new StaffDocument();
        $this->_memberPhotoModel = new StaffPhoto();
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
            'name',  'designation_type_id', 'email',
            'mobile', 'aadhar_number', 'pan_number', 'address', 'landline', 
            'whatsapp', 'approved_date',  
            'date_of_birth',
        ];
        
        $member = (array) $this->_model->selectFieldsById($fields, $this->_id);
        
        if (DateHelper::isDate($member['date_of_birth'])) {
            $member['date_of_birth'] = DateHelper::datepicker($member['date_of_birth']);
        }
        
        if (DateHelper::isDate($member['approved_date'])) {
            $member['approved_date'] = DateHelper::datepicker($member['approved_date']);
        }
        
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
            'name',  'designation_type_id', 'email',
            'mobile', 'aadhar_number', 'pan_number', 'address', 'landline', 
            'whatsapp', 'approved_date',  
            'date_of_birth',
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
     * get document
     * 
     * @return null
     */
    private function _getDocument()
    {
        if (! $this->_checkId($this->_memberDocumentModel)) {
            return;
        }
                
        $fields = ['document_type_id', 'description'];
        $this->_setResult($this->_memberDocumentModel->selectFieldsById($fields, $this->_formValues['id']));
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
            if (! $this->_memberDocumentModel->hasId($documentId)) {
                return;
            }
            
            if ($this->_id != $this->_memberDocumentModel->selectColumnById('staff_id', $documentId)) {
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
            $current = $this->_memberDocumentModel->find($documentId);
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
            
            $this->_memberDocumentModel->updateById($document, $documentId);
            $this->_saved('Document', true);
            
            return;
        }
        
        $document['staff_id'] = $this->_id;
        $document['document_type_id'] = $documentTypeId;
        $document['description'] = $description;
        $document['created_at'] = DateHelper::timestamp();
        $this->_memberDocumentModel->insert($document);
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
        
        if (! $this->_checkMemberId($this->_memberDocumentModel)) {
            return;
        }

        if ($this->_membershipValidator->notSelected($this->_formValues['document_type_id'])) {
            $this->_response->message('Document Type not selected');
            return;
        }
        
        $update = [];
        $current = $this->_memberDocumentModel->find($this->_formValues['id']);
        foreach ($fields as $field) {
            if ($this->_formValues[$field] != $current->{$field}) {
                $update[$field] = $this->_formValues[$field];
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $this->_memberDocumentModel->updateById($update, $this->_formValues['id']);
        $this->_saved('Document', true);
    }
    
    /**
     * remove document
     * 
     * @return null
     */
    private function _removeDocument()
    {
        $this->_remove($this->_memberDocumentModel, 'Document');
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
                    if ($this->_memberPhotoModel->checkByColumnId('staff_id', $this->_id)) {
                        $currentId = $this->_memberPhotoModel->selectIdByColumn('staff_id', $this->_id);
                        $update = [
                            'file_path' => $path,
                            'filename' => $upload->getUploadedFilename(),
                        ];
                        $this->_memberPhotoModel->updateById($update, $currentId);
                    } else {
                        $record = [
                            'staff_id' => $this->_id,
                            'file_path' => $path,
                            'filename' => $upload->getUploadedFilename(),
                        ];
                        $this->_memberPhotoModel->insert($record);
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
        
        $insert = [ 'staff_id' => $this->_id ];
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
        
        return $this->_id == $model->selectColumnById('staff_id', $this->_formValues['id']);
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
    
    /**
     * check member item id
     * 
     * @param \Web\Database\TableGateway $model
     * @return boolean
     */
    private function _checkId($model)
    {
        $this->_loadAjaxForm();
        if (! $this->_checkFormId()) {
            return false;
        }

        if (! $this->_checkMemberId($model)) {
            return false;
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
    
    
}
