<?php
/**
 * Application Pqr Loan Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage    Application\Form\Pqr
 */

namespace Application\Form\Pqr;

use Web\Form\Form;


class LoanForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'fmc_id',
            'loan_source_id',
            'loan_type_id',
            'amount',
            'report_option',
            'reporting_date',
        ];
        $this->addFields($fields);
        $this->multiselect(['loan_source_id', 'loan_type_id', 'amount']);
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
