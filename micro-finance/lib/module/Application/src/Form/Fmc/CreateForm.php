<?php
/**
 * Application Fmc Create Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\Fmc
 */

namespace Application\Form\Fmc;

use Web\Form\Form;


class CreateForm extends Form
{  
    public function __construct()
    {
        parent::__construct();
        $this->addFields([ 'code', 'name' ]);
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
        } elseif ($this->getModel()->checkByColumn('name', $values['name'])) {
            $this->error->exists('Name');
        }
        
        if ($this->validator->isNull($values['code'])) {
            $this->error->required('Code');
        } elseif ($this->getModel()->checkByColumn('code', $values['code'])) {
            $this->error->exists('Code');
        }
        
        return $this->noError();
    }
    
    /**
     * create fmc record
     * 
     * @return int
     */
    public function create()
    {
        return $this->save($this->getValues());
    }
    
}
