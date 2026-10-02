<?php
/**
 * Application ModifyDcb Edit Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Helper\CommentLogger;

use Web\Form\Form;
use Web\Utility\DateHelper;
use Application\Utility\LoanHelper;
use Model\{Loan, LoanType, RPHeads, LoanSource};
use Shared\Utility\AccountSettings;
use Shared\Utility\Transactions;

class ViewForm extends Form
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
    private $_administratorId;
    private $_loanTypeModel;
    private $_headModel;
    private $_loanSource;
    
    public function __construct()
    {
        parent::__construct();
        $fields = [
            'approval_status',
        ];
        $this->addFields($fields);
        $this->trackChanges();
        $this->_loanTypeModel = new LoanType();
        $this->_headModel = new RPHeads();
        $this->_loanSource = new LoanSource();
        $this->_trans = new Transactions();
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administratorId = $id;
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
     * change loan
     * 
     * @return int
     */
    public function change($store = null, $fmc_id = null, $branch_id = null)
    {
        $transBy = 0;
        $loan = $store->loan();
        $modified = $this->getModified();
        if ($this->_formValues['approval_status'] === 'Pending') {
            return  false;
        } else {
            $update = $modified;
            $update['approval_status'] = $this->_formValues['approval_status'];
            $update['updated_at'] = DateHelper::timestamp();
            $update['last_updated'] = DateHelper::today();
            $update['approved_on'] = DateHelper::timestamp();

            $this->save($update);
            $now = DateHelper::timestamp();
            $transBy = 0;
            if (LoanHelper::LOAN_SOURCE_FMC == $loan->loan_source_id) {                
                // Payment Voucher
                $loan = $store->loan();
                $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
                $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
                $code = $loanTypeCode[0]->code;
                $headData['head_name'] =  $loanTypeCode[0]->loan_type;
                $headData['code'] = $code;
                $headData['transType'] = 'Direct Expense';
                 // Payment Entries
                if($loan->transBy > 0){
                    $transBy = $loan->transBy;
                }
                $amount = $loan->loan_amount;
                $insert = [
                    'transDate' => $loan->loan_date,
                    'member_id' => $loan->member_id,
                    'amount'  => $amount,
                    'page_no' =>  1,
                    'created_by' => $this->_administratorId,
                    'transBy' => $transBy,
                    'created_at' => $now,
                    'updated_at' => $now, 
                    'loan_id' => $loan->id,
                    'type' => 'MF Loan'
                ];
                $this->_trans->generateVoucher($headData, $insert, $fmc_id);
            } else {
            //MF Account
                $fmc_id = LoanHelper::MF_ACCOUNT_ID;
                $loan = $store->loan();
               //Receipt Creation
                $loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$loan->loan_source_id}";
                $loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
                $loan_source = $loanSourceType[0]->loan_source;
                $particulars = $loan_source ." Loan Receipt";
                if($loan->transBy > 0){                
                    $rpheads = $this->_headModel->checkByColumnByFmc('id', $loan->transBy, $fmc_id);
                    if(isset($rpheads->id)){
                        $mfHeadData['head_name'] =  $rpheads->head_name ." Loan";
                        $mfHeadData['code'] = str_replace(" ", "_", $mfHeadData['head_name']);                
                    }
                    $mfHeadData['transType'] = 'Direct Income';
                    $insert = [
                        'transDate' => $loan->loan_date,
                        'member_id' => $loan->member_id,
                        'amount'  => $loan->loan_amount,
                        'particulars' => $particulars,
                        'page_no' =>  1,
                        'created_by' => $this->_administratorId,
                        'transBy' => $loan->transBy,
                        'created_at' => $now,
                        'updated_at' => $now, 
                        'loan_id' => $loan->id,
                    ];
                    $this->_trans->generateCashReceipt($mfHeadData, $insert, $fmc_id);
                }
                 //Payment Voucher Generation
                $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
                $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
                $code = $loanTypeCode[0]->code;
                $headData['head_name'] =  $loanTypeCode[0]->loan_type. " Loan";
                $headData['code'] = $code."_loan";
                $headData['transType'] = 'Direct Expense';
                if($loan->transBy > 0){
                    $transBy = $loan->transBy;
                }
                $particulars = $loan_source ." Loan Payment";
                $insert = [
                    'transDate' => $loan->loan_date,
                    'member_id' => $loan->member_id,
                    'amount'  => $loan->loan_amount,
                    'particulars' => $particulars,
                    'page_no' =>  1,
                    'created_by' => $this->_administratorId,
                    'transBy' => $transBy,
                    'created_at' => $now,
                    'updated_at' => $now, 
                    'loan_id' => $loan->id,
                ];
                $this->_trans->generateVoucher($headData, $insert, $fmc_id);
                //FMC Account
                if($branch_id) {
                    $loanSourceQuery = "select loan_source, code from  " . $this->_loanSource->getTableQuoted()." where id = {$loan->loan_source_id}";
                    $loanSourceType = $this->_loanSource->queryRows($loanSourceQuery);
                    $loan_source = $loanSourceType[0]->loan_source;
                    $particulars = $loan_source ." Loan Receipt";
                    $fmc_id = $branch_id;
                    if($loan->transBy > 0){                
                        $insert = [
                            'transDate' => $loan->loan_date,                   
                            'amount'  => $loan->loan_amount,
                            'member_id' => $loan->member_id,
                            'particulars' => $particulars,
                            'page_no' =>  1,
                            'created_by' => $this->_administratorId,
                            'transBy' => $loan->transBy,
                            'created_at' => $now,
                            'updated_at' => $now, 
                            'loan_id' => $loan->id,
                        ];
                        $this->_trans->generateCashReceipt($mfHeadData, $insert, $fmc_id);
                    }
                     //Payment Voucher Generation
                $loanTypeQuery = "select loan_type, code from  " . $this->_loanTypeModel->getTableQuoted()." where id = {$loan->loan_type_id}";
                $loanTypeCode = $this->_loanTypeModel->queryRows($loanTypeQuery);
                $code = $loanTypeCode[0]->code;
                $headData['head_name'] =  $loanTypeCode[0]->loan_type. " Loan";
                $headData['code'] = $code."_loan";
                $headData['transType'] = 'Direct Expense';
                 // Payment Entries
                if($loan->transBy > 0){
                    $transBy = $loan->transBy;
                }
                $particulars = $loan_source ." Loan Payment";
                $insert = [
                    'transDate' => $loan->loan_date,
                    'member_id' => $loan->member_id,
                    'amount'  => $loan->loan_amount,
                    'particulars' => $particulars,
                    'page_no' =>  1,
                    'created_by' => $this->_administratorId,
                    'transBy' => $transBy,
                    'created_at' => $now,
                    'updated_at' => $now, 
                    'loan_id' => $loan->id,
                ];
                $this->_trans->generateVoucher($headData, $insert, $fmc_id);
                }
            }
            return true;
        }
    }
    /**
     * save comment
     * 
     * @return null
     */
    private function _saveComment()
    {
        if (empty($this->_formValues['note'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administratorId);
        $logger->id($this->getId())
               ->save($this->_formValues['note']);
    }
    
    
}
