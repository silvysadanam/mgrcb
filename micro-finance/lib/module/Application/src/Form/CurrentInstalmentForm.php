<?php
/**
 * Application CurrentInstalment Form
 * 
 * @category   Application
 * @package    Application\Form
 */

namespace Application\Form;

use Application\Utility\DcbCreator;
use Application\Helper\CommentLogger;

use Web\Form\Form;


class CurrentInstalmentForm extends Form
{    
    /**
     * 
     * @var array
     */
    private $_formValues;

    public function __construct()
    {
        parent::__construct();
        $fields = [ 'repayment', 'catch', 'note', 'dcbPaymentMode' ];
        $this->addFields($fields);
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

        if ($this->validator->isNull($this->_formValues['repayment'])) {
            $this->error->required('LR');
        } elseif (! $this->_validateRepayment($this->_formValues['repayment'])) {
            $this->error->invalid('LR');
        }

        if ($this->validator->isNull($this->_formValues['catch'])) {
            $this->error->required('Catch');
        } elseif (! $this->validator->integer($this->_formValues['catch'])) {
            $this->error->invalid('Catch');
        }
        
        return $this->noError();
    }
    
    /**
     * create
     * 
     * @param \Application\Utility\LoanStore $store
     * @return boolean
     */
    public function create($store, $administratorId)
    {
        $creator = new DcbCreator($store);
        $creator->repayment($this->_formValues['repayment'])
                ->paymentMode($this->_formValues['dcbPaymentMode'])
                ->catchAmount($this->_formValues['catch']);
        if (! $creator->checkDemand()) {
            return false;
        }
        
        $creator->run();
        
        $this->_saveComment($store->id(), $administratorId);

        return true;
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
