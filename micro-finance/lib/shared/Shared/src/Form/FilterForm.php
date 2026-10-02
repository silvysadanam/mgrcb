<?php
/**
 * Shared Filter Form
 * 
 * @category Shared
 * @package  Shared\Form
 */

namespace Shared\Form;

use Web\Form\FormGet;


class FilterForm extends FormGet
{   
    public function __construct()
    {
        parent::__construct();
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
