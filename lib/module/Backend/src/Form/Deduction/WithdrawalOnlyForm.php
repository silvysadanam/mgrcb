<?php
/**
 * Backend Deduction WithdrawalOnly Form
 * 
 * @category   Backend
 * @package    Backend\Form
 * @subpackage Backend\Form\Deduction
 */

namespace Backend\Form\Deduction;

use Web\Form\Form;
use Shared\Constant\DeductionConfig;


class WithdrawalOnlyForm extends Form
{
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [ 'deduction_type' ];
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
        return $this->noError();
    }
    
    /**
     * update deduction entries
     * 
     * @return boolean
     */
    public function update()
    {
        $changed = false;
        $update = $this->getModel()->getUpdatePreparedStatement(['advance']);
        $sql = $this->getModel()->getSql();
        $sql->whereId('withdrawal_type', DeductionConfig::WITHDRAWAL_MEMBER);
        $list = $this->getModel()->selectPairs(['id', 'advance'], $sql->getWhere());
        $selected = $this->_formValues['deduction_type'];
        foreach ($list as $id => $item) {
            if (isset($selected[$id])) {
                if ($selected[$id] != $item) {
                    $update->execute([$selected[$id], $id]);
                    if (! $changed) {
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }
    
}
