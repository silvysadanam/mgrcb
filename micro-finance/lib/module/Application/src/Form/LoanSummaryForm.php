<?php
/**
 * Application LoanSummary Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Web\Form\Form;


class LoanSummaryForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'fmc_id', 'start_date', 'end_date' ];
        $this->addFields($fields);
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
