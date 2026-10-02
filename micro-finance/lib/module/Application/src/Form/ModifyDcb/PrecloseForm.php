<?php
/**
 * Application Preclose Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Utility\DcbClose;
use Application\Helper\{CommentLogger, TimelineLogger};

use Web\Form\Form;


class PrecloseForm extends Form
{    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var int
     */
    private $_administaratorId;
    
    /**
     * 
     * @var \Application\Utility\LoanStore
     */
    private $_store;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'closing_date', 'overdue', 'outstanding', 'interest',
            'penal_interest', 'total', 'paid_amount', 'payment_date',
            'receipt_number', 'fmc_voucher_number',  'note',
        ];
        $this->addFields($fields);
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administaratorId = $id;
    }
    
    /**
     * set store
     * 
     * @param \Application\Utility\LoanStore $store
     */
    public function setStore($store)
    {
        $this->_store = $store;
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
        
        if ($this->validator->notNull($this->_formValues['paid_amount'])) {
            if (! $this->validator->integer($this->_formValues['paid_amount'])) {
                if (! $this->validator->decimal($this->_formValues['paid_amount'])) {
                    $this->error->invalid('Amount Paid');
                } elseif (! $this->validator->decimalPoints($this->_formValues['paid_amount'], 2)) {
                    $this->error->invalid('Amount Paid');
                }
            }
        }
        
        return $this->noError();
    }
    
    /**
     * create
     * 
     * @return boolean
     */
    public function create()
    {
        $close = new DcbClose($this->_store);
        $close->run($this->_formValues);
        $this->_saveComment();
        $this->_timeline();

        return true;
    }
    
    private function _saveComment()
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administaratorId);
        $logger->id($this->_store->id())
               ->save($this->_formValues['note']);
    }
    
    private function _timeline()
    {
        $logger = new TimelineLogger($this->_administaratorId);
        $logger->id($this->_store->id())
               ->save('Closed', 'Loan preclosed as on ' . $this->_formValues['closing_date']);
    }
    
}
