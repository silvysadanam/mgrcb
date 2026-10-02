<?php
/**
 * Shared Ajax Form
 * 
 * @category   Shared
 * @package    Shared\Form
 */

namespace Shared\Form;

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
