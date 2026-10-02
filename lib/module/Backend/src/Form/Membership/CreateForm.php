<?php
/**
 * Backend Membership Create Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Membership
 */

namespace Backend\Form\Membership;

use Backend\Form\{DocumentForm, PhotoForm};

use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{MemberCard, Boat, MemberDocument, DocumentType, MemberPhoto, Account};

use Shared\Utility\{DocumentDirectory, FileTypeDetector, MembershipValidator};


class CreateForm extends Form
{
    /**
     * 
     * @var \Shared\Utility\MembershipValidator
     */
    private $_memberValidator;
    
    /**
     * 
     * @var int
     */
    private $_memberId;
    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'name', 'parish', 'member_type_id', 'email',
            'mobile', 'aadhar_number', 'pan_number', 'address', 'landline', 
            'whatsapp', 'approved_date', 'code',
            'date_of_birth', 'location', 'membership_fee', 'membership_type_id',
        ];
        $multiple = [
            'card_number', 'issue_date', 'expiry_date', 
            'document_description',
        ];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
        $this->_memberValidator = new MembershipValidator();
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();      
        if ($this->validator->isNull($this->_formValues['name'])) {
            $this->error->required('Name');
        }
       if (
            $this->_formValues['membership_type_id'] === null ||
            $this->_formValues['membership_type_id'] === '' ||
            (int) $this->_formValues['membership_type_id'] === 0
        ) {
            $this->error->required('Membership Type');
        }
        if ($this->validator->isNull($this->_formValues['code'])) {
            $this->error->required('Code');
        } elseif ($this->getModel()->checkByColumn('code', $this->_formValues['code'])) {
            $this->error->exists('Code');
        }
        
        if (! $this->_memberValidator->mobile($this->_formValues['mobile'])) {
            $this->error->invalid('Mobile');
        }
        return $this->noError();
    }
    
    /**
     * create member record
     * 
     * @return int
     */
    public function create()
    {
        $insert = ['created_at' => DateHelper::timestamp()];
        $direct = [
            'member_type_id', 'address', 'parish', 'mobile', 'aadhar_number',
            'email', 'approved_date', 'name', 'date_of_birth', 'landline', 'location',
            'whatsapp', 'pan_number', 'membership_fee','code','membership_type_id',
        ];
        
        foreach ($direct as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        
        if ('' != $this->_formValues['date_of_birth']) {
            $insert['date_of_birth'] = DateHelper::fromDatepicker($this->_formValues['date_of_birth']);
        }
        $insert['fmc_id'] = 1;
         
        if ('' != $this->_formValues['approved_date']) {
            $insert['approved_date'] = DateHelper::fromDatepicker($this->_formValues['approved_date']);
        }
        if (empty($insert['date_of_birth'])) {
            unset($insert['date_of_birth']);
        }
        if (empty($insert['approved_date'])) {
            unset($insert['approved_date']);
        }
        $this->_memberId = $this->save($insert);
        $this->_savePhoto();
        $this->_saveDocuments();
        return $this->_memberId;
    }
    
    private function _savePhoto()
    {
        $memberPhotoModel = new MemberPhoto();
        $upload = new PhotoForm('photo', 'Photo');
        if ($upload->isValid()) {
            if ($upload->hasFile()) {
                $directory = new DocumentDirectory();
                $directory->setBase('documents');
                $path = $directory->run($this->_memberId);
                $upload->setUploadPath(UPLOAD_DIR . '/documents');
                if ($upload->upload($path)) {
                    $record = [
                        'member_id' => $this->_memberId,
                        'file_path' => $path,
                    ];
                    $record['filename'] = $upload->getUploadedFilename();
                    $memberPhotoModel->insert($record);
                }
            }
        }
    }
    private function _saveDocuments()
    {
        $documentTypeModel = new DocumentType();
        $documentTypes = $documentTypeModel->selectIds();
        $fileType = new FileTypeDetector();
        $memberDocumentModel = new MemberDocument();
        $directory = new DocumentDirectory();
        $path = $directory->run($this->_memberId);
        $default = [
            'member_id' => $this->_memberId,
            'document_type_id' => 0,
            'description' => '',
            'original_filename' => '',
            'filename' => '',
            'file_path' => $path,
            'file_size' => '',
            'file_type_id' => 0,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $insert = $memberDocumentModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($documentTypes as $documentTypeId) {
            $upload = new DocumentForm('document_type_' . $documentTypeId);
            if ($upload->isValid()) {
                if ($upload->hasFile()) {
                    $upload->setUploadPath(UPLOAD_DIR . '/documents');
                    if ($upload->upload($path)) {
                        $record = $default;
                        $record['filename'] = $upload->getUploadedFilename();
                        $record['original_filename'] = $upload->getRawFilename();
                        $record['description'] = $this->_formValues['document_description'][$documentTypeId];
                        $record['file_size'] = $upload->getUploadedFileSize();
                        $record['document_type_id'] = $documentTypeId;
                        $record['file_type_id'] = $fileType->getFromFilename($record['filename']);
                        $insert->execute(array_values($record));
                    }
                }
            }
        }
    }
    
}
