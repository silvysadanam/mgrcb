<?php
/**
 * Application Repayment Annual Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\Repayment;

use Web\Form\Form;
use Web\Utility\DateHelper;


class AnnualForm extends Form
{ 
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'fmc',
            'loan_source_id',
            'start_date',
            'end_date',
            'report_option',
        ];
        $this->addFields($fields);
    }

    /**
     * validate form
     *
     * @param array $limits
     * @return boolean
     */
    public function isValid($limits)
    {
        $this->csrf();
        $values = $this->postback();
        $start = DateHelper::fromDatepicker($values['start_date']);
        $end = DateHelper::fromDatepicker($values['end_date']);
        $startTime = DateHelper::getDateTime($start);
        $endTime = DateHelper::getDateTime($end);
        if ($startTime > $endTime) {
            $this->error->invalid('Report period');
        }
        
        if ($startTime < DateHelper::getDateTime($limits['min_date'])) {
            $this->error->invalid('Start date');
        }
        
        if ($endTime > DateHelper::getDateTime($limits['max_date'])) {
            $this->error->invalid('End date');
        }
        
        if ($this->noError()) {
            $startTime->modify('+11 months');
            $days = $startTime->format('t');
            $startTime->setDate($startTime->format('Y'), $startTime->format('n'), $days);
            if ($endTime > $startTime) {
                $this->error->invalid('Report period');
            }
        }
        
        return $this->noError();
    }
    
}
