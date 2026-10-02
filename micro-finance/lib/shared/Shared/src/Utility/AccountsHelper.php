<?php
/**
 * Shared AccountsHelper Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{RPHeads};
use Model\{RPTransMemberDetails, RPTransMember, RPTransactionDetails, Member};
use Shared\Constant\DeductionConfig;
use Shared\Utility\AccountSettings;
use Web\Utility\DateHelper;


class AccountsHelper
{
    const CONVERSION_NO = 0;
    const CONVERSION_YES = 1;
    const CONVERSION_PERCENTAGE = 2;
    const CONVERSION_PER_BILL = 3;
    const CONVERSION_FIXED = 4;
    
    const GROUP_CONTRIBUTION = 1;
    const GROUP_LOAN = 2;
    const GROUP_SAVINGS = 3;
    
    /**
     * 
     * @var int
     */
    private $_fmcid;
     /**
     * constructor
     */
    public function __construct($fmcid = 0)
    {
        $this->_fmcid = $fmcid;
        $this->_headModel = new RPHeads();
        $this->_sql = $this->_headModel->getSql();
    }
    /**
     * get conversion options
     * 
     * @return array
     */
    public static function conversionOptions()
    {
        return [
            self::CONVERSION_NO => 'No',
            self::CONVERSION_YES => 'Yes',
            self::CONVERSION_PERCENTAGE => 'Percentage',
            self::CONVERSION_PER_BILL => 'Amount Per Bill',
            self::CONVERSION_FIXED => 'Fixed Amount',
        ];
    }
    /*
     * get head ID
     */
    public function getAccountHeadByID($head_name)
    {
        
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcid)
                   ->whereEqual('head_name', $head_name);
        $heads = $this->_sql->getWhere();
        return $heads;
    }
    public function getHeads(){
        $sql = $this->_headModel->getSql();
        $sql->clear();
        $sql->whereEqual('fmc_id', $this->_fmcid);
        $where = $sql->getWhere();
        $heads = $this->_headModel->select($where);
        return $heads;
    }

    public function getHeadID($headData)
    {
         //Account entries start here
        $headName = $headData['name'];
        $heads = [];
        $headCode = $headData['code'];
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where code= '{$headCode}' and transType = '{$headData['transType']}' and fmc_id = {$this->_fmcid} order by head_name";
        $rpheads = $this->_headModel->queryRows($headQuery);   
        if(isset($rpheads[0]->id)){
            $heads['id'] = $rpheads[0]->id;
            $heads['name'] = $rpheads[0]->head_name;
        }
        else {
            //create new head
            $insert = [];
            $insert['head_name'] = $headName;
            $insert['head_alias'] = $headName;
            $insert['code'] = $headData['code'];
            $insert['transType'] = $headData['transType'];
            $insert['opb'] = 0;
            $insert['fmc_id'] = $this->_fmcid;
            $insert['status'] = 'N';
            $heads['id'] = $this->_headModel->insert($insert);
            $heads['name'] = $headName;
        }
        return $heads;
    }

    public function getNewVoucherNo()
    {
        $voucherNo = 0;
        $options = new AccountSettings($this->_fmcid);
        $options->load();
        $optionVoucherNo = $options->VoucherNo();
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
    Payment transaction push to rp_transactiondetails table
    */
    public function deductionPayment($auctionChit)
    {
       
        //$voucherNo = $this->getNewVoucherNo();
        $voucherNo = 0;
        $headData = [];
        $headData['name'] = 'Member Advance';
        $headData['code'] = "memb_advance";
        $headData['transType'] = 'Direct Expense';
         // Payment Entries
        $heads = $this->getHeadID($headData, $this->_fmcid);
        $today = DateHelper::today();
        $transModel = new RPTransactionDetails();
        $insert = [
            'action_chit_id' => $auctionChit->id,
            'fmc_id' => $this->_fmcid,
            'donor_id' => 0,
            'member_id' => $auctionChit->member_id,
            'transType' =>'P',
            'transBy' => 0,
            'transDate' => $today,
            'headID' => $heads['id'],
            'amount' => $auctionChit->advance,
            'particulars' => 'Member Advance through deduction bill'
           // 'transNo' =>$voucherNo
        ];
        $transid = $transModel->insert($insert); 
    }

     /*
    Payment transaction push to rp_transactiondetails table
    */
    public function deductionBalancePayment($auctionChit)
    {
       
        $voucherNo = $this->getNewVoucherNo();
        $headData = [];
        $headData['name'] = 'Member Payment';
        $headData['code'] = "memb_payment";
        $headData['transType'] = 'Direct Expense';
         // Payment Entries
        $heads = $this->getHeadID($headData, $this->_fmcid);
        $today = DateHelper::today();
        $transModel = new RPTransactionDetails();
        $insert = [
            'action_chit_id' => $auctionChit->id,
            'fmc_id' => $this->_fmcid,
            'donor_id' => 0,
            'member_id' => $auctionChit->member_id,
            'transType' =>'P',
            'transBy' => 0,
            'transDate' => $today,
            'headID' => $heads['id'],
            'amount' => $auctionChit->balance,
            'particulars' => $heads['name']
           // 'transNo' =>$voucherNo
        ];
        $transid = $transModel->insert($insert); 
    }
     /*
     * Payment push to rp_trans_member and rp_trans_member_details tables
     */
    public function deductionPaymentTransactions($auctionChit)
    {
        $headData = [];
        $headData['name'] = 'Member Advance';
        $headData['code'] = "memb_advance";
        $headData['transType'] = 'Direct Expense';
         // Payment Entries
        $heads = $this->getHeadID($headData, $this->_fmcid);
        $today = DateHelper::today();
        $masterModel = new RPTransMember();
        $insert = [
            'action_chit_id' => $auctionChit->id,
            'fmc_id' => $this->_fmcid,
            'donor_id' => 0,
            'member_id' => $auctionChit->member_id,
            'transType' =>'P',
            'transBy' => 0,
            'transDate' => $today
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        $insert = [];
        $insert = [
            'transID' => $transid,
            'headID' => $heads['id'],
            'amount' => $auctionChit->advance,
            'remarks' => $heads['name']
        ];
        $dataModel->insert($insert); 
    }
    
      /*
     * get head ID
     */
    public function deductionReceiptTransactions($auctionChit)
    {
        $headData = [];
        $memberModel = new Member();
        $options = new AccountSettings($this->_fmcid);
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
        $member = $memberModel->find($auctionChit->donor_id);
        $name = explode(" ", $member->name);
            //Auction collection
        $headName = "{$member->code}-".$name[0]."-Auct Collection";
        //$headData['name'] = "{$code}-Auctioneer Collection";
        $headData['name'] = $headName;
        $headData['code'] = $member->code;
        $headData['transType'] = 'Direct Income';
         // Payment Entries
        $heads = $this->getHeadID($headData, $this->_fmcid);
         // Payment Entries
        $today = DateHelper::today();
        $masterModel = new RPTransMember();
        $insert = [
          //  'receiptNo' => $receiptNo,
            'action_chit_id' => $auctionChit->id,
            'fmc_id' => $this->_fmcid,
            'donor_id' => $auctionChit->donor_id,
            'member_id' => 0,
            'transType' =>'R',
            'transBy' => 0,
            'transDate' => $today
        ];
        $transid = $masterModel->insert($insert); 
        $dataModel = new RPTransMemberDetails();
        $insert = [];
        $insert = [
            'transID' => $transid,
            'headID' => $heads['id'],
            'amount' => $auctionChit->advance,
            'remarks' => $heads['name']
        ];
        $dataModel->insert($insert); 
    }
}
