<?php
/**
 * Application Pqr Member Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage    Application\Form\Pqr
 */

namespace Application\Form\Pqr;

use Web\Form\Form;


class MemberForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'fmc_id',
            'amount',
            'report_option',
            'reporting_date',
        ];
        $this->addFields($fields);
        $this->multiselect(['amount']);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->postback();
        
        return $this->noError();
    }
    
}
