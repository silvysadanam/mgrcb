<?php
/**
 * Application ModifyDcb WriteOff Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Utility\LoanHelper;
use Application\Helper\{CommentLogger, TimelineLogger};

use Model\{LoanSummary, WriteOff};

use Web\Form\Form;
use Web\Utility\DateHelper;


class WriteOffForm extends Form
{    
    /**
     * 
     * @var int
     */
    private $_loanId;
    
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
            'write_off_date',
            'write_off_interest',
            'write_off_amount',
            'member_paid_amount',
            'member_receipt_number',
            'member_paid_date',
            'balance_written_off_amount',
            'fmc_voucher_number',
            'fmc_voucher_date',
            'fmc_lr_receipt_number',
            'fmc_lr_receipt_date',
            'write_off_comments',
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
        
        if ($this->validator->isNull($this->_formValues['write_off_date'])) {
            $this->error->required('Write off date');
        }
        
        $this->_validateAmount();
        
        return $this->noError();
    }
    
    /**
     * write off loan
     * 
     * @return boolean
     */
    public function change()
    {
        $this->_loanId = $this->_store->id();
        $loanSummary = $this->_store->loanSummary();
        $loan = $this->_store->loan();
        
        $now = DateHelper::timestamp();
        $summaryUpdate = [
            'written_off' => 1,
            'write_off_date' => DateHelper::fromDatepicker($this->_formValues['write_off_date']),
            'modified_at' => $now,
        ];
        
        $summaryModel = new LoanSummary();
        $summaryId = $summaryModel->selectIdByColumn('loan_id', $this->_loanId);
        $summaryModel->updateById($summaryUpdate, $summaryId);
        
        $writeOffModel = new WriteOff();
        
        $insert = [
            'loan_id' => $this->_loanId,
            'write_off_date' => $summaryUpdate['write_off_date'],
            'tenure_end_date' => $loan->end_date,
            'interest_amount' => $this->_formValues['write_off_interest'],
            'outstanding_amount' => $loanSummary->balance_actual_principal_interest_outstanding,
            'total_write_off_amount' => $this->_formValues['write_off_amount'],
            'member_paid_amount' => $this->_formValues['member_paid_amount'],
            'balance_written_off_amount' => $this->_formValues['balance_written_off_amount'],
            'created_by' => $this->_administaratorId,
            'created_at' => $now,
            'comments' => $this->_formValues['write_off_comments'],
        ];
        
        $direct = [
            'member_receipt_number', 'fmc_voucher_number', 'fmc_lr_receipt_number',
        ];
        foreach ($direct as $field) {
            $insert[$field] = $this->_formValues[$field];
        }
        
        $dates = ['member_paid_date', 'fmc_voucher_date', 'fmc_lr_receipt_date'];
        foreach ($dates as $date) {
            if ('' != $this->_formValues[$date]) {
                $insert[$date] = DateHelper::fromDatepicker($this->_formValues[$date]);
            }
        }

        $writeOffModel->insert($insert);
        
        $update = [
            'loan_status_id' => LoanHelper::STATUS_WRITTEN_OFF,
            'updated_at' => $now,
            'last_updated' => DateHelper::today(),
        ];
        $this->getModel()->updateById($update, $this->_loanId);
        
        $this->_store->clearPqam($summaryUpdate['write_off_date']);
        
        $logger = new TimelineLogger($this->_administaratorId);
        $logger->id($this->_loanId)
               ->save('DCB written off', 'Loan written off as on ' . $this->_formValues['write_off_date']);
        
        $this->_saveComment();

        return true;
    }
    
    
    /**
     * validate amount
     * 
     * @return null
     */
    private function _validateAmount()
    {
        if (! $this->validator->isNull($this->_formValues['member_paid_amount'])) {
            if (! $this->validator->decimalPoints($this->_formValues['member_paid_amount'], 2)){
                $this->error->invalid('Amount paid by member');
            }
        }
        
        if ($this->_formValues['member_paid_amount'] > $this->_formValues['write_off_amount']) {
            $this->error->add('Member paid amount cannot be more than total write off amount');
        }
        
    }
    
    /**
     * save comment
     * 
     * @return null
     */
    private function _saveComment()
    {
        if (empty($this->_formValues['write_off_comments'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administaratorId);
        $logger->id($this->_loanId)
               ->save($this->_formValues['write_off_comments']);
    }
    
}
