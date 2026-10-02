<?php
/**
 * Application Pqa Loan Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\Pqa
 */

namespace Application\Form\Pqa;

use Model\Member;

use Web\Form\Form;


class LoanForm extends Form
{
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'fmc_id',
            'member_code',
            'loan_source_id',
            'loan_type_id',
            'member_name',
            'report_option',
            'reporting_date',
            'amount',
            'ageing',
        ];
        $this->addFields($fields);
        $this->multiselect(['loan_source_id', 'loan_type_id', 'amount']);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid($fmcId)
    {
        $this->csrf();
        $values = $this->postback();
        if ($this->validator->notNull($values['member_code'])) {
            $memberModel = new Member();
            if ($memberModel->checkByColumn('code', $values['member_code'])) {
                $member = $memberModel->selectByColumn('code', $values['member_code']);
                if ($fmcId > 0) {
                    if ($fmcId != $member->fmc_id) {
                        $this->error->invalid('Member');
                    }
                } elseif ($values['fmc_id'] > 0) {
                    if ($values['fmc_id'] != $member->fmc_id) {
                        $this->error->invalid('Member');
                    }
                }
            } else {
                $this->error->invalid('Member');
            }
        }

        return $this->noError();
    }
    
}
