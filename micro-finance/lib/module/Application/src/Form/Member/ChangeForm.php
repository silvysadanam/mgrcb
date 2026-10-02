<?php
/**
 * Application Member Change Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\Member
 */

namespace Application\Form\Member;

use Web\Form\Form;


class ChangeForm extends Form
{  
    public function __construct()
    {
        parent::__construct();
        $this->addFields([ 'code', 'name', 'fmc_id', 'member_type_id' ]);
        $this->trackChanges();
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $values = $this->postback();
        if ($this->validator->notSelected($values['fmc_id'])) {
            $this->error->notSelected('FMC');
        }
        if ($this->validator->isNull($values['name'])) {
            $this->error->required('Name');
        }
        
        if ($this->validator->isNull($values['code'])) {
            $this->error->required('Code');
        } elseif ($this->getModel()->checkByColumnExceptId('code', $values['code'], $this->getId())) {
            $this->error->exists('Code');
        }
        
        return $this->noError();
    }
    
    /**
     * update member record
     * 
     * @return boolean
     */
    public function change()
    {
        $modified = $this->getModified();
        if (empty($modified)) {
            return false;
        }
        
        return $this->save($modified);
    }
    
}
