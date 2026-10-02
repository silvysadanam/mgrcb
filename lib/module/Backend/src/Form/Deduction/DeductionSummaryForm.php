<?php
/**
 * Backend Deduction Summary Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Deduction
 */

namespace Backend\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;


class DeductionSummaryForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'fmc', 'start_date', 'end_date' ];
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
        
        $startDate = DateHelper::fromDatepicker($values['start_date']);
        $endDate = DateHelper::fromDatepicker($values['end_date']);
        $singleDate = $startDate == $endDate;
        if (! $singleDate) {
            if (DateHelper::timestamp($startDate) > DateHelper::timestamp($endDate)) {
                $this->error->invalid('Report Period');
            }
        }
        
        return $this->noError();
    }
    
}

