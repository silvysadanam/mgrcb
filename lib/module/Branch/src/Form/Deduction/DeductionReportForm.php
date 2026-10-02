<?php
/**
 * Branch Deduction Report Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Deduction
 */

namespace Branch\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Shared\Utility\DeductionHelper;


class DeductionReportForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'start_date', 'end_date', 'group' ];
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
        
        if ($this->validator->notSelected($values['group'])) {
            $this->error->notSelected('Group');
        }
        
        if ($this->noError()) {
            $groups = DeductionHelper::groupedDeductionTypeIds();
            if (empty($groups[$values['group']])) {
                $this->addError('Deduction items not found');
            } else {
                $deductions = $branch->deductions();
                $allowed = [];
                foreach ($groups[$values['group']] as $item) {
                    if (isset($deductions[$item])) {
                        $allowed[] = $item;
                    }
                }
                
                if (empty($allowed)) {
                    $this->addError('Deduction items not found');
                } else {
                    $model = $this->getModel();
                    $sql = $model->getSql();
                    $sql->whereIn('member_id', $branch->memberIds())
                        ->whereIn('deduction_type_id', $allowed);
                    if ($singleDate) {
                        $sql->whereEqual('deduction_date', $startDate);
                    } else {
                        $sql->whereBetween('deduction_date', $startDate, $endDate);
                    }
                    if (0 == $model->selectTotal($sql->getWhere())) {
                        $this->addError('No records found');
                    }
                }
            }
        }
        
        return $this->noError();
    }
    
}

