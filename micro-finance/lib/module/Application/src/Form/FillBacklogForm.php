<?php
/**
 * Application FillBacklog Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Application\Utility\{DcbCreator, LoanStore};
use Application\Helper\CommentLogger;

use Web\Form\Form;


class FillBacklogForm extends Form
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
        $this->addFields(['last', 'note']);
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid($backlog)
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        $repayment = $this->_formValues['repayment'];
        $catch = $this->_formValues['catch'];
        $last = $this->_formValues['last'];
        foreach ($backlog as $item) {
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
        
        return $this->noError();
    }
    
    /**
     * fill
     * 
     * @param int $loanId
     * @param int $administratorId
     * @return boolean
     */
    public function fill($loanId, $administratorId)
    {
        $success = true;
        foreach ($this->_entries as $scheduleId) {
            $store = new LoanStore($loanId);
            if (! $store->dataEntry()) {
                $success = false;
                break;
            }
            
            $creator = new DcbCreator($store);
            $creator->repayment($this->_formValues['repayment'][$scheduleId])
                    ->catchAmount($this->_formValues['catch'][$scheduleId]);
            if (! $creator->checkDemand()) {
                $success = false;
                break;
            }
            
            $creator->run();
        }
        
        if ($success) {
            $this->_saveComment($loanId, $administratorId);
        }

        return $success;
    }
    
    private function _saveComment($loanId, $administratorId)
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($administratorId);
        $logger->id($loanId)
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
