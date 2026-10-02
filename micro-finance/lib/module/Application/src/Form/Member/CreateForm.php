<?php
/**
 * Application Member Create Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\Member
 */

namespace Application\Form\Member;

use Web\Form\Form;
use Web\Utility\DateHelper;


class CreateForm extends Form
{  
    public function __construct()
    {
        parent::__construct();
        $this->addFields([ 'code', 'name', 'fmc_id', 'member_type_id' ]);
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
        } elseif ($this->getModel()->checkByColumn('code', $values['code'])) {
            $this->error->exists('Code');
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
        $values = $this->getValues();
        $values['created_at'] = DateHelper::timestamp();
        
        return $this->save($values);
    }
    
}
