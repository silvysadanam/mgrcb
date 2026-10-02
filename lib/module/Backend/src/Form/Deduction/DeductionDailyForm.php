<?php
/**
 * Backend Deduction Report Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Deduction
 */

namespace Backend\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Shared\Utility\DeductionHelper;


class DeductionDailyForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'fmc', 'deduction_date' ];
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
        $values = $this->postback();
        if ($this->validator->notSelected($values['fmc'])) {
            $this->error->notSelected('FMC');
        }
        
        return $this->noError();
    }
    
}

