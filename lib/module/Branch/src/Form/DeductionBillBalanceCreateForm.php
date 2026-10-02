<?php
/**
 * Branch DeductionBillBalanceCreate Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;

use Shared\Utility\DeductionHelper;


class DeductionBillBalanceCreateForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;

    public function __construct()
    {
        parent::__construct();
        $multiple = ['deduction_item', 'balance', 'opening_date'];
        $this->addFields($multiple);
        $this->multiselect($multiple);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        $deductionTypes = DeductionHelper::deductionTypes();
        foreach ($this->_formValues['deduction_item'] as $index => $type) {
            if (! $this->_validateEntry($this->_formValues['balance'][$index])) {
                $this->error->invalid('Balance for ' . $deductionTypes[$type]);
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create opening balance
     * 
     * @param int $memberId
     * @return boolean
     */
    public function create($memberId)
    {
        $default = [
            'member_id' => $memberId,
            'deduction_type_id' => 0,
            'opening_balance' => 0,
            'opening_date' => '',
        ];

        $insert = $this->getModel()->getInsertPreparedStatement(array_keys($default));
        
        foreach ($this->_formValues['deduction_item'] as $index => $type) {
            $record = $default;
            $record['deduction_type_id'] = $type;
            $record['opening_balance'] = $this->_formValues['balance'][$index];
            $record['opening_date'] = $this->_formValues['opening_date'][$index];
            $insert->execute(array_values($record));
        }
        
        return true;
    }
    
    
    /**
     * validate entry
     * 
     * @param string $value
     * @return boolean
     */
    private function _validateEntry($value)
    {
        if ($this->validator->isNull($value)) {
            return false;
        }
        
        if ('0' == $value) {
            return true;
        }
        
        return $this->validator->isPositiveNumber($value);
    }
    
}
