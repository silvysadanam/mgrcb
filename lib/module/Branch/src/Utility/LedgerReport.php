<?php
/**
 * Branch Deduction Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;
use Model\{RPTransMember, RPTransMemberDetails, RPTransactionDetails, RPHeads, Member};
use Model\DeductionMemberDaily;
use Shared\Utility\ReportHelper;
use Web\Utility\DateHelper;
use Branch\Form\Deduction\DeductionReportForm;
use Branch\Utility\DeductionReport;
use Model\DeductionType;

class LedgerReport
{
    /**
     * 
     * @var \Branch\Utility\BranchStore
     */
    private $_branch;
    
    /**
     *
     * @var \Model\DeductionMemberDaily 
     */
    private $_model;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_startDate = '';
    
    /**
     * 
     * @var string
     */
    private $_endDate = '';
    
    /**
     * 
     * @var int
     */
    private $_groupId = 0;
    private $_membTrans;
    private $_headModel;
    private $_transactionModel;
    private $_member;
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    public function __construct($branch)
    {
        $this->_branch = $branch;
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_member = new Member();
    }
    
    /**
     * set start date
     * @param string $date
     * @return $this
     */
    public function setStartDate($date)
    {
        $this->_startDate = $date;
        return $this;
    }
    
    /**
     * set emd date
     * 
     * @param string $date
     * @return $this
     */
    public function setEndDate($date)
    {
        $this->_endDate = $date;
        return $this;
    }
    
    /**
     * set Head id
     * 
     * @param int $id
     * @return $this
     */
    public function setHeadId($id)
    {
        $this->_headId = $id;
        return $this;
    }
    /**
     * get report
     * 
     * @return array
     */
    public function report()
    {
        return $this->_report;
    }
    
    /**
     * run report
     * 
     * @return boolean
     */
    public function run()
    {
        $this->_preload();
        $this->_loadReport();
       // if (0 == $this->_report['total']) {
       //     return false;
       // }
        $this->_report['total'] = 1;
        return true;
    }
    
    private function _loadReport()
    {
        $this->_sql->clear();
        $fmc_id = $this->_branch->id();
        $paymentTransactions = [];
        $this->_report['opb'] = 0;
        $this->_report['report'] = [];
        $bankTransTotal = [];
        $cashTotal = 0;
        $reportTotal = 0;
        $cashTransTotal = [];
        $cashPaymentTotal = 0;
        $receiptBankTotal = 0;
        $cashClosing = 0;
        $byBankTotal = 0;
        $bycashTotal = 0;
        $bankTotal = 0;
        $bankClosing = 0 ;
        $toBankPaymentTotal = 0;
        $getMemberReceipts = [];
        $paymentTotal = 0;
        $cashReciptTotal = 0;
        $x = 0;
        /*$this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereIn('deduction_type_id', $this->_report['deductions'])
                   ->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where = $this->_sql->getWhere();
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }*/
        //Get Head Type
        $cashOpening = $this->getCashOpening();
        $headOpening = $this->getHeadOpening($this->_headId);
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where id={$this->_headId} and fmc_id = {$fmc_id} order by head_name";
        $heads = $this->_headModel->queryRows($headQuery);
        $bankOpening = $this->getBankOpening($this->_headId);
        //Similar opposit heads
        $matchingHeadId = 0;
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where id={$this->_headId} and fmc_id = {$fmc_id} order by head_name";
        $heads = $this->_headModel->queryRows($headQuery);
        $cumulativeIncomeSum = 0;
        $cumulativePaymentSum = 0;
        $catchTotal = 0;
        $memberAdvancePaid = 0;
        $donorCashTotal = 0;
        //Check donor ledger
        $donorQuery = "select * from " . $this->_member->getTableQuoted()." where code='{$heads[0]->code}' and fmc_id = {$fmc_id} and member_type_id = 2";
        $donor = $this->_member->queryRows($donorQuery);
        if(isset($donor[0]->id)){
            $openingBalance = $this->getAuctioneerOpening($this->_headId);
            $donorCollectionAmount = 0;
            $auctionAdvancePaid = 0;
            $auctionChitopening = 0;
            $MemberAdvanceHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='Member Advance' and code='memb_advance' and fmc_id = {$fmc_id} order by head_name";
            $advanceHeads = $this->_headModel->queryRows($MemberAdvanceHeadQuery);
            $memberAdvanceHeadID = $advanceHeads[0]->id;
            $reportTotal = 100;
            $openingChits = $this->auctionChitIdsOpening($donor[0]->id, $fmc_id);
            if(is_array($openingChits)){
                $auctionChitopening = $this->periodSumOpening($openingChits); //Auction chit amount    
                $auctionAdvancePaid = $this->periodSumOpeningByHeadID($openingChits, $memberAdvanceHeadID);
                $donorCollectionAmount = $this->getAuctioneerCollectionOpening($donor[0]->id, $this->_headId);
            }
            //Donor Collection            
            $donorTotalRepayment = $donorCollectionAmount + $auctionAdvancePaid;
            $auctionChitopening +=  $openingBalance;  
            $auctionChitopening -= $donorTotalRepayment;
            
            $auctionChitIDs = $this->auctionChitIds($donor[0]->id, $fmc_id);
            if(is_array($auctionChitIDs)){
                $catchTotal = $this->periodSum($auctionChitIDs);
                $memberAdvancePaid = $this->periodSumOpeningByHeadID($auctionChitIDs, $memberAdvanceHeadID);
            }
            $this->_report['opb'] =  $auctionChitopening;
            
             //Member Receipts
                $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='{$heads[0]->head_name}' and fmc_id = {$fmc_id} and transType in('Indirect Expense', 'Direct Expense') order by head_name";
                $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                if(isset($otherHeads[0]->id)){
                    $matchingHeadId = $otherHeads[0]->id;
                    $paymentTransactions = $this->getPaymentTransactions($matchingHeadId);
                }           
                 $deductionTypeOpening = $this->deductionOpeningAmount($this->_headId, $fmc_id);
                    if($deductionTypeOpening > 0) {
                        $cumulativeIncomeSum = $this->getCumulativeByHeadID($this->_headId);
                        $cumulativePaymentSum = $this->cumulativePaymentyHeadID($matchingHeadId);
                        $this->_report['opb'] = ($deductionTypeOpening + $cumulativeIncomeSum) - $cumulativePaymentSum;
                  }
                 $memberTransaction = $this->getMemberTransactions($this->_headId);
                 $incomeTransaction = $this->getIncomeTransactions($this->_headId);
                 $donorCashTotal = 0;
                 if($memberTransaction){ 
                    foreach ($memberTransaction as $item) 
                    {      
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                                if($item->receiptNo){
                                    $donorCashTotal += $item->amount;
                                }
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->remarks;
                       if($item->action_chit_id){
                        $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                       } else if ($item->loan_id){
                           $this->_report['report'][$x]['transNo'] = $item->loan_id;
                       }
                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                       $reportTotal += $item->amount;
                       $cashReciptTotal += $item->amount;
                       $x++;
                    }
                }

                if($incomeTransaction){ 
                    foreach ($incomeTransaction as $item) 
                    {              
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;

                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                        $reportTotal += $item->amount;
                        $cashReciptTotal += $item->amount;
                        $x++;
                    }
                }

                if($matchingHeadId > 0){ 
                    foreach ($paymentTransactions as $item) 
                    {              
                       $this->_report['report'][$x]['cash'] =  0;
                       $this->_report['report'][$x]['bank'] =  0;
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;
                       if($item->transBy > 0) {
                                $this->_report['report'][$x]['bybank'] =  $item->amount;
                                $this->_report['report'][$x]['bycash'] =  0;
                                $byBankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['bycash'] =  $item->amount;
                                $this->_report['report'][$x]['bybank'] = 0;
                                $bycashTotal += $item->amount;
                       }    
                        $reportTotal += $item->amount;
                        $paymentTotal += $item->amount;
                        $x++;
                    }
                }
                if($this->_report['report']){
                    array_multisort(array_column($this->_report['report'], 'transDate'), SORT_ASC, $this->_report['report']);
                }
                $receiptPaymentTotal = $this->_report['opb'] + $catchTotal;
                if($receiptPaymentTotal > 0) {
                    $cashClosing = ($this->_report['opb'] + $catchTotal) -  ($memberAdvancePaid + $donorCashTotal);
                } else {
                    $cashClosing = $paymentTotal;
                }
           
           // print_r($data);
        } else {
            if($heads[0]->transType == 'Direct Expense' || $heads[0]->transType == 'Indirect Expense'){
                $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='{$heads[0]->head_name}' and fmc_id = {$fmc_id} and transType in('Indirect Income', 'Direct Income') order by head_name";
                $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                if(isset($otherHeads[0]->id)){
                    $matchingHeadId = $otherHeads[0]->id;  
                    $memberTransaction = $this->getMemberTransactions($matchingHeadId);
                    $incomeTransaction = $this->getIncomeTransactions($matchingHeadId);
                    $deductionTypeOpening = $this->deductionOpeningAmount($matchingHeadId, $fmc_id);
                    if($deductionTypeOpening > 0) {
                        $cumulativeIncomeSum = $this->getCumulativeByHeadID($matchingHeadId);
                        $cumulativePaymentSum = $this->cumulativePaymentyHeadID($this->_headId);
                        $this->_report['opb'] = ($deductionTypeOpening + $cumulativeIncomeSum) - $cumulativePaymentSum;
                    } else {
                        $this->_report['opb'] =  $this->getHeadOpening($matchingHeadId);
                    }
                }   
                $paymentTransactions = $this->getPaymentTransactions($this->_headId);
                if($paymentTransactions){ 
                    foreach ($paymentTransactions as $item) 
                    {              
                       $this->_report['report'][$x]['cash'] =  0;
                       $this->_report['report'][$x]['bank'] =  0;
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;
                       if($item->transBy > 0) {
                                $this->_report['report'][$x]['bybank'] =  $item->amount;
                                $this->_report['report'][$x]['bycash'] =  0;
                                $byBankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['bycash'] =  $item->amount;
                                $this->_report['report'][$x]['bybank'] = 0;
                                $bycashTotal += $item->amount;
                       }    
                        $reportTotal += $item->amount;
                        $paymentTotal += $item->amount;
                        $x++;
                    }
                }
                if($matchingHeadId > 0){ 
                    foreach ($memberTransaction as $item) 
                    {      
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->remarks;
                       if($item->action_chit_id){
                        $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                       } else if ($item->loan_id){
                           $this->_report['report'][$x]['transNo'] = $item->loan_id;
                       }
                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                       $reportTotal += $item->amount;
                       $cashReciptTotal += $item->amount;
                       $x++;
                    }
                }
                if($matchingHeadId > 0){ 
                    foreach ($incomeTransaction as $item) 
                    {              
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;

                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                        $reportTotal += $item->amount;
                        $cashReciptTotal += $item->amount;
                        $x++;
                    }
                }
                  // Loan Repaid
                if($heads[0]->head_name == 'Bank' && $heads[0]->code == 'exp_bank_loan'){
                    $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where code like '%_loan_repaid%' and fmc_id = {$fmc_id} and transType ='Direct Expense' order by head_name";
                    $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                    foreach ($otherHeads as $ohead){
                        $paymentTransactions = $this->getPaymentTransactions($ohead->id);
                        if($paymentTransactions){
                         foreach ($paymentTransactions as $item) 
                            {              
                               $this->_report['report'][$x]['cash'] =  0;
                               $this->_report['report'][$x]['bank'] =  0;
                               $this->_report['report'][$x]['transNo'] = $item->transNo;
                               $this->_report['report'][$x]['transDate'] = $item->transDate;
                               $this->_report['report'][$x]['name'] = $item->name;
                               $this->_report['report'][$x]['particulars'] = $item->particulars;
                               if($item->transBy > 0) {
                                        $this->_report['report'][$x]['bybank'] =  $item->amount;
                                        $this->_report['report'][$x]['bycash'] =  0;
                                        $byBankTotal += $item->amount;
                               } else {
                                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                                        $this->_report['report'][$x]['bybank'] = 0;
                                        $bycashTotal += $item->amount;
                               }    
                                $reportTotal += $item->amount;
                                $paymentTotal += $item->amount;
                                $x++;
                            }
                        }
                    }
                     // Preclosure
                    $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where code like '%_loan_pre_closure%' and fmc_id = {$fmc_id} and transType ='Direct Expense' order by head_name";
                    $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                    foreach ($otherHeads as $ohead){
                        $paymentTransactions = $this->getPaymentTransactions($ohead->id);
                        if($paymentTransactions){
                         foreach ($paymentTransactions as $item) 
                            {              
                               $this->_report['report'][$x]['cash'] =  0;
                               $this->_report['report'][$x]['bank'] =  0;
                               $this->_report['report'][$x]['transNo'] = $item->transNo;
                               $this->_report['report'][$x]['transDate'] = $item->transDate;
                               $this->_report['report'][$x]['name'] = $item->name;
                               $this->_report['report'][$x]['particulars'] = $item->particulars;
                               if($item->transBy > 0) {
                                        $this->_report['report'][$x]['bybank'] =  $item->amount;
                                        $this->_report['report'][$x]['bycash'] =  0;
                                        $byBankTotal += $item->amount;
                               } else {
                                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                                        $this->_report['report'][$x]['bybank'] = 0;
                                        $bycashTotal += $item->amount;
                               }    
                                $reportTotal += $item->amount;
                                $paymentTotal += $item->amount;
                                $x++;
                            }
                        }
                    }
                }
                if($this->_report['report']){
                    array_multisort(array_column($this->_report['report'], 'transDate'), SORT_ASC, $this->_report['report']);
                }
                $receiptPaymentTotal = $this->_report['opb'] + $cashReciptTotal;
                if($receiptPaymentTotal > 0) {
                    $cashClosing = ($this->_report['opb'] + $cashReciptTotal) -  $paymentTotal;
                } else {
                    $cashClosing = $paymentTotal;
                }
                $this->_report['opb'] = number_format($this->_report['opb'], 2);
            } else  if($heads[0]->transType == 'Direct Income' || $heads[0]->transType == 'Indirect Income'){
                $this->_report['opb'] =  $headOpening;
                //Member Receipts
                $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where head_name='{$heads[0]->head_name}' and fmc_id = {$fmc_id} and transType in('Indirect Expense', 'Direct Expense') order by head_name";
                $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                if(isset($otherHeads[0]->id)){
                    $matchingHeadId = $otherHeads[0]->id;
                    $paymentTransactions = $this->getPaymentTransactions($matchingHeadId);
                }           
                 $deductionTypeOpening = $this->deductionOpeningAmount($this->_headId, $fmc_id);
                    if($deductionTypeOpening > 0) {
                        $cumulativeIncomeSum = $this->getCumulativeByHeadID($this->_headId);
                        $cumulativePaymentSum = $this->cumulativePaymentyHeadID($matchingHeadId);
                        $this->_report['opb'] = ($deductionTypeOpening + $cumulativeIncomeSum) - $cumulativePaymentSum;
                  }
                 $memberTransaction = $this->getMemberTransactions($this->_headId);
                 $incomeTransaction = $this->getIncomeTransactions($this->_headId);
                 if($memberTransaction){ 
                    foreach ($memberTransaction as $item) 
                    {      
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->remarks;
                       if($item->action_chit_id){
                        $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                       } else if ($item->loan_id){
                           $this->_report['report'][$x]['transNo'] = $item->loan_id;
                       }
                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                       $reportTotal += $item->amount;
                       $cashReciptTotal += $item->amount;
                       $x++;
                    }
                }

                if($incomeTransaction){ 
                    foreach ($incomeTransaction as $item) 
                    {              
                        if($item->transBy > 0) {
                                $this->_report['report'][$x]['bank'] =  $item->amount;
                                $this->_report['report'][$x]['cash'] = 0;
                                $bankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['cash'] =  $item->amount;
                                $this->_report['report'][$x]['bank'] =0;
                                $cashTotal += $item->amount;
                       }    
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;

                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                        $reportTotal += $item->amount;
                        $cashReciptTotal += $item->amount;
                        $x++;
                    }
                }

                if($matchingHeadId > 0){ 
                    foreach ($paymentTransactions as $item) 
                    {              
                       $this->_report['report'][$x]['cash'] =  0;
                       $this->_report['report'][$x]['bank'] =  0;
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->particulars;
                       if($item->transBy > 0) {
                                $this->_report['report'][$x]['bybank'] =  $item->amount;
                                $this->_report['report'][$x]['bycash'] =  0;
                                $byBankTotal += $item->amount;
                       } else {
                                $this->_report['report'][$x]['bycash'] =  $item->amount;
                                $this->_report['report'][$x]['bybank'] = 0;
                                $bycashTotal += $item->amount;
                       }    
                        $reportTotal += $item->amount;
                        $paymentTotal += $item->amount;
                        $x++;
                    }
                }
                 // Loan Repaid
                if($heads[0]->head_name == 'Bank' && $heads[0]->code == 'bank_loan'){
                    $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where code like '%_loan_repaid%' and fmc_id = {$fmc_id} and transType ='Direct Expense' order by head_name";
                    $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                    foreach ($otherHeads as $ohead){
                        $paymentTransactions = $this->getPaymentTransactions($ohead->id);
                        if($paymentTransactions){
                         foreach ($paymentTransactions as $item) 
                            {              
                               $this->_report['report'][$x]['cash'] =  0;
                               $this->_report['report'][$x]['bank'] =  0;
                               $this->_report['report'][$x]['transNo'] = $item->transNo;
                               $this->_report['report'][$x]['transDate'] = $item->transDate;
                               $this->_report['report'][$x]['name'] = $item->name;
                               $this->_report['report'][$x]['particulars'] = $item->particulars;
                               if($item->transBy > 0) {
                                        $this->_report['report'][$x]['bybank'] =  $item->amount;
                                        $this->_report['report'][$x]['bycash'] =  0;
                                        $byBankTotal += $item->amount;
                               } else {
                                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                                        $this->_report['report'][$x]['bybank'] = 0;
                                        $bycashTotal += $item->amount;
                               }    
                                $reportTotal += $item->amount;
                                $paymentTotal += $item->amount;
                                $x++;
                            }
                        }
                    }
                    // Preclosure
                    $otherHeadQuery = "select * from " . $this->_headModel->getTableQuoted()." where code like '%_loan_pre_closure%' and fmc_id = {$fmc_id} and transType ='Direct Expense' order by head_name";
                    $otherHeads = $this->_headModel->queryRows($otherHeadQuery);
                    foreach ($otherHeads as $ohead){
                        $paymentTransactions = $this->getPaymentTransactions($ohead->id);
                        if($paymentTransactions){
                         foreach ($paymentTransactions as $item) 
                            {              
                               $this->_report['report'][$x]['cash'] =  0;
                               $this->_report['report'][$x]['bank'] =  0;
                               $this->_report['report'][$x]['transNo'] = $item->transNo;
                               $this->_report['report'][$x]['transDate'] = $item->transDate;
                               $this->_report['report'][$x]['name'] = $item->name;
                               $this->_report['report'][$x]['particulars'] = $item->particulars;
                               if($item->transBy > 0) {
                                        $this->_report['report'][$x]['bybank'] =  $item->amount;
                                        $this->_report['report'][$x]['bycash'] =  0;
                                        $byBankTotal += $item->amount;
                               } else {
                                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                                        $this->_report['report'][$x]['bybank'] = 0;
                                        $bycashTotal += $item->amount;
                               }    
                                $reportTotal += $item->amount;
                                $paymentTotal += $item->amount;
                                $x++;
                            }
                        }
                    }
                }
                if($this->_report['report']){
                    array_multisort(array_column($this->_report['report'], 'transDate'), SORT_ASC, $this->_report['report']);
                }
               $receiptPaymentTotal = $this->_report['opb'] + $cashReciptTotal;
                if($receiptPaymentTotal > 0) {
                    $cashClosing = ($this->_report['opb'] + $cashReciptTotal) -  $paymentTotal;
                } else {
                    $cashClosing = $paymentTotal;
                }
                 $this->_report['opb'] = number_format($this->_report['opb'], 2);
            } else if($heads[0]->transType == 'Cash'){
                $cashTransTotal = $this->getCashTransactions($cashOpening);
                $reportTotal = $cashTransTotal['cashTotal'] + $cashTransTotal['cashPaymentTotal'] + $cashOpening;
                $cashTotal = $cashTransTotal['cashTotal'];
                $cashPaymentTotal = $cashTransTotal['cashPaymentTotal'];
                $cashClosing = $cashTotal - $cashPaymentTotal;  
                $this->_report['opb'] = number_format($cashOpening, 2);
                $bycashTotal = $cashTransTotal['cashPaymentTotal'];
            } else if($heads[0]->transType == 'Bank'){
                $bankTransTotal = $this->getBankTransactions($this->_headId);
                $reportTotal = $bankTransTotal['toBankPaymentTotal'] + $bankTransTotal['byBankPaymentTotal'] + $bankOpening;
                $toBankPaymentTotal = $bankTransTotal['toBankPaymentTotal'];
                $byBankTotal = $bankTransTotal['byBankPaymentTotal'];
                $bankClosing = ($toBankPaymentTotal + $bankOpening) - $byBankTotal;  
                $this->_report['opb'] = number_format($bankOpening, 2);
            }
        }
        /*if($ledgerTransaction){ 
            $x =0;
            foreach ($ledgerTransaction as $items) 
            {              
              foreach($items as $item){
                    $amount = 0;
                    if(isset($item['bycash']) > 0){
                        $this->_report['report'][$x]['bycash'] =  $item['bycash'];
                        $amount = $item['bycash'];
                        $cashTotal = $item['bycash'];
                    }
                    if(isset($item['bybank']) > 0){
                        $this->_report['report'][$x]['bybank'] =  $item['bybank'];
                        $amount = $item['cash'];
                    }
                    $this->_report['report'][$x]['transNo'] = $item['transNo'];
                    $this->_report['report'][$x]['transDate'] = $item['transDate'];
                    $this->_report['report'][$x]['name'] = $item['name'];               
                    $this->_report['report'][$x]['particulars'] = $item['particulars'];
                    if(isset($item['cash']) > 0){
                       $this->_report['report'][$x]['cash'] =  $item['cash'];
                       $amount = $item['cash'];
                       $cashTotal = $item['cash'];
                    }   
                     if(isset($item['bank']) > 0){
                        $this->_report['report'][$x]['bank'] =  $item['bank'];
                        $amount = $item['cash'];
                    }
                    $reportTotal += $amount;
                    $cashTotal += $cashTotal;
                    $x++;
                 }
            }
        }*/
        
        /*if($paymentTransactions){ 
            print_r($getMemberReceipts);
            foreach ($paymentTransactions as $item) 
            {              
               $this->_report['report'][$x]['bycash'] =  0;
               $this->_report['report'][$x]['bybank'] =  0;
               $this->_report['report'][$x]['transNo'] = $item->transNo;
               $this->_report['report'][$x]['transDate'] = $item->transDate;
               $this->_report['report'][$x]['name'] = $item->name;               
               if($item->action_chit_id){
                $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
               } else if ($item->loan_id){
                   $this->_report['report'][$x]['transNo'] = $item->loan_id;
               }
               $this->_report['report'][$x]['particulars'] = $item->particulars;
               if($item->transBy > 0) {
                 if($item->transType === 'Direct Income' || $item->transType === 'Indirect Income') {
                        $this->_report['report'][$x]['bank'] =  $item->amount;
                 } else if($item->transType === 'Direct Expense' || $item->transType === 'Indirect Expense') {
                        $this->_report['report'][$x]['bybank'] =  $item->amount;
                 } 
               } else {
                    if($item->transType === 'Direct Income' || $item->transType === 'Indirect Income') {
                        $this->_report['report'][$x]['cash'] =  $item->amount;
                    } else if($item->transType === 'Direct Expense' || $item->transType === 'Indirect Expense') {
                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                    } else {
                         $this->_report['report'][$x]['cash'] =  $item->amount;
                    }
               }              
               $reportTotal += $item->amount;
               $cashTotal += $item->amount;
               $x++;
            }
        }*/
       
        
        $month = date("F", strtotime($this->_endDate));
        $year = date('Y', strtotime($this->_endDate));
       
      
          $this->_report['Month'] = strtoupper($month);
          $this->_report['Year'] = $year;
         // $this->_report['cashClosing'] = $cashClosing;
          $this->_report['transactions'] = $this->_report;
          $this->_report['cashPaymentTotal'] =  number_format($cashPaymentTotal, 2);
          $this->_report['reportTotal'] = number_format($reportTotal, 2);
          $this->_report['cashTotal'] = number_format($cashTotal, 2);
          $this->_report['cashClosing'] = number_format($cashClosing, 2);
          $this->_report['byBankTotal'] = number_format($byBankTotal, 2);
          $this->_report['bankTotal'] = number_format($bankTotal, 2);
          $this->_report['bycashTotal'] = number_format($bycashTotal, 2);          
          $this->_report['bankClosing'] = number_format($bankClosing, 2);
          $this->_report['toBankPaymentTotal'] = number_format($toBankPaymentTotal, 2);
          $this->_report['ledgerName'] = $heads[0]->head_name;
//          $this->_report['auctionChitAmount'] = $auctionChitopening;
          $this->_report['donorCashTotal'] = number_format($donorCashTotal, 2);
          $this->_report['catchTotal'] = number_format($catchTotal, 2);
          $this->_report['memberAdvancePaid'] = number_format($memberAdvancePaid, 2);
          //$this->_report['reciptBankTotal'] = number_format($receiptBankTotal, 2);
          
    }
    public function auctionChitIds($donor_id, $fmc_id)
    {
        
        $sql = $this->_model->getSql();
        $sql->where("`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `fmc_id` = {$fmc_id} and `status` = '1' and action_chit_id > 0")
       // $sql->where($this->_where)
            ->whereId('donor_id', $donor_id);
        $where = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where)) {
            return false;
        }
        
        return $this->_model->selectColumns('action_chit_id', $where);
    }
    public function auctionChitIdsOpening($donor_id, $fmc_id)
    {
        $sql = $this->_model->getSql();
        $sql->where("`transDate` < '{$this->_startDate}'  and `fmc_id` = {$fmc_id} and `status` = '1' and action_chit_id > 0")
            ->whereId('donor_id', $donor_id);
        $where = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where)) {
            return false;
        }
        
        return $this->_model->selectColumns('action_chit_id', $where);
    }
    public function periodSumOpening($auctionChitID)
    {
        $sql = $this->_transactionModel->getSql();
        $sql->whereIn('action_chit_id', $auctionChitID);
        $where = $sql->getWhere();
        if (0 == $this->_transactionModel->selectTotal($where)) {
            return 0;
        }
        
        return $this->_transactionModel->selectSum('amount', $where);
    }
    public function periodSumOpeningByHeadID($auctionChitID, $headID)
    {
        $sql = $this->_transactionModel->getSql();
        $sql->whereIn('action_chit_id', $auctionChitID)
             ->whereId('headID', $headID);
        $where = $sql->getWhere();
        if (0 == $this->_transactionModel->selectTotal($where)) {
            return 0;
        }
        
        return $this->_transactionModel->selectSum('amount', $where);
    }
     public function periodSum($auctionChitID)
    {
        $sql = $this->_transactionModel->getSql();
        $sql->whereIn('action_chit_id', $auctionChitID);
        $where = $sql->getWhere();
        if (0 == $this->_transactionModel->selectTotal($where)) {
            return 0;
        }
        
        return $this->_transactionModel->selectSum('amount', $where);
    }
    public function ledgerPaymentTransactions($fmc_id)
    {
        
    }
    
    public function deductionOpeningAmount($headID, $fmc_id)
    {
            $amount = 0;
            $this->_deductionTypeModel = new DeductionType();
            if ($headID > 0) {
                 $head = $this->_headModel->checkByColumnByFmc('id', $headID, $fmc_id);
                 if(isset($head->code)){
                    $deductionType = $this->_deductionTypeModel->selectByColumn('code', $head->code);
                    $generator = new DeductionReport($this->_branch);
                    if(isset($deductionType->id)){
                        $amount = $generator->getDeductionOpening($deductionType->id);
                     //   $amount = $reportTotal[$deductionType->id]['opening'];
                    }
                 }
            }
            return $amount;
    }
    
    public function collectionAmountAction($headID, $fmc_id)
    {
            $amount = 0;
            $this->_deductionTypeModel = new DeductionType();
            if ($headID > 0) {
                 $head = $this->_headModel->checkByColumnByFmc('id', $headID, $fmc_id);
                 if(isset($head->code)){
                    $deductionType = $this->_deductionTypeModel->selectByColumn('code', $head->code);
                    $generator = new DeductionReport($this->_branch);
                    if(isset($deductionType->id)){
                        $reportTotal = $generator->getReport($deductionType->id);
                        $amount = $reportTotal[$deductionType->id]['opening'];
                    }
                 }
            }
            return $amount;
    }
    
    private function getPaymentTransactions($headId)
        {
            $fmc_id = $this->_branch->id();
            $sql = "SELECT `h`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' "
                    . "and `t`.`headID` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            return $data;          
    }
    
    private function getMemberTransactions($matchingHeadId = 0)
    {
         $fmc_id = $this->_branch->id();
         if($matchingHeadId > 0 ){              
           $sql = "SELECT `h`.`transType`,`m`.`receiptNo`, `m`.`action_chit_id`, `m`.`loan_id`, `a`.`name`, `m`.`transDate`,  `t`.`remarks`, `t`.`amount`, `m`.`transBy` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1 and `m`.`transType` = 'R' and `t`.`headID` = {$matchingHeadId} and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            return $data;    
         }
    }
    
    private function getIncomeTransactions($matchingHeadId = 0)
    {
        $fmc_id = $this->_branch->id();
        $sql = "SELECT `h`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`,  `m`.`name`, `t`.`transDate`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`headID` = {$matchingHeadId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            return $data;    
    }
    
    private function getAuctioneerCollectionOpening($donorID, $headId)
    {
        $receiptSum = 0;
        $fmc_id = $this->_branch->id();
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "  where 1=1 and `m`.`transType` = 'R' and receiptNo > 0 and `t`.`headID` = {$headId} and `m`.`donor_id` = {$donorID}  and `m`.`status` = '1' and  `m`.`transDate` < '{$this->_startDate}'  and `m`.`fmc_id` = {$fmc_id}";
        $data = $this->_transactionModel->queryRows($sql);   
        $receiptSum = $data[0]->sum;
        return $receiptSum;
    }
     
    private function getMemberReceipts()
    {
            $fmc_id = $this->_branch->id();
            $receiptBankTotal = 0;
            $receiptTotal = [];
            $reportTotal = 0;
            $x = 0;
            $sql = "SELECT `m`.`receiptNo`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "  where 1=1 and `m`.`transType` = 'R' and `t`.`headID` = {$this->_headId} and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->remarks;

                    if($item->transBy > 0) {
                      $this->_report['report'][$x]['bank'] =  $item->amount;
                      $receiptBankTotal += $item->amount;
                    } else {
                      $this->_report['report'][$x]['cash'] =  $item->amount;
                    }
                    $this->_report['report'][$x]['bycash'] =  0;
                    $this->_report['report'][$x]['bybank'] =  0;
                    $reportTotal += $item->amount;
                    $x++;
                }
            }
            $sql = "SELECT `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`headID` = {$this->_headId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;

                    if($item->transBy > 0) {
                      $this->_report['report'][$x]['bank'] =  $item->amount;
                      $receiptBankTotal += $item->amount;
                    } else {
                         $this->_report['report'][$x]['cash'] =  $item->amount;
                    }
                    $this->_report['report'][$x]['bycash'] =  0;
                    $this->_report['report'][$x]['bybank'] =  0;
                    $reportTotal += $item->amount;
                    $x++;
                }
            }
            $receiptTotal['bankTotal'] = $receiptBankTotal;
            $receiptTotal['cashTotal'] = $reportTotal;
            
            return $receiptTotal;
    }
        
    private function getCashOpening($date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_branch->id();
            $date = $this->_startDate;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`head_name` = 'Cash' and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            $opb = $data[0]->opb;
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`status` = '1' and `m`.`transDate` < '$date' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $cashPayment =  $data[0]->sum;
        //Cash payment ends here
            return round(($cashTotal - $cashPayment),2);
    }
    
    private function getAuctioneerOpening($headID)
    {
            $cashTotal = 0;
            $fmc_id = $this->_branch->id();
            $date = $this->_startDate;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`id` = {$headID} and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            $opb = $data[0]->opb;
            return $opb;
    }
    
    private function getHeadOpening($headID, $date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_branch->id();
            $date = $this->_startDate;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`id` = {$headID} and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            $opb = $data[0]->opb;
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `t`.`headID` = $headID and `m`.`status` = '1' and `m`.`transDate` < '$date' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $cashPayment =  $data[0]->sum;
        //Cash payment ends here
            return round(($cashTotal - $cashPayment),2);
    }

    private function getCashTransactions($opb)
    {
            $cashTotal = [];
            $x = 0;
            $fmc_id = $this->_branch->id();
            $reportCashPaymentTotal = 0;
        //Cash Receipts start here
            $sql = "SELECT `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                   . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $reportTotal = $opb;
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;
                    if($item->transType == 'P') {
                        $this->_report['report'][$x]['bycash'] =  $item->amount;
                        $reportCashPaymentTotal += $item->amount;
                    }else {
                        $this->_report['report'][$x]['cash'] =  $item->amount;
                        $reportTotal += $item->amount;
                    }                 
                    $x++;
                }
            }
            $sql = "SELECT `m`.`receiptNo`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`transType` = 'R' and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->remarks;
                    $this->_report['report'][$x]['cash'] =  $item->amount;
                    $reportTotal += $item->amount;
                    $x++;
                }
            }
            $cashTotal['cashPaymentTotal'] = $reportCashPaymentTotal;
            $cashTotal['cashTotal'] = $reportTotal;
            return $cashTotal;
    }

    private function getBankOpening($headID , $date = null)
    {
            $opb = 0;
            $fmc_id = $this->_branch->id();
            $date = $this->_startDate;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`id` = '{$headID}' and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            if(isset($data[0]->opb)){
               $opb = $data[0]->opb;
            }
        //Cash Deposit to Bank
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $deposit =  $data[0]->sum;
        //Bank Receipts start here (Interest, tds refund etc
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $receiptSum = $data[0]->sum;
            //Member Receipts
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` < '$date' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            $memberReceipt =  $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb + $deposit;
        //Bank Receipts ends here
        //Payments through Bank start here
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $bankPayment =  $data[0]->sum;
        //Bank payment ends here
        //Bank Withdrawl start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $withdrawl = $data[0]->sum;
        //Bank payment ends here 
            $payments = $bankPayment + $withdrawl;

            return round(($cashTotal - $payments),2);
    }
    
    private function getBankTransactions($headID)
    {
            
            $fmc_id = $this->_branch->id();       
            $byBankPaymentTotal = 0;
            $toBankPaymentTotal = 0;
            $x = 0;
            //Cash Deposit
            $sql = "SELECT `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy`  from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' AND `t`.transBy = 0 " 
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                    if($item->action_chit_id){
                     $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                    } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;
                    $this->_report['report'][$x]['tobank'] =  $item->amount;
                    $toBankPaymentTotal += $item->amount;
                    $x++;
                }
            }
        //Bank Receipts start here (Interest, tds refund etc
            $sql = "SELECT `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;
                    $this->_report['report'][$x]['tobank'] =  $item->amount;
                    $toBankPaymentTotal += $item->amount;
                    $x++;
                }
            }
            //Member Receipts
            $sql = "SELECT `m`.`receiptNo`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id`  FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                     . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->remarks;
                    $this->_report['report'][$x]['tobank'] =  $item->amount;
                    $toBankPaymentTotal += $item->amount;
                    $x++;
                }
            }
        //Bank Receipts ends here
        //Payments through Bank start here
            $sql = "SELECT  `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                    if($item->action_chit_id){
                     $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                    } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;
                    $this->_report['report'][$x]['bybank'] =  $item->amount;
                    $byBankPaymentTotal += $item->amount;
                    $x++;
                }
            }
        //Bank payment ends here
        //Bank Withdrawl start here
            $sql = "SELECT  `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                     . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['name'] = $item->name;
                   if($item->action_chit_id){
                    $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                   } 
                    $this->_report['report'][$x]['particulars'] = $item->particulars;
                    $this->_report['report'][$x]['bybank'] =  $item->amount;
                    $byBankPaymentTotal += $item->amount;
                    $x++;
                }
            }
            $bankTotal['byBankPaymentTotal'] = $byBankPaymentTotal;
            $bankTotal['toBankPaymentTotal'] = $toBankPaymentTotal;
            return $bankTotal;
    }
   private function getCumulativeByHeadID($headID)
    {
        $fmc_id = $this->_branch->id();
        $cumHeadAmount = 0;
        $fydata = $this->getFinancialYearInfo();
        $end_date = $this->getPreviousDay($this->_startDate);
        //$fydata['year'] = '2023-04-01';
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m`  ON `m`.`id` = `t`.`transID` "
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 and `t`.`headID` ={$headID} "
                . "and `m`.`transDate` between '{$fydata['year']}' and '{$end_date}'  and `m`.`status` = '1' and `m`.`fmc_id` = {$fmc_id}";
        $data = $this->_membTrans->queryRows($sql);
        if(!$data[0]->sum){
            $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`headID` = {$headID} and `t`.`transType` ='R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` >0 and `t`.`transDate` between '{$fydata['year']}'  and '{$end_date}' and `t`.`fmc_id` = {$fmc_id} "
                    . "group by `t`.`headID`";
           $data = $this->_transactionModel->queryRows($sql);
        }
        if(isset($data[0]->sum)){
            return $data[0]->sum;
        }
        else {
            return $cumHeadAmount;
        }
    }
    
    private function cumulativePaymentyHeadID($headID)
    {
        $fmc_id = $this->_branch->id();
        $fydata = $this->getFinancialYearInfo();
        $end_date = $this->getPreviousDay($this->_startDate);
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`headID`={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$fydata['year']}' and '{$end_date}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
    }
    /**
     * preload
     */
    private function _preload()
    {
        $this->_report['filters'] = [
            'start' => $this->_startDate,
            'end' => $this->_endDate,
            'group' => $this->_groupId,
            'period' => [
                DateHelper::datepicker($this->_startDate),
                DateHelper::datepicker($this->_endDate),
            ],
        ];
        
        $start = DateHelper::getDateTime($this->_startDate);
        $end = DateHelper::getDateTime($this->_endDate);
        
        $this->_report['params'] = 'group=' . $this->_groupId
                                 . '&amp;start=' . $start->format('Ymd')
                                 . '&amp;end=' . $end->format('Ymd');
        
        $deductions = $this->_branch->deductionTypes();
        $brachDeductionIds = array_keys($deductions);
       // $groupedDeductions = ReportHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
       // $this->_report['deductions'] = $groupedDeductions[$this->_groupId];
        
        $this->_report['titles'] = [
            'opening' => 'Opening',
            'collection' => 'Collection',
            'withdrawal' => 'Withdrawal',
            'closing' => 'Balance',
        ];
    }
    
    private function getFinancialYearInfo()
    {
        $today = date("Y-m-d");
        $date=date_create($this->_startDate);
        //echo date_format($date,"m");
      //  echo "<br> Month: ".date_format($date,"m");
        if (date_format($date,"m") >= 4) {//On or After April (FY is current year - next year)
            $financial_year = (date_format($date,"Y")) . '-' . (date_format($date,"y")+1);
            $currentYear = (date_format($date,"Y"));
        } else {//On or Before March (FY is previous year - current year)
            $financial_year = (date_format($date,"Y")-1) . '-' . date_format($date,"y");
            $currentYear = (date_format($date,"Y")-1);
        }
        $currentFinancialYearWithDate = $currentYear."-04-01";
        //echo "<br> FY ".$financial_year;
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);
    }
    
    
    private function getPreviousDay($date)
    {
       $date = isset($date) ? $date : date('Y-m-d');
       $prev_date = date('Y-m-d', strtotime($date .' -1 day'));
       return $prev_date;
    }
    
    /*
     * print report
     */
    private function _print()
    {
        echo '<pre>';
        print_r($this->_report);
        exit;
    }
    
}
