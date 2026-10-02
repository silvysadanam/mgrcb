<?php
/**
 * Backend Deduction Change Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Deduction
 */

namespace Backend\Form\Deduction;

use Web\Form\Form;
use Web\Utility\DateHelper;

use Shared\Utility\DeductionHelper;
use Shared\Model\PairModel;


class ChangeForm extends Form
{
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'deduction_type', 'conversion_type', 'conversion_value' ];
        $this->addFields($fields);
        $this->multiselect($fields);
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
        
        $pairModel = new PairModel();
        $titles = $pairModel->deductionType();
        $exclude = [DeductionHelper::CONVERSION_NO, DeductionHelper::CONVERSION_YES];
        $integer = [DeductionHelper::CONVERSION_PER_BILL, DeductionHelper::CONVERSION_FIXED];
        $selected = false;
        
        foreach ($this->_formValues['deduction_type'] as $index => $id) {
            if (! in_array($this->_formValues['conversion_type'][$index], $exclude)) {
                if (! $this->_validateNumber($this->_formValues['conversion_value'][$index])) {
                    $this->error->invalid($titles[$id] . ' - deduction value');
                } elseif (in_array($this->_formValues['conversion_type'][$index], $integer)) {
                    if (! $this->validator->isPositiveNumber($this->_formValues['conversion_value'][$index])) {
                        $this->error->invalid($titles[$id] . ' - deduction value');
                    }
                }
                
                if (! $selected) {
                    $selected = true;
                }
            } elseif (DeductionHelper::CONVERSION_YES == $this->_formValues['conversion_type'][$index]) {
                if (! $selected) {
                    $selected = true;
                }
            }
        }
        
        if (! $selected) {
            $this->error->notSelected('Deductions');
        }
        
        return $this->noError();
    }
    
    /**
     * update deduction entries
     * 
     * @return boolean
     */
    public function update($fmcId)
    {
        $default = [
            'fmc_id' => $fmcId,
            'deduction_type_id' => 0,
            'deduction_factor' => '',
            'conversion_type' => DeductionHelper::CONVERSION_YES,
            'converted_value' => '',
            'display_text' => 'Yes',
            'updated_at' => DateHelper::timestamp(),
        ];
        
        $current = $this->getModel()->selectIndexed(['fmc_id' => $fmcId], 'id', null, 'deduction_type_id');
        
        foreach ($this->_formValues['deduction_type'] as $index => $id) {
            if ($this->_formValues['conversion_type'][$index] > 0) {
                if (isset($current[$id])) {
                    if ($current[$id]->conversion_type != $this->_formValues['conversion_type'][$index]
                            || $current[$id]->deduction_factor != $this->_formValues['conversion_value'][$index]) {
                        $update = [];
                        if ($current[$id]->conversion_type != $this->_formValues['conversion_type'][$index]) {
                            $update['conversion_type'] = $this->_formValues['conversion_type'][$index];
                        }
                        
                        if ($current[$id]->deduction_factor != $this->_formValues['conversion_value'][$index]) {
                            $update['deduction_factor'] = $this->_formValues['conversion_value'][$index];
                            $update['converted_value'] = $this->_formValues['conversion_value'][$index];
                        }
                        $update['display_text'] = DeductionHelper::displayText($this->_formValues['conversion_type'][$index], $this->_formValues['conversion_value'][$index]);
                        $this->getModel()->updateById($update, $current[$id]->id);
                    }
                } else {
                    $record = $default;
                    $record['deduction_type_id'] = $id;
                    $record['conversion_type'] = $this->_formValues['conversion_type'][$index];
                    if (DeductionHelper::CONVERSION_YES != $this->_formValues['conversion_type'][$index]) {
                        $record['deduction_factor'] = $this->_formValues['conversion_value'][$index];
                        $record['converted_value'] = $this->_formValues['conversion_value'][$index];
                        $record['display_text'] = DeductionHelper::displayText($record['conversion_type'], $record['deduction_factor']);
                    }
                    $this->getModel()->insert($record);
                }
            } else {
                if (isset($current[$id])) {
                    $this->getModel()->deleteById($current[$id]->id);
                }
            }
        }

        return true;
    }
    
    /**
     * validate number
     * 
     * @param string $input
     * @return boolean
     */
    private function _validateNumber($input)
    {
        if (in_array($input, ['', '0'. '0.0', '0.00'])) {
            return false;
        }
        
        if (1 == preg_match('/^[1-9]\d*$/', $input)) {
            return true;
        }
        
        return $this->validator->decimalPoints($input, 2);
    }
    
}
