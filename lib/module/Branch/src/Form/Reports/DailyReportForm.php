<?php
/**
 * Branch Deduction Daily Report Form
 * 
 * @category   Branch
 * @package    Branch\Form
 * @subpackage Branch\Form\Deduction
 */

namespace Branch\Form\Deduction;


use Web\Form\Form;
use Web\Utility\DateHelper;


class DailyReportForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $this->addFields(['deduction_date']);
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
        $startDate = DateHelper::fromDatepicker($values['deduction_date']);
        $model = $this->getModel();
        $sql = $model->getSql();
        $sql->whereIn('member_id', $branch->memberIds())
            ->whereEqual('deduction_date', $startDate);
        if (0 == $model->selectTotal($sql->getWhere())) {
            $this->addError('No records found');
        }
        
        return $this->noError();
    }
    
}

