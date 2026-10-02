<?php
/**
 * Application Filter Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Web\Form\FormGet;


class FilterForm extends FormGet
{   
    public function __construct()
    {
        parent::__construct();
        $this->addField('search');
    }

    /**
     * get filtered values
     * 
     * @return array
     */
    public function getFilteredValues()
    {
        return $this->load();
    }
    
}
