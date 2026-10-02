<?php
/**
 * Branch DeductionBillBalanceEdit Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;

use Shared\Utility\DeductionHelper;


class DeductionBillBalanceEditForm extends Form
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
     * update opening balance
     * 
     * @param int $memberId
     * @return bpplean
     */
    public function update($memberId)
    {
        $sql = $this->getModel()->getSql();
        $sql->whereId('member_id', $memberId);
        $current = $this->getModel()->selectIndexed($sql->getWhere(), 'id', null, 'deduction_type_id');
        
        foreach ($this->_formValues['deduction_item'] as $index => $type) {
            if (isset($current[$type])) {
                $update = [];
                if ($this->_formValues['balance'][$index] != $current[$type]->opening_balance) {
                    $update['opening_balance'] = $this->_formValues['balance'][$index];
                }
                if ($this->_formValues['opening_date'][$index] != $current[$type]->opening_date) {
                    $update['opening_date'] = $this->_formValues['opening_date'][$index];
                }
                
                if (! empty($update)) {
                    $this->getModel()->updateById($update, $current[$type]->id);
                }
            } else {
                $record = [
                    'member_id' => $memberId,
                    'deduction_type_id' => $type,
                    'opening_balance' => $this->_formValues['balance'][$index],
                    'opening_date' => $this->_formValues['opening_date'][$index],
                ];
                $this->getModel()->insert($record);
            }
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
