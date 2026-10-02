<?php
/**
 * Branch IncomeForm Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Application\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Shared\Utility\AccountsHelper;
use Model\RPTransMemberDetails;

use Shared\Utility\AuctionSummary;


class IncomeForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var boolean
     */
    private $_allowDuplicate = true;

    public function __construct()
    {
        parent::__construct();
        $fields = ['receipt_date', 'payment_type', 'note', 'bank_id'];
        $multiple = ['head', 'amount', 'total'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
    }
    
    private function _validateEntries()
    {
        if (0 == count($this->_formValues['head'])) {
            $this->error->add('No entries added');
            
            return;
        }
        
        $counter = 0;
        foreach ($this->_formValues['head'] as $index => $fishId) {
            ++$counter;
            if ($this->validator->notSelected($fishId)) {
                $this->error->add('Entry ' . $counter . ': Head not selected');
            }
            
            if ($this->validator->isNull($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount required');
            } elseif (! $this->validator->nonZeroInteger($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount is invalid');
            }
        }
    }
}
