<?php
/**
 * Branch Membership Create Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Membership
 */

namespace Branch\Form\Membership;

use Branch\Form\{DocumentForm, PhotoForm};

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
            'code', 'name', 'member_type_id', 'name_malayalam',
            'mobile', 'aadhar_number', 'aadhar_mobile', 'address', 'village', 
            'taluk', 'kerosene_distribution_center', 'preferred_inspection_location',
            'date_of_birth', 'bank_id', 'account_type_id', 'account_number',
            'description',
        ];
        $multiple = [
            'card_number', 'issue_date', 'expiry_date', 
            'document_description', 'registration_number', 'registration_date', 
            'panchayath_brand_id', 'horse_power', 'purchase_date', 'panchayath_number', 
            'thattumadi', 'fishing_license_number', 'fishing_license_date', 
            'pds_permit_number', 'matsyafed_permit_number',
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
        
        if ($this->validator->isNull($this->_formValues['code'])) {
            $this->error->required('Code');
        } elseif ($this->getModel()->checkByColumn('code', $this->_formValues['code'])) {
            $this->error->exists('Code');
        }
        
        if (! $this->_memberValidator->mobile($this->_formValues['mobile'])) {
            $this->error->invalid('Mobile');
        }
        
        if (! $this->_memberValidator->aadhar($this->_formValues['aadhar_number'])) {
            $this->error->invalid('Aadhar Number');
        }
        
        if (! $this->_memberValidator->mobile($this->_formValues['mobile'])) {
            $this->error->invalid('Aadhar Linked Mobile');
        }
        
        $this->_validateBoats();
        $this->_validateAccount();
        
        return $this->noError();
    }
    
    /**
     * create member record
     * 
     * @return int
     */
    public function create($fmcId)
    {
        $insert = [
            'fmc_id' => $fmcId,
            'created_at' => DateHelper::timestamp(),
        ];
        $direct = [
            'code', 'name', 'member_type_id', 'name_malayalam',
            'mobile', 'aadhar_number', 'aadhar_mobile', 'address', 'village', 
            'taluk', 'kerosene_distribution_center', 'preferred_inspection_location',
        ];
        
        foreach ($direct as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        
        if ('' != $this->_formValues['date_of_birth']) {
            $insert['date_of_birth'] = DateHelper::fromDatepicker($this->_formValues['date_of_birth']);
        }
        
         if ('' != $this->_formValues['approved_date']) {
            $insert['approved_date'] = DateHelper::fromDatepicker($this->_formValues['approved_date']);
        }
        
        
        
        $this->_memberId = $this->save($insert);
        $this->_savePhoto();
        $this->_saveCards();
        $this->_savePanchayaths();
        $this->_saveDocuments();
        $this->_saveAccount();

        return $this->_memberId;
    }
    
    
    private function _validateBoats()
    {
        foreach ($this->_formValues['registration_number'] as $index => $registrationNumber) {
            if ($this->validator->notNull($registrationNumber)) {
                $panchayath = $index > 0 ? 'Second ' : '';
                $panchayath .= ' Yaanam';
                
                if ($this->validator->isNull($this->_formValues['registration_date'][$index])) {
                    $this->error->required($panchayath . 'Registration Date');
                }
                if ($this->validator->notSelected($this->_formValues['panchayath_brand_id'][$index])) {
                    $this->error->notSelected($panchayath . 'Brand');
                }
                
                if (! $this->_memberValidator->horsePower($this->_formValues['horse_power'][$index])) {
                    $this->error->invalid($panchayath . 'HP');
                }
                if ($this->validator->isNull($this->_formValues['panchayath_number'][$index])) {
                    $this->error->required($panchayath . 'Panchayath Number');
                }
            }
        }
    }
    
    private function _validateAccount()
    {
        if (0 == $this->_formValues['bank_id']) {
            return;
        }
        
        if ($this->validator->isNull($this->_formValues['account_number'])) {
            $this->error->required('Account Number');
        } elseif (! $this->_memberValidator->accountNumber($this->_formValues['account_number'])) {
            $this->error->invalid('Account Number');
        }
        
        if ($this->validator->notSelected($this->_formValues['account_type_id'])) {
            $this->error->notSelected('Account type');
        }
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
    
    private function _saveCards()
    {
        $memberCardModel = new MemberCard();
        $default = [
            'card_type_id' => 0,
            'member_id' => $this->_memberId,
            'card_number' => '',
            'issue_date' => '',
            'expiry_date' => '',
        ];
        $insert = $memberCardModel->getInsertPreparedStatement(array_keys($default));
        foreach ($this->_formValues['card_number'] as $cardTypeId => $cardNumber) {
            if ($this->validator->notNull($cardNumber)) {
                $record = $default;
                $record['card_type_id'] = $cardTypeId;
                $record['card_number'] = $cardNumber;
                $record['issue_date'] = $this->_formValues['issue_date'][$cardTypeId];
                $record['expiry_date'] = $this->_formValues['expiry_date'][$cardTypeId];
                $insert->execute(array_values($record));
            }
        }
    }
    
    private function _savePanchayaths()
    {
        $boatModel = new Boat();
        $default = [
            'member_id' => $this->_memberId,
            'registration_number' => '',
            'registration_date' => '',
            'panchayath_brand_id' => 0,
            'horse_power' => '',
            'purchase_date' => '',
            'panchayath_number' => '',
            'second_boat' => 0,
            'thattumadi' => 0,
            'fishing_license_number' => '',
            'fishing_license_date' => '',
            'pds_permit_number' => '',
            'matsyafed_permit_number' => '',
        ];
        
        $insert = $boatModel->getInsertPreparedStatement(array_keys($default));
        foreach ($this->_formValues['registration_number'] as $index => $registrationNumber) {
            if ($this->validator->notNull($registrationNumber)) {
                $record = $default;
                $record['registration_number'] = $registrationNumber;
                $record['registration_date'] = $this->_formValues['registration_date'][$index];
                $record['panchayath_brand_id'] = $this->_formValues['panchayath_brand_id'][$index];
                $record['horse_power'] = $this->_formValues['horse_power'][$index];
                $record['purchase_date'] = $this->_formValues['purchase_date'][$index];
                $record['panchayath_number'] = $this->_formValues['panchayath_number'][$index];
                $record['second_boat'] = $index;
                $record['thattumadi'] = $this->_formValues['thattumadi'][$index];
                $record['fishing_license_number'] = $this->_formValues['fishing_license_number'][$index];
                $record['fishing_license_date'] = $this->_formValues['fishing_license_date'][$index];
                $record['pds_permit_number'] = $this->_formValues['pds_permit_number'][$index];
                $record['matsyafed_permit_number'] = $this->_formValues['matsyafed_permit_number'][$index];
                $insert->execute(array_values($record));
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
    
    private function _saveAccount()
    {
        if (0 == $this->_formValues['bank_id']) {
            return;
        }
        
        $fields = ['bank_id', 'account_type_id', 'account_number', 'description'];
        $insert = [
            'member_id' => $this->_memberId,
        ];
        foreach ($fields as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        
        $accountModel = new Account();
        $accountModel->insert($insert);
    }
    
}
