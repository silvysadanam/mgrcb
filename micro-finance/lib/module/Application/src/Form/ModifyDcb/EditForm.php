<?php
/**
 * Application ModifyDcb Edit Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Helper\CommentLogger;

use Web\Form\Form;
use Web\Utility\DateHelper;


class EditForm extends Form
{    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var int
     */
    private $_administratorId;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'loan_type_id',
            'loan_source_id',
            'account_number',
            'note',
        ];
        $this->addFields($fields);
        $this->trackChanges();
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administratorId = $id;
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
        
        return $this->noError();
    }
    
    /**
     * change loan
     * 
     * @return int
     */
    public function change()
    {
        $modified = $this->getModified();
        if (empty($modified)) {
            return  false;
        }
        
        $this->_saveComment();
        if (isset($modified['note'])) {
            unset($modified['note']);
        }
        
        if (empty($modified)) {
            return  false;
        }
        
        $update = $modified;
        $update['updated_at'] = DateHelper::timestamp();
        $update['last_updated'] = DateHelper::today();
        
        $this->save($update);
        
        return true;
    }

    /**
     * save comment
     * 
     * @return null
     */
    private function _saveComment()
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administratorId);
        $logger->id($this->getId())
               ->save($this->_formValues['note']);
    }
    
    
}
