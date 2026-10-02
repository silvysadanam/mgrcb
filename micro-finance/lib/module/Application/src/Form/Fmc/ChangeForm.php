<?php
/**
 * Application Fmc Change Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\Fmc
 */

namespace Application\Form\Fmc;

use Web\Form\Form;


class ChangeForm extends Form
{  
    public function __construct()
    {
        parent::__construct();
        $this->addFields([ 'code', 'name' ]);
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
        if ($this->validator->isNull($values['name'])) {
            $this->error->required('Name');
        } elseif ($this->getModel()->checkByColumnExceptId('name', $values['name'], $this->getId())) {
            $this->error->exists('Name');
        }
        
        if ($this->validator->isNull($values['code'])) {
            $this->error->required('Code');
        } elseif ($this->getModel()->checkByColumnExceptId('code', $values['code'], $this->getId())) {
            $this->error->exists('Code');
        }
        
        return $this->noError();
    }
    
    /**
     * change fmc record
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
