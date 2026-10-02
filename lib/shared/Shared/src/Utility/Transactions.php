<?php
/**
 * Shared ReceiptStore Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{Member, Fmc};
use Model\{RPTransMemberDetails, RPTransactionDetails, RPHeads, RPTransMember};
use Web\Utility\DateHelper;
use Shared\Utility\AccountSettings;
use Shared\Model\PairModel;
use Application\Utility\{LoanHelper};

class Transactions
{
    /**
     * 
     * @var \Model\RPTransMember
     */
    private $_model;
    
    /**
     * 
     * @var \Model\RPTransMemberDetails
     */
    private $_receiptEntryModel;
    private $_rpTransModel;
    private $_loanTypeModel;
    private $_headModel;
    /**
     * constructor
     */
    public function __construct()
    {
        $this->_model = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_headModel = new RPHeads();
    }
    
    /**
     * generate voucher
     * 
     * @param array $voucher
     */
    public function generateVoucher($headData, $voucher, $fmc_id)
    {
        //Payment Voucher Generation
        $heads = $this->getHeadID($headData, $fmc_id);
        $voucherNo = $this->getNextVoucherNo($fmc_id);
        $fmcCode = $this->getFmcCode($fmc_id);
        if($voucher['particulars'] == ''){
            $particulars = $heads['name']." - ".$voucher['type'];
        } else {
             $particulars = $voucher['particulars'];
        }
        $transModel = new RPTransactionDetails();
        $voucherInsert = [
            'transNo' => $voucherNo,
            'transDate' => $voucher['transDate'],
           // 'member_id' => $voucher['member_id'],
            'amount'  => $voucher['amount'],
            'headID' => $heads['id'],
            'particulars' => $particulars,
            'code_no' => $fmcCode,
            'page_no' => ($voucher['page_no']) ?? 0,
            'created_by' => $voucher['created_by'],
            'fmc_id' => $fmc_id,
            'transType' => 'P',
            'created_at' => $voucher['created_at'],
            'updated_at' => $voucher['updated_at'], 
        ];
        if($voucher['loan_id']){
           $voucherInsert['loan_id'] = $voucher['loan_id'];
        }
        if(isset($voucher['loan_source_id'])){
           $voucherInsert['loan_source_id'] = $voucher['loan_source_id'];
        }
        if($voucher['transBy']){
           $voucherInsert['transBy'] = $voucher['transBy'];
        } else {
          $voucherInsert['transBy'] = 0;  
        }
        if(isset($voucher['member_id'])){
            $voucherInsert['member_id'] = $voucher['member_id'];
        }
        
        $transModel->insert($voucherInsert);
    }
  
    public function getHeadID($headData, $fmcid)
    {
         //Account entries start here
        $headName = $headData['head_name'];
        $headCode = $headData['code'];
        $heads = [];        
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where code= '{$headCode}' and transType = '{$headData['transType']}' and fmc_id = {$fmcid} order by head_name";
        $rpheads = $this->_headModel->queryRows($headQuery);   
        if(isset($rpheads[0]->id)){
            $heads['id'] = $rpheads[0]->id;
            $heads['name'] = $rpheads[0]->head_name;
        } else {
            //create new head
            $insert = [];
            $insert['head_name'] = $headName;
            $insert['head_alias'] = $headName;
            $insert['code'] = $headData['code'];
            $insert['transType'] = $headData['transType'];
            $insert['opb'] = 0;
            $insert['fmc_id'] = $fmcid;
            $insert['status'] = 'N';
            $heads['id'] = $this->_headModel->insert($insert);
            $heads['name'] = $headName;
        }
        return $heads;
    }
    /**
     * generate Receipt
     * 
     * @param array $receipt
     */
    public function generateReceipt($headData, $receipt, $fmc_id)
    {
        $heads = $this->getHeadID($headData, $fmc_id);
        if(!$receipt['receipt']) {
            $receiptNo = $this->getNextReceiptNo($fmc_id);
        }
        else {
            $receiptNo = $receipt['receipt'];
        }
        $transModel = new RPTransMember();
        //$remarks =  "DCB Re-Finance";
        $insert = [
            'receiptNo' => $receiptNo,
            'transDate' => $receipt['transDate'],
            'member_id' => $receipt['member_id'],
            'remarks' => $receipt['remarks'],
            'created_by' => $receipt['created_by'],
            'fmc_id' => $fmc_id,
            'transBy' => $receipt['transBy'],
            'transType' => 'R',
            'created_at' => $receipt['created_at'],
            'updated_at' => $receipt['updated_at'],
        ];
        if($receipt['loan_id']){
           $insert['loan_id'] = $receipt['loan_id'];
        }
        if($receipt['non_member']){
           $insert['non_member'] = $receipt['non_member'];
        }
        if(isset($receipt['trans_from'])){
            $insert['trans_from'] = $receipt['trans_from'];
        }
        if(isset($receipt['voucher_id'])){
            $insert['voucher_id'] = $receipt['voucher_id'];
        }
        $transid = $transModel->insert($insert);
        $dataModel = new RPTransMemberDetails();
        $outStandingAmount = $receipt['amount'];
        $data = [
                'transID' => $transid,
                'headID' => $heads['id'],
                'amount' => LoanHelper::amount($outStandingAmount),
                'remarks' => $heads['name']
            ];            
        $dataModel->insert($data); 
    }
    
    public function getNextCashReceiptNo($fmc_id)
    {
        $options = new AccountSettings($fmc_id);
        $options->load();
        $cashReceiptNo = $options->getCashReceiptNo();
        if(!$cashReceiptNo){
            $receiptNo = 1;
        }else {
            $receiptNo = $cashReceiptNo + 1;
        }
        return $receiptNo;
    }
    /*
     *  Get Next Voucher No
     * @param $fmc_id
     */
    public function getNextVoucherNo($fmc_id){
        $options = new AccountSettings($fmc_id);
        $options->load();
        $optionVoucherNo = $options->VoucherNo();
        //$fmcCode = $options->code();
        $currentVoucherNo = $options->getNextVoucherNo();
        if(!$currentVoucherNo){
            $voucherNo = $optionVoucherNo;
        }else {
            if($optionVoucherNo > $currentVoucherNo){
                $voucherNo = $optionVoucherNo;
            } else {
                $voucherNo = $currentVoucherNo + 1;
            }
        }
        return $voucherNo;
    }
    
    /*
     * Get next Receipt No
     * @param $fmc_id
     */
    public function getNextReceiptNo($fmc_id){
        $options = new AccountSettings($fmc_id);
        $options->load();
        $optionReceiptNo = $options->receiptNo();
        $currentReceiptNo = $options->getNextReceiptNo();
        if(!$currentReceiptNo){
            $receiptNo = $optionReceiptNo;
        }else {
            if($optionReceiptNo > $currentReceiptNo){
                $receiptNo = $optionReceiptNo;
            } else {
                $receiptNo = $currentReceiptNo + 1;
            }
        }
        return $receiptNo;
    }
    
    public function getFmcCode($fmc_id){
        $options = new AccountSettings($fmc_id);
        $options->load();
        $code = $options->code();
        return $code;
    }

    public function generateCashReceipt($headData, $receipt, $fmc_id)
    {
        $heads = $this->getHeadID($headData, $fmc_id);
        $transModel = new RPTransactionDetails();
        $cashReceiptNo = $this->getNextCashReceiptNo($fmc_id);
        if($receipt['particulars'] !== ''){
            $particulars = $receipt['particulars'];
        } else {
             $particulars = "";
        }
        $options = new AccountSettings($fmc_id);
        $options->load();
        $fmcCode = $options->code();
        $cashReceiptInsert = [
            'transNo' => $cashReceiptNo,
            'transDate' => $receipt['transDate'],
            'amount'  => $receipt['amount'],
            'headID' => $heads['id'],
            'particulars' => $particulars,
            'code_no' => $fmcCode,
            'page_no' => ($receipt['page_no']) ?? 0,
            'created_by' => $receipt['created_by'],
            'fmc_id' => $fmc_id,
            'transType' => 'R',
            'created_at' => $receipt['created_at'],
            'updated_at' => $receipt['updated_at'] 
        ];
        if($receipt['loan_id']){
           $cashReceiptInsert['loan_id'] = $receipt['loan_id'];
        }
        if($receipt['transBy']){
           $cashReceiptInsert['transBy'] = $receipt['transBy'];
        } else {
           $cashReceiptInsert['transBy'] = 0; 
        }
        if(isset($receipt['loan_source_id'])){
           $cashReceiptInsert['loan_source_id'] = $receipt['loan_source_id'];
        }
        
        if(isset($receipt['member_id'])){
            $cashReceiptInsert['member_id'] = $receipt['member_id'];
        }
        $transModel->insert($cashReceiptInsert);
    }
    /**
     * escape html
     * 
     * @param string $string
     * @return string
     */
    private function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
}
