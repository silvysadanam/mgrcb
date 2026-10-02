<?php
/**
 * Branch R&D Report Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Deduction
 */

namespace Application\Form\Reports;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Shared\Utility\ReportHelper;


class LedgerReportForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'start_date', 'end_date', 'head'];
        $this->addFields($fields);
    }
    
    /**
     * validate form
     * 
     * @param \Branch\Utility\BranchStore $branch Branch Store
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $values = $this->postback();
        $startDate = DateHelper::fromDatepicker($values['start_date']);
        $endDate = DateHelper::fromDatepicker($values['end_date']);
        $singleDate = $startDate == $endDate;
        if (! $singleDate) {
            if (DateHelper::timestamp($startDate) > DateHelper::timestamp($endDate)) {
                $this->error->invalid('Report Period');
            }
        }
         if ($this->validator->notSelected($values['head'])) {
            $this->error->notSelected('Head');
        }
        return $this->noError();
    }
    
}

