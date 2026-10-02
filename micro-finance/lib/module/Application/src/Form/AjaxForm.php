<?php
/**
 * Application Ajax Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Web\Form\Form;


class AjaxForm extends Form
{   
    public function __construct()
    {
        parent::__construct();
        $this->addField('id');
    }
    
    /**
     * process form
     *
     * @return array
     */
    public function run()
    {
        $this->csrf();
        
        return $this->postback();
    }

}
