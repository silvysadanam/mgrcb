<?php
/**
 * Backend ManageSettings Utility
 *
 * @category   Backend
 * @package    Backend\Utility
 */

namespace Backend\Utility;

use Shared\Form\AjaxForm;
use Model\{DesignationType, MemberCard, DocumentType, MemberDocument, Medicine, Disease, Bank, Account, PanchayathBrand, Staff};


class ManageSettings
{
    /**
     * 
     * @var \Web\Mvc\AjaxResponse
     */
    private $_response;
    
    /**
     * 
     * @var array
     */
    private $_values;
    
    /**
     * 
     * @var array
     */
    private $_result = [];
    
    /**
     * 
     * @param \Web\Mvc\AjaxResponse $response
     */
    public function __construct($response)
    {
        $this->_response = $response;
        $this->_response->message('Invalid input');
    }
    
    /**
     * manage designation type
     */
    public function designationType()
    {
        $this->_loadForm(['action', 'designation_type']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveDesignationType();
        } else {
            $this->_removeDesignationType();
        }
        
        return $this->_result;
    }
    
    /**
     * manage document type
     */
    public function documentType()
    {
        $this->_loadForm(['action', 'document_type']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveDocumentType();
        } else {
            $this->_removeDocumentType();
        }
        
        return $this->_result;
    }
    
    /**
     * manage medicine
     */
    public function medicine()
    {
        $this->_loadForm(['action', 'medicine', 'name_english', 'name_malayalam']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveMedicine();
        } else {
            $this->_removeFish();
        }
        
        return $this->_result;
    }
    
    
    /**
     * manage medicine
     */
    public function disease()
    {
        $this->_loadForm(['action', 'disease', 'name_english', 'name_malayalam']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
       
        if (1 == $this->_values['action']) {
            $this->_saveDisease();
        } else {
            $this->_removeDisease();
        }
        
        return $this->_result;
    }
    
    /**
     * manage bank
     */
    public function bank()
    {
        $this->_loadForm(['action', 'bank', 'branch', 'ifsc']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_saveBank();
        } else {
            $this->_removeBank();
        }
        
        return $this->_result;
    }
    
    /**
     * manage panchayath brand
     */
    public function panchayathBrand()
    {
        $this->_loadForm(['action', 'panchayath_brand']);
        if (empty($this->_values)) {
            return $this->_result;
        }
        
        if (! $this->_checkAction()) {
            return $this->_result;
        }
        
        if (1 == $this->_values['action']) {
            $this->_savePanchayathBrand();
        } else {
            $this->_removePanchayathBrand();
        }
        
        return $this->_result;
    }
    
    
    /**
     * check action
     * 
     * @return boolean
     */
    private function _checkAction()
    {
        return in_array($this->_values['action'], [1, 2]);
    }
    
    /**
     * save designation type
     * 
     * @return null
     */
    private function _saveDesignationType()
    {
        $model = new DesignationType();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateDesignationType($id);
            return;
        }
        
        $errors = [];
        if ('' == $this->_values['designation_type']) {
            $errors[] = 'Card Type is required';
        } else {
            if ($model->checkByColumn('designation_type', $this->_values['designation_type'])) {
                $errors[] = 'Card Type exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $model->insert(['designation_type' => $this->_values['designation_type']]);
        $this->_saved('Card Type', false);
    }
    
    /**
     * update designation type
     * 
     * @param int $id
     * @return null
     */
    private function _updateDesignationType($id)
    {
        $model = new DesignationType();
        $errors = [];
        if ('' == $this->_values['designation_type']) {
            $errors[] = 'Designation Type is required';
        } elseif ($model->checkByColumnExceptId('designation_type', $this->_values['designation_type'], $id)) {
             $errors[] = 'Designation Type exists';
        }

        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }

        if ($this->_values['designation_type'] == $model->selectColumnById('designation_type', $id)) {
            $this->_noChange();
            return;
        }

        $model->updateById(['designation_type' => $this->_values['designation_type']], $id);
        $this->_saved('Designation Type', true);
    }
    
    /**
     * remove designation type
     * 
     * @return null
     */
    private function _removeDesignationType()
    {
        $model = new DesignationType();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $staffModel = new Staff();
            if ($staffModel->checkByColumnId('designation_type_id', $id)) {
                $this->_response->message('Designation Type already in use');
                return;
            }
            
            $designationType = $model->selectColumnById('designation_type', $id);
            $model->deleteById($id);
            $this->_deletd('Designation Type', $designationType);
        }
    }
    
    /**
     * save document type
     * 
     * @return null
     */
    private function _saveDocumentType()
    {
        $model = new DocumentType();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateDocumentType($id);
            return;
        }
        
        $errors = [];
        if ('' == $this->_values['document_type']) {
            $errors[] = 'Document Type is required';
        } else {
            if ($model->checkByColumn('document_type', $this->_values['document_type'])) {
                $errors[] = 'Document Type exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $model->insert(['document_type' => $this->_values['document_type']]);
        $this->_saved('Document Type', false);
    }
    
    /**
     * update document type
     * 
     * @param int $id
     * @return null
     */
    private function _updateDocumentType($id)
    {
        $model = new DocumentType();
        $errors = [];
        if ('' == $this->_values['document_type']) {
            $errors[] = 'Document Type is required';
        } elseif ($model->checkByColumnExceptId('document_type', $this->_values['document_type'], $id)) {
             $errors[] = 'Document Type exists';
        }

        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }

        if ($this->_values['document_type'] == $model->selectColumnById('document_type', $id)) {
            $this->_noChange();
            return;
        }

        $model->updateById(['document_type' => $this->_values['document_type']], $id);
        $this->_saved('Document Type', true);
    }
    
    /**
     * remove document type
     * 
     * @return null
     */
    private function _removeDocumentType()
    {
        $model = new DesignationType();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $memberDocumentModel = new MemberDocument();
            if ($memberDocumentModel->checkByColumnId('document_type_id', $id)) {
                $this->_response->message('Document Type already in use');
                return;
            }
            
            $documentType = $model->selectColumnById('document_type', $id);
            $model->deleteById($id);
            $this->_deletd('Document Type', $documentType);
        }
    }
    
    /**
     * save medicine
     * 
     * @return null
     */
    private function _saveMedicine()
    {
        $model = new Medicine();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->__updateMedicine($id);
            return;
        }
        
        $this->_addMedicine();
    }
    
     /**
     * save medicine
     * 
     * @return null
     */
    private function _saveDisease()
    {
        $model = new Disease();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateDisease($id);
            return;
        }
        
        $this->_addDisease();
    }
    
    /**
     * add medicine
     * 
     * @return null
     */
    private function _addMedicine()
    {
        $model = new Medicine();
        $errors = [];
        if ('' == $this->_values['medicine']) {
            $errors[] = 'Medicine name is required';
        } else {
            if ($model->checkByColumn('medicine', $this->_values['medicine'])) {
                $errors[] = 'Medicine name exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['medicine', 'name_english', 'name_malayalam'];
        $insert = [];
        foreach ($fields as $field) {
            if ('' != $this->_values[$field]) {
                $insert[$field] = $this->_values[$field];
            }
        }
        
        $insert['name'] = $this->_getFishName($insert);
        $model->insert($insert);
        $this->_saved('Medicine', false);
    }
    
    private function _addDisease()
    {
        $model = new Disease();
        $errors = [];
        if ('' == $this->_values['disease']) {
            $errors[] = 'Disease name is required';
        } else {
            if ($model->checkByColumn('disease', $this->_values['disease'])) {
                $errors[] = 'Disease name exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['disease', 'name_english', 'name_malayalam'];
        $insert = [];
        foreach ($fields as $field) {
            if ('' != $this->_values[$field]) {
                $insert[$field] = $this->_values[$field];
            }
        }
        
        $insert['name'] = $this->_getFishName($insert);
        $model->insert($insert);
        $this->_saved('Disease', false);
    }
    
    /**
     * update medicine
     * 
     * @param int $id
     * @return null
     */
    private function __updateMedicine($id)
    {
        $model = new Medicine();
        $errors = [];
        if ('' == $this->_values['medicine']) {
            $errors[] = 'Medicine name is required';
        } else {
            if ($model->checkByColumnExceptId('medicine', $this->_values['medicine'], $id)) {
                $errors[] = 'Medicine name exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $current = $model->find($id);
        $fields = ['medicine', 'name_english', 'name_malayalam'];
        $update = [];
        $nameValues = [];
        foreach ($fields as $field) {
            if ($this->_values[$field] != $current->{$field}) {
                $update[$field] = $this->_values[$field];
                $nameValues[] = $this->_values[$field];
            } else {
                $nameValues[] = $current->{$field};
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $update['name'] = $this->_getFishName($nameValues);
        $model->updateById($update, $id);
        $this->_saved('Medicine', true);
    }
    
     private function _updateDisease($id)
    {
        $model = new Disease();
        $errors = [];
        if ('' == $this->_values['disease']) {
            $errors[] = 'Disease name is required';
        } else {
            if ($model->checkByColumnExceptId('disease', $this->_values['disease'], $id)) {
                $errors[] = 'Disease name exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $current = $model->find($id);
        $fields = ['disease', 'name_english', 'name_malayalam'];
        $update = [];
        $nameValues = [];
        foreach ($fields as $field) {
            if ($this->_values[$field] != $current->{$field}) {
                $update[$field] = $this->_values[$field];
                $nameValues[] = $this->_values[$field];
            } else {
                $nameValues[] = $current->{$field};
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $update['name'] = $this->_getFishName($nameValues);
        $model->updateById($update, $id);
        $this->_saved('Disease', true);
    }
    
    /**
     * remove medicine
     * 
     * @return null
     */
    private function _removeFish()
    {
        $model = new Medicine();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }

            $medicine = $model->selectColumnById('medicine', $id);
            $model->deleteById($id);
            $this->_deletd('Medicine', $medicine);
        }
    }
    
    /**
     * remove disease
     * 
     * @return null
     */
    private function _removeDisease()
    {
        $model = new Medicine();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }

            $medicine = $model->selectColumnById('disease', $id);
            $model->deleteById($id);
            $this->_deletd('Disease', $medicine);
        }
    }
    
    /**
     * save bank
     * 
     * @return null
     */
    private function _saveBank()
    {
        $model = new Bank();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updateBank($id);
            return;
        }
        
        $this->_addBank();
    }
    
    /**
     * add bank
     * 
     * @return null
     */
    private function _addBank()
    {
        $model = new Bank();
        $errors = [];
        if ('' == $this->_values['bank']) {
            $errors[] = 'Bank name is required';
        }
        
        if ('' == $this->_values['branch']) {
            $errors[] = 'Branch name is required';
        }
        
        if ('' == $this->_values['ifsc']) {
            $errors[] = 'IFSC is required';
        } elseif (! $this->_ifsCode($this->_values['ifsc'])) {
            $errors[] = 'IFSC is invalid';
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $sql = $model->getSql();     
        $sql->whereEqual('bank', $this->_values['bank'])
            ->whereEqual('branch', $this->_values['branch']);
        if ($model->selectTotal($sql->getWhere()) > 0) {
            $errors[] = 'Bank/Branch name exists';
        }
        
        if ($model->checkByColumn('ifsc', $this->_values['ifsc'])) {
            $errors[] = 'IFSC exists';
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['bank', 'branch', 'ifsc'];
        $insert = [];
        foreach ($fields as $field) {
            $insert[$field] = $this->_values[$field];
        }
        
        $model->insert($insert);
        $this->_saved('Bank', false);
    }
    
    /**
     * update bank
     * 
     * @param int $id
     * @return null
     */
    private function _updateBank($id)
    {
        $model = new Bank();
        if ('' == $this->_values['bank']) {
            $errors[] = 'Bank name is required';
        }
        
        if ('' == $this->_values['branch']) {
            $errors[] = 'Branch name is required';
        }
        
        if ('' == $this->_values['ifsc']) {
            $errors[] = 'IFSC is required';
        } elseif (! $this->_ifsCode($this->_values['ifsc'])) {
            $errors[] = 'IFSC is invalid';
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $sql = $model->getSql();     
        $sql->whereEqual('bank', $this->_values['bank'])
            ->whereEqual('branch', $this->_values['branch'])
            ->whereNotId('id', $id);
        if ($model->selectTotal($sql->getWhere()) > 0) {
            $errors[] = 'Bank/Branch name exists';
        }
        
        if ($model->checkByColumnExceptId('ifsc', $this->_values['ifsc'], $id)) {
            $errors[] = 'IFSC exists';
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $fields = ['bank', 'branch', 'ifsc'];
        $update = [];
        $current = $model->find($id);
        
        foreach ($fields as $field) {
            if ($this->_values[$field] != $current->{$field}) {
                $update[$field] = $this->_values[$field];
            }
        }
        
        if (empty($update)) {
            $this->_noChange();
            return;
        }
        
        $model->updateById($update, $id);
        $this->_saved('Bank', true);
    }
    
    /**
     * remove bank
     * 
     * @return null
     */
    private function _removeBank()
    {
        $model = new Bank();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }

            $accountModel = new Account();
            if ($accountModel->checkByColumnId('bank_id', $id)) {
                $this->_response->message('Bank already in use');
                return;
            }
            
            $bank = $model->selectColumnById('bank', $id);
            $model->deleteById($id);
            $this->_deletd('Bank', $bank);
        }
    }
    
    /**
     * save panchayath brand
     * 
     * @return null
     */
    private function _savePanchayathBrand()
    {
        $model = new PanchayathBrand();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $this->_updatePanchayathBrand($id);
            return;
        }
        
        $errors = [];
        if ('' == $this->_values['panchayath_brand']) {
            $errors[] = 'Panchayath Brand is required';
        } else {
            if ($model->checkByColumn('panchayath_brand', $this->_values['panchayath_brand'])) {
                $errors[] = 'Panchayath Brand exists';
            }
        }
        
        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }
        
        $model->insert(['panchayath_brand' => $this->_values['panchayath_brand']]);
        $this->_saved('Panchayath Brand', false);
    }
    
    /**
     * update panchayath brand
     * 
     * @param int $id
     * @return null
     */
    private function _updatePanchayathBrand($id)
    {
        $model = new PanchayathBrand();
        $errors = [];
        if ('' == $this->_values['panchayath_brand']) {
            $errors[] = 'Panchayath Brand is required';
        } elseif ($model->checkByColumnExceptId('panchayath_brand', $this->_values['panchayath_brand'], $id)) {
             $errors[] = 'Panchayath Brand exists';
        }

        if (! empty($errors)) {
            $this->_setError($errors);
            return;
        }

        if ($this->_values['panchayath_brand'] == $model->selectColumnById('panchayath_brand', $id)) {
            $this->_noChange();
            return;
        }

        $model->updateById(['panchayath_brand' => $this->_values['panchayath_brand']], $id);
        $this->_saved('Panchayath Brand', true);
    }
    
    /**
     * remove panchayath brand
     * 
     * @return null
     */
    private function _removePanchayathBrand()
    {
        $model = new PanchayathBrand();
        $id = (int) $this->_values['id'];
        if ($id > 0) {
            if (! $model->hasId($id)) {
                return;
            }
            
            $panchayathBrand = $model->selectColumnById('panchayath_brand', $id);
            $model->deleteById($id);
            $this->_deletd('Panchayath Brand', $panchayathBrand);
        }
    }
    
    
    /**
     * validate IFS Code
     * 
     * @param string $code
     * @return boolean
     */
    private function _ifsCode($code)
    {
        if (11 != strlen($code)) {
            return false;
        }
        
        if (0 == preg_match('/^[A-Z0-9][A-Z0-9]+$/', $code)) {
            return false;
        }
        
        return '0' == substr($code, 4, 1);
    }
    
    /**
     * get Medicine name
     * 
     * @param array $options
     * @return string
     */
    private function _getFishName($options)
    {
        $list = array_filter($options);
        
        return implode(' / ', $list);
    } 
    
    /**
     * load form
     * 
     * @param array $fields
     * @return null
     */
    private function _loadForm($fields = [])
    {
        $form = new AjaxForm();
        if (! empty($fields)) {
            $form->addFields($fields);
        }
        
        if (! $form->isPost()) {
            return;
        }
        
        $this->_values = $form->run();
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
    private function _deletd($title, $name = '')
    {
        if ('' != $name) {
            $title .= sprintf(' "%s"', $this->_escape($name));
        }
        
        $this->_valid($title . ' deleted', true);
    }
    
    private function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
}
