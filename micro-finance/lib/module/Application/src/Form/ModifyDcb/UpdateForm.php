<?php
/**
 * Application ModifyDcb Update Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage    Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Utility\{DcbCreator, LoanStore};
use Application\Helper\CommentLogger;

use Web\Form\Form;


class UpdateForm extends Form
{    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var array
     */
    private $_entries = [];

    public function __construct()
    {
        parent::__construct();
        $fields = [ 'repayment', 'catch' ];
        $this->addFields($fields);
        $this->multiselect($fields);
        $this->addFields(['last', 'note', 'dcbPaymentMode']);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid($payment)
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        $repayment = $this->_formValues['repayment'];
        $catch = $this->_formValues['catch'];
        $last = $this->_formValues['last'];
        foreach ($payment as $item) {
            if ($this->validator->isNull($repayment[$item->id])) {
                $this->error->required('Instalment ' . $item->instalment . ' LR');
            } elseif (! $this->_validateRepayment($repayment[$item->id])) {
                $this->error->invalid('Instalment ' . $item->instalment . ' LR');
            }
            
            if ($this->validator->isNull($catch[$item->id])) {
                $this->error->required('Instalment ' . $item->instalment . ' Catch');
            } elseif (! $this->validator->integer($catch[$item->id])) {
                $this->error->invalid('Instalment ' . $item->instalment . ' Catch');
            }
            
            $this->_entries[] = $item->id;
            
            if ($last == $item->id) {
                break;
            }
        }
        
        if ($this->noError()) {
            $modified = false;
            foreach ($payment as $item) {
                if ($repayment[$item->id] != $item->collection_repaid) {
                    $modified = true;
                    break;
                }
                if ($catch[$item->id] != $item->catch_amount) {
                    $modified = true;
                    break;
                }
                
                if ($last == $item->id) {
                    break;
                }
            }
            
            if (! $modified) {
                $lastItem = end($payment);
                if ($last == $lastItem->id) {
                    $this->error->add('No entries changed');
                }
            }
        }
        
        return $this->noError();
    }
    
    /**
     * change
     * 
     * @return boolean
     */
    public function change($administratorId)
    {
        $success = true;
        $loanId = $this->getId();
        $store = new LoanStore($loanId);
        $store->resetDcb();
        foreach ($this->_entries as $scheduleId) {
            $store = new LoanStore($loanId);
            if (! $store->dataEntry()) {
                break;
            }
            
            $creator = new DcbCreator($store);
            $creator->repayment($this->_formValues['repayment'][$scheduleId])
                    ->catchAmount($this->_formValues['catch'][$scheduleId])
                    ->paymentMode($this->_formValues['dcbPaymentMode']);
            if (! $creator->checkDemand()) {
                $success = false;
                break;
            }
            $creator->run();
        }

        $this->_saveComment($administratorId);
        
        return $success;
    }
    
    private function _saveComment($administratorId)
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($administratorId);
        $logger->id($this->getId())
               ->save($this->_formValues['note']);
    }
    
    /**
     * validate repayment amount
     * 
     * @param string $amount
     * @return null
     */
    private function _validateRepayment($amount)
    {
        if ('0' == $amount || '0.0' == $amount || '0.00' == $amount) {
            return true;
        }
        
        if ($this->validator->integer($amount)) {
            return true;
        }
        
        if ($this->validator->decimal($amount)) {
            return $this->validator->decimalPoints($amount, 2);
        }
        
        return false;
    }
    
}
