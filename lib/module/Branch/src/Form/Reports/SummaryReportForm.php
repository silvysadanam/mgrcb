<?php
/**
 * Branch Deduction Summary Report Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Deduction
 */

namespace Branch\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;


class SummaryReportForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'start_date', 'end_date' ];
        $this->addFields($fields);
    }
    
    /**
     * validate form
     * 
     * @param \Branch\Utility\BranchStore $branch Branch Store
     *
     * @return boolean
     */
    public function isValid($branch)
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
        if ($this->noError()) {
            $model = $this->getModel();
            $sql = $model->getSql();
            $sql->whereIn('member_id', $branch->memberIds());
            if ($singleDate) {
                $sql->whereEqual('deduction_date', $startDate);
            } else {
                $sql->whereBetween('deduction_date', $startDate, $endDate);
            }
            if (0 == $model->selectTotal($sql->getWhere())) {
                $this->addError('No records found');
            }
        }
        
        return $this->noError();
    }
    
}

