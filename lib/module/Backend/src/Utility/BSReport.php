<?php

namespace Backend\Utility;
use Model\{RPTransMember, RPTransMemberDetails, RPTransactionDetails, RPHeads, RPBSHeads, Member};
use Model\DeductionMemberDaily;
use Shared\Utility\ReportHelper;
use Web\Utility\DateHelper;


class BSReport
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
    private $_fmc_id;
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
        $this->_bsHeadModel = new RPBSHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_member = new Member();
        $this->_fmc_id = $this->_branch->id();
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
        $fmc_id = $this->_fmc_id;
        $catchOpening = 0;
        $catchOpeningTotal = 0;
        $totalMemberPayments = 0;
        $memberAdvanceSum =0;
        $donorOutStandingOpening =0;
        $donorCollectionPayment = 0;
        $donorCollectionPaymentTotal= 0;
        $totalAssetsBalance = 0;
        $totalFMCLoanAmount = 0;
        $fmcLoanOpening = 0;
        $donorAssets =0;
        $fmcLoanReceipt = 0;
        $fmcLoanReceiptTotal = 0;
        $fmcAdvReceipt = 0;
        $fmcAdvOpening = 0;
        $fmcAdvTotals = 0;
        $fmcOpening =0;
        $fmcAssetsTotals =0;
        $fmcReceiptTotal =0;
        $liquidated = 0;
        $currentYearLiability = 0;
        $refundedLiability = 0;
        $currentYearTotalLiability = 0;
        $cashOpening = 0;
        $bankOpening = [];
        $subTotalBankOpening = 0;
        $cashClosing = 0;
        $subTotalBankClosing =0;
        $assetsOpeningTotal = 0;
        $assetsBalanceTotal = 0;
        $liabilityOpeningTotal = 0;
        $liabilityBalanceTotal = 0;
        ///
        $bankOpening = [];
        $bankClosing = [];
        $assetsItems = [];
        $liablilityItems = [];
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
        $toBankPaymentTotal = 0;
        $getMemberReceipts = [];
        $paymentTotal = 0;
        $cashReciptTotal = 0;
        $fmcAdvReceiptTotal = 0;
        $x = 0;
        $matchingHeadId = 0;
        $catchTotal =0;
        $memberAdvancePaid = 0;
        $month = date("F", strtotime($this->_endDate));
        $year = date('Y', strtotime($this->_endDate));
             
        $fyDetails = $this->getFinancialYearInfo();
        
        
        
        $headsQuery = "select * from " . $this->_bsHeadModel->getTableQuoted()." where TransType in ('Assets','Liability') and status='N' and fmc_id = {$fmc_id} and fy='{$fyDetails['FY']}' order by id";
        $BSHeads = $this->_bsHeadModel->queryRows($headsQuery);
        $bankQuery = "select * from  " . $this->_headModel->getTableQuoted()." where TransType ='bank' and fmc_id = {$fmc_id} order by head_name";
        $bankHeads = $this->_headModel->queryRows($bankQuery);     
        $liabilityCount = 0;
        $assetsCount = 0;
        $cashOpening = $this->getCashOpening();
        $cashClosing = $this->getCashTransactions($cashOpening);
        foreach ($bankHeads as $rows) {
            $bank_opening = $this->getBankOpening($rows->id);
            if(isset($bank_opening)) {
                $bankOpening[$rows->id]['head_name'] = $rows->head_name;
                $bankOpening[$rows->id]['amount'] =  $bank_opening;
                $subTotalBankOpening += $bank_opening;
            }
        }
        //Bank transactions for closing
        foreach ($bankHeads as $rows) {
            $bank_closing = $this->getBankTransactions($rows->id, $bankOpening[$rows->id]['amount']);
            if(isset($bank_closing)) {
                $bankClosing[$rows->id]['head_name'] = $rows->head_name;
                $bankClosing[$rows->id]['amount'] =  $bank_closing;
                $subTotalBankClosing += $bank_closing;
            }
        }
        foreach($BSHeads as $bs_item){
            $headSum = 0;
            $y = 0;
            $monthTotal = 0;
            $cumulativeTotal = 0;
             if($bs_item->code === 'FWS_Account'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='FWS_loan_repayment' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='FWS_loan_repayment' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Loan taken this year';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Repaid Amount';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }
             if($bs_item->code === 'Siffs_Grant_Old'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='UBL_loan_repaid' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='UBI_loan' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Fresh Loans';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Repaid';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             } 
             
             if($bs_item->code === 'SIFFS_Grant_New'){
                                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                /*$headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Advance_Receieved' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Advance_Receieved' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }*/

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Fresh Loans';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Repaid';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }
             if($bs_item->code === 'gratuity_paid'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
             echo   $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Gratuity _PP' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Inc_Sundry_Payables' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'New Contributions';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Refunds';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }   
             if($bs_item->code === 'Sundry_Payables'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Sundry_Payables' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Inc_Sundry_Payables' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'New Contributions';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Refunds';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }   
              if($bs_item->code === 'Provision_for_Bad_debts'){
                                  $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Write_of_Allocation' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Provision_Bad_debt' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Current year allocation';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Current year written off allocation';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }   
             if($bs_item->code === 'Loans_Advances_Taken_OS'){
                                 $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code='fresh_loans' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='fresh_advance' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Fresh Loans/Advances taken';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Repaid';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }   
             if($bs_item->code === 'Costal_Bank_Bulk_Loan_Liable'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in('MFL_loan_repaid', 'MFL_loan_pre_closure') and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Fresh Loans';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Repaid';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             }   
             
             
              if($bs_item->code === 'Educational_Scholarship_Fund'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Educational_Scholarship_Paid' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Educational_Scholarship_Fund' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Current Year';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Less Paid';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             } 
             
             if($bs_item->code === 'Gratuity_Fundbs'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='payment_to_lic' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='lic_Fresh_advance' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Current Year Allocation';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Repaid Amount';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             } 
             if($bs_item->code === 'DMF_Grant'){
                $refundedLiability = 0;
                $currentYearLiability = 0;
                $currentYearTotalLiability = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='IOB_loan_repaid' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $refundedLiability += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='IOB_loan' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $currentYearLiability += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $currentYearTotalLiability = $currentYearLiability + $fmcOpening;
             
                $liablilityItems[$liabilityCount][$y]['head_name'] =  $bs_item->head_name;
                $liablilityItems[$liabilityCount][$y]['opening'] = $fmcOpening;
                $liablilityItems[$liabilityCount][$y]['assets_total'] = ''; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Loan taken this year';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $currentYearLiability; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = ''; 
                $y++;
                $liablilityItems[$liabilityCount][$y]['head_name'] = 'Repaid Amount';
                $liablilityItems[$liabilityCount][$y]['opening'] = '';
                $liablilityItems[$liabilityCount][$y]['assets_total'] = $refundedLiability ; 
                $liablilityItems[$liabilityCount][$y]['assets_balance'] = $currentYearTotalLiability - $refundedLiability; 
                $liabilityCount ++;
             } 
            
            //FMC Loan Repayable
           //get FMC loan payment transactions
            if($bs_item->code === 'Loans_OS'){
                $loanHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='IOB_loan_repaid'  and fmc_id = {$fmc_id} order by head_name";
                $fmcLoanHeads = $this->_bsHeadModel->queryRows($loanHeadsQuery);
                if(isset($fmcLoanHeads[0]->id)){
                    $headSum = $this->getPaymentyHeadID($fmcLoanHeads[0]->id);
                    $totalFMCLoanAmount += $headSum;
                }
                   //  $fmcLoanReceipt = $this->getSumByHeadID($item->id);  
                   //  $fmcLoanReceiptTotal += $fmcLoanReceipt;
                //get FMC loan receipt transactions
                $loanRHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in ('fmc_loan', 'production_loan', 'thattumady_loan', 'labour_loan', 'relief_loan', 'DCB_re_finance') and fmc_id = {$fmc_id} order by head_name";
                $fmcLoanRHeads = $this->_headModel->queryRows($loanRHeadsQuery);
                foreach($fmcLoanRHeads as $item){
                     $fmcLoanReceipt = $this->getSumByHeadID($item->id);  
                     $fmcLoanReceiptTotal += $fmcLoanReceipt;
                }

                $y = 0;
                $fmcLoanOpening = $bs_item->opb;
                $fmcLoanTotals = $totalFMCLoanAmount + $fmcLoanOpening;               

                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcLoanOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fresh Loans';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $totalFMCLoanAmount; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Repaid';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcLoanReceiptTotal ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcLoanTotals - $fmcLoanReceiptTotal; 
                $assetsCount++;
            }
             if($bs_item->code === 'Land_Buildings'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Land_and_Building' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Inc_Land_and_Building' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fresh Assets Created';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Assets Disposed';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
         
   
          if($bs_item->code === 'Furniture_Fittings'){
               //Get donors opening amount. 
                    //Check donor ledger
                     $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='furn_other_assets_liq' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
               //get FMC advance receipt transactions
                $loanFHeadsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='furn_other_asset' and fmc_id = {$fmc_id} order by head_name";
                $fmcLoanFHeads = $this->_bsHeadModel->queryRows($loanFHeadsQuery);
                foreach($fmcLoanFHeads as $item){
                     $fmcAdvReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcAdvReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fresh Procurements Loans';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Sold/Depreciation';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
              if($bs_item->code === 'Staff_PF'){
                 $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='PF_Subscription' and transType='Direct Expense'  and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code in ('inc_Employee_Contribution', 'inc_Employer_Contribution') and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'PF Collection this year';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Paid PF Account';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            if($bs_item->code === 'Loans_Advances_OS'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='inc_FWS_Advance' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getSumByHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='fmc_advance' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fresh Loans/Advances Given';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Repaid';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            
            
            if($bs_item->code === 'Chitty_Collection'){
                 $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Chitty_Collection_Payments' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Chitty_Collection_Payments' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Opening Balance';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Received';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
             if($bs_item->code === 'Loans_OS_MF'){
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Loan_OS_MF' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getSumByHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='Loan_OS_MF' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Add Current Year';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Current Year';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            if($bs_item->code === 'Working_Capital_Loan'){
                 $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='CKMU_Working_Loan' and transType='Direct Income' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getSumByHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get fixed assets transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='CKMU_Working_Loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fesh Loans';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Received';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            
            if($bs_item->code === 'Costal_Bulk_Bank_Loan'){
                $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='inc_MFL_loan_repaid' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getSumByHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get fixed assets transactions
               /* $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='fixed_assets' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }*/

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Fesh Loans';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Received';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            
          
             if($bs_item->code === 'Educational_Scholarship'){
                  $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='fresh_scholarship_refund' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getSumByHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='fresh_scholarship_deposit' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getSumByHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                 $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = $fmcOpening;
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Current Year ';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $fmcReceiptTotal; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Less Received';
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = $liquidated ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $fmcAssetsTotals - $liquidated; 
                $assetsCount++;
            }
            
            /*if($bs_item->code === 'Gratuity_Fund'){
                  $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='liquidated_assets' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Office';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $cashOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $cashClosing);
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Bank';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $subTotalBankOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $subTotalBankClosing) ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $cashClosing + $subTotalBankClosing; 
                $assetsCount++;
            }
             * 
             */
            
           /* if($bs_item->code === 'DMF_Grant'){
                  $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='liquidated_assets' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Office';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $cashOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $cashClosing);
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Bank';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $subTotalBankOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $subTotalBankClosing) ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $cashClosing + $subTotalBankClosing; 
                $assetsCount++;
            }*/
            
            if($bs_item->code === 'Current_Assets'){
                  $fmcReceiptTotal = 0 ;
                $liquidated = 0;
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='liquidated_assets' and fmc_id = {$fmc_id} order by head_name";
                $fmcPaymentHeads = $this->_bsHeadModel->queryRows($headsQuery);
                foreach($fmcPaymentHeads as $item){
                     $headSum = $this->getPaymentyHeadID($item->id);
                     $liquidated += $headSum; 
                }
                //get loan transactions
                $headsQuery = "select * from " . $this->_headModel->getTableQuoted()." where code ='MFL_loan' and transType='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
                $receiptHeads = $this->_headModel->queryRows($headsQuery);
                foreach($receiptHeads as $item){
                     $fmcReceipt = $this->getPaymentyHeadID($item->id);  
                     $fmcReceiptTotal += $fmcReceipt;
                }

                $y = 0;
                $fmcOpening = $bs_item->opb;
                $fmcAssetsTotals = $fmcReceiptTotal + $fmcOpening;
             
                $assetsItems[$assetsCount][$y]['head_name'] =  $bs_item->head_name;
                $assetsItems[$assetsCount][$y]['opening'] = '';
                $assetsItems[$assetsCount][$y]['assets_total'] = ''; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Office';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $cashOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $cashClosing);
                $assetsItems[$assetsCount][$y]['assets_balance'] = ''; 
                $y++;
                $assetsItems[$assetsCount][$y]['head_name'] = 'Cash at Bank';
                $assetsItems[$assetsCount][$y]['opening'] = sprintf("%.2f", $subTotalBankOpening);
                $assetsItems[$assetsCount][$y]['assets_total'] = sprintf("%.2f", $subTotalBankClosing) ; 
                $assetsItems[$assetsCount][$y]['assets_balance'] = $cashClosing + $subTotalBankClosing; 
                $assetsCount++;
            }
           
            
        }
       
        if($liabilityCount > $assetsCount){
            for ($x = 0; $x < $liabilityCount; $x++) 
            {
                for($y = 0; $y <3; $y++){
                    $this->_report['report'][$x]['liability'][$y]['head_name'] =  $liablilityItems[$x][$y]['head_name'];
                    $this->_report['report'][$x]['liability'][$y]['opening'] = $liablilityItems[$x][$y]['opening'];
                    $this->_report['report'][$x]['liability'][$y]['assets_total'] = $liablilityItems[$x][$y]['assets_total'];
                    $this->_report['report'][$x]['liability'][$y]['assets_balance'] = $liablilityItems[$x][$y]['assets_balance'];
                    $liabilityOpeningTotal += (float) $liablilityItems[$x][$y]['opening'];
                    $liabilityBalanceTotal += (float) $liablilityItems[$x][$y]['assets_balance'];
                    
                }
                if(isset($assetsItems[$x])) {
                    for($y = 0; $y <3; $y++){
                        $this->_report['report'][$x]['assets'][$y]['head_name'] = $assetsItems[$x][$y]['head_name'];
                        $this->_report['report'][$x]['assets'][$y]['opening'] = $assetsItems[$x][$y]['opening'];
                        $this->_report['report'][$x]['assets'][$y]['assets_total'] = $assetsItems[$x][$y]['assets_total'];
                        $this->_report['report'][$x]['assets'][$y]['assets_balance'] = $assetsItems[$x][$y]['assets_balance']; 
                        $assetsOpeningTotal += (float) $assetsItems[$x][$y]['opening'];
                        $assetsBalanceTotal += (float) $assetsItems[$x][$y]['assets_balance'];
                   }
                }
            }
        }
        else {
            for ($x = 0; $x < $assetsCount; $x++) 
            {
               for($y = 0; $y <3; $y++){
                        $this->_report['report'][$x]['assets'][$y]['head_name'] = $assetsItems[$x][$y]['head_name'];
                        $this->_report['report'][$x]['assets'][$y]['opening'] = $assetsItems[$x][$y]['opening'];
                        $this->_report['report'][$x]['assets'][$y]['assets_total'] = $assetsItems[$x][$y]['assets_total'];
                        $this->_report['report'][$x]['assets'][$y]['assets_balance'] = $assetsItems[$x][$y]['assets_balance']; 
                        $assetsOpeningTotal += (float) $assetsItems[$x][$y]['opening'];
                        $assetsBalanceTotal += (float) $assetsItems[$x][$y]['assets_balance'];
                }          
               if(isset($liablilityItems[$x])) {
                     for($y = 0; $y <3; $y++){
                        $this->_report['report'][$x]['liability'][$y]['head_name'] =  $liablilityItems[$x][$y]['head_name'];
                        $this->_report['report'][$x]['liability'][$y]['opening'] = $liablilityItems[$x][$y]['opening'];
                        $this->_report['report'][$x]['liability'][$y]['assets_total'] = $liablilityItems[$x][$y]['assets_total'];
                        $this->_report['report'][$x]['liability'][$y]['assets_balance'] = $liablilityItems[$x][$y]['assets_balance'];
                        $liabilityOpeningTotal += (float) $liablilityItems[$x][$y]['opening'];
                        $liabilityBalanceTotal += (float) $liablilityItems[$x][$y]['assets_balance'];
                    }
               }
        }
        }
        //New
         $operatingProfitCol1 = 0;
         $operatingProfitCol2 = 0;
         $operatingProfitCol3 = 0;
         $totalLiabilityCol1 = 0;
         $totalLiabilityCol2 = 0;
         $totalLiabilityCol3 = 0;
         $totalAssetsCol1 = 0;
         $totalAssetsCol2 = 0;
         $totalAssetsCol3 = 0;
         $operatingLossCol1 = 0;
         $operatingLossCol2 = 0;
         $operatingLossCol3 = 0;
         $totalLiabilityLastCol1 = 0;
         $totalLiabilityLastCol2 = 0;
         $totalLiabilityLastCol3 = 0;
         $totalAssetsLastCol1 = 0;
         $totalAssetsLastCol2 = 0;
         $totalAssetsLastCol3 = 0;
         //$operating_loss = 0;
         $assetsOperatingLoss = 0;
        //
         $receiptTotal =0; 
         $paymentsCurrenTotal =0;
         $paymentsCumilativeTotal= 0;
         $paymentTotal = 0;
         $paymentGrandSubTotal =0;
         $paymentGrandTotal =0;
         $paymentSubTotal =0;
         $paymentCumulative=0;
         $overCumulativeIncome =0;
         $overCumulativeExpense =0;
         $overTotalIncome =0;
         $receiptGrandSubTotal =0;
         $receiptGrandTotal =0;
         $overTotalExpense =0;
        $receiptsCurrentTotal =0;
        $receiptSubTotal =0;
        $receiptCumulative =0;
        $receiptsCumilativeTotal =0;
       
        $assets['totalAssetsCol2'] = 0;        
        $totalAssetsCol1 = $assetsOpeningTotal ; 
        $assets['totalAssetsCol3'] = sprintf('%0.2f', $assetsBalanceTotal);  
        $totalAssetsLastCol3 = $assets['totalAssetsCol3'] + $assets['totalAssetsCol2'];
        $assets['totalAssetsLastCol3'] = sprintf('%0.2f', $totalAssetsLastCol3);
        $totalLiabilityLastCol3 = $totalAssetsLastCol3;
        $totalLiabilityCol1 = $liabilityOpeningTotal;
        $operatingProfitCol3 = ($totalLiabilityLastCol3 - $liabilityBalanceTotal);
        $liability['operatingProfitCol3'] = sprintf('%0.2f', $operatingProfitCol3);
        $liability['totalLiabilityCol1'] = sprintf('%0.2f', $totalLiabilityCol1);
        $operatingProfitCol1 = ($assetsOpeningTotal - $liabilityOpeningTotal);
        $liability['totalLiabilityCol3'] = sprintf('%0.2f', $liabilityBalanceTotal);
        $liability['operatingProfitCol1'] = sprintf('%0.2f', $operatingProfitCol1);
        $totalLiabilityLastCol1 = $liabilityOpeningTotal + $operatingProfitCol1;
        $liability['totalLiabilityLastCol1'] = sprintf('%0.2f', $totalLiabilityLastCol1);
       $liability['totalLiabilityLastCol3'] = sprintf('%0.2f', $totalLiabilityLastCol3);
        
        $assets['totalAssetsCol1'] = sprintf('%0.2f', $totalAssetsCol1);
        $assets['operatingLossCol1'] = 0;
        if(($liabilityOpeningTotal - $assetsOpeningTotal) > 0)
        {
            $assets['operatingLossCol1'] = sprintf('%0.2f', $operatingLossCol1);
        }
        
        
        //$liability['liabilityOpeningTotal'] = sprintf('%0.2f', $liabilityOpeningTotal);
        $receipts['receiptsCumilativeTotal'] = sprintf('%0.2f', $receiptsCumilativeTotal);
        $receipts['receiptTotal'] = sprintf('%0.2f', $receiptTotal);
        $payments['paymentsCurrenTotal'] = sprintf('%0.2f', $paymentsCurrenTotal);
        $payments['paymentsCumilativeTotal'] = sprintf('%0.2f', $paymentsCumilativeTotal);
        $payments['paymentTotal'] = sprintf('%0.2f', $paymentTotal);
        $payments['paymentGrandSubTotal'] = sprintf('%0.2f', $paymentGrandSubTotal);
        
        //$payments['paymentCumulativeSubTotal'] = sprintf('%0.2f', $paymentCumulativeSubTotal);
        $assets['assetsOpeningTotal'] = sprintf('%0.2f', $assetsOpeningTotal);
        $assets['assetsBalanceTotal'] = sprintf('%0.2f', $assetsBalanceTotal);
        if($assetsOperatingLoss > 0){
            $assets['assetsOperatingLoss'] = sprintf('%0.2f', $assetsOperatingLoss);
        } else {
            $assetsOperatingLoss = 0;
            $assets['assetsOperatingLoss'] = sprintf('%0.2f', $assetsOperatingLoss); 
        }
        $assetsOpeningTotal = $assetsOpeningTotal + $assetsOperatingLoss;
        $assets['assetsOpeningTotal'] = sprintf('%0.2f', $assetsOpeningTotal);
        $payments['cumulativeSubTotal'] = sprintf('%0.2f', $paymentCumulative);
        $payments['overCumulativeIncome'] = sprintf('%0.2f', $overCumulativeIncome);
        $receipts['overCumulativeExpense'] = sprintf('%0.2f', $overCumulativeExpense);
        
        $payments['overTotalIncome'] = sprintf('%0.2f', $overTotalIncome);
        $receipts['receiptGrandSubTotal'] = sprintf('%0.2f', $receiptGrandSubTotal);
        $receipts['receiptGrandTotal'] = sprintf('%0.2f', $receiptGrandTotal);
        $receipts['overTotalExpense'] = sprintf('%0.2f', $overTotalExpense);
       
        $this->_report['liability'] = $liability;
        $this->_report['assets'] = $assets;
        $this->_report['Month'] = strtoupper($month);
        $this->_report['Year'] = $year;
    }
    
    private function getCashOpening($date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id;            
            $fy = $this->getFinancialYearInfo();
            $date = $fy['year'];
            $opb =0;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`head_name` = 'Cash' and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);  
            if(isset($data[0]->opb)){
                $opb = $data[0]->opb;
            }
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

    private function getCashTransactions($opb)
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id;
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = 0 and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
            $cashPayment =  $data[0]->sum;
        //Cash payment ends here
            return round(($cashTotal - $cashPayment),2);
    }

    private function getBankOpening($headID , $date = null)
    {
            $opb = 0;
            $fmc_id = $this->_fmc_id;
            $fy = $this->getFinancialYearInfo();
            $date = $fy['year'];
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
    
    private function getBankTransactions($headID, $opb)
    {
            $fmc_id = $this->_fmc_id;
       
        //Cash Deposit to Bank
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $deposit =  $data[0]->sum;
        //Bank Receipts start here (Interest, tds refund etc
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $receiptSum = $data[0]->sum;
            //Member Receipts
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            $memberReceipt =  $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb + $deposit;
        //Bank Receipts ends here
        //Payments through Bank start here
            $sql = "SELECT sum(amount) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $bankPayment =  $data[0]->sum;
        //Bank payment ends here
        //Bank Withdrawl start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            $withdrawl = $data[0]->sum;
        //Bank payment ends here 
            $payments = $bankPayment + $withdrawl;

            return round(($cashTotal - $payments),2);
    }
   private function getSumByHeadID($headID)
   {
        $headSum = 0;
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` "
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                . "where 1=1 and `t`.`headID` ={$headID} and `m`.`fmc_id` = {$fmc_id} and `m`.`status` = '1' "
                . "and `m`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}'";
        $data = $this->_membTrans->queryRows($sql);
        if(isset($data[0]->sum)){
            $headSum += $data[0]->sum;
        }
        //if(!$data[0]->sum) {
             $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                     . " where 1=1 and `t`.headID='{$headID}' and `t`.`transType` ='R' and `t`.`transBy` >0 and `t`.`status` = '1' "
                     . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data_next = $this->_transactionModel->queryRows($sql);
            if(isset($data_next[0]->sum)){
                $headSum += $data_next[0]->sum;
            }
       // }
        
        return $data[0]->sum;
    }
    private function getCumulativeByHeadID($headID)
    {
        $fmc_id = $this->_fmc_id;
        $cumHeadAmount = 0;
        $fydata = $this->getFinancialYearInfo();
        $end_date = $this->getPreviousDay($this->_startDate);
        //$fydata['year'] = '2023-04-01';
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m`  ON `m`.`id` = `t`.`transID` "
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 and `t`.`headID` ={$headID} "
                . "and `m`.`transDate` between '{$fydata['year']}' and '{$end_date}'  and `m`.`status` = '1' and `m`.`fmc_id` = {$fmc_id}";
        $data = $this->_membTrans->queryRows($sql);
        if(isset($data[0]->sum)){
            $cumHeadAmount = $data[0]->sum;
        }
       // if(!$data[0]->sum){
            $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`headID` = {$headID} and `t`.`transType` ='R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` >0 and `t`.`transDate` between '{$fydata['year']}'  and '{$end_date}' and `t`.`fmc_id` = {$fmc_id} "
                    . "group by `t`.`headID`";
           $data = $this->_transactionModel->queryRows($sql);
       // }
         if(isset($data[0]->sum)){
            $cumHeadAmount += $data[0]->sum;
           }
        //}
        /*if(isset($data[0]->sum)){
            return $data[0]->sum;
        }
        else {*/
            return $cumHeadAmount;
        //}
    }

    private function getPaymentyHeadID($headID)
    {
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`headID` ={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
    }

    private function cumulativePaymentyHeadID($headID)
    {
        $fmc_id = $this->_fmc_id;
        $fydata = $this->getFinancialYearInfo();
        $end_date = $this->getPreviousDay($this->_startDate);
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`headID`={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$fydata['year']}' and '{$end_date}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
    }

 
    
    private function getFinancialYearInfo()
    {
    /*
        $today = date("Y-m-d");
        $date=date_create($today);

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
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);*/
        
           //$yrdata= strtotime($this->_startDate);
        //echo date('m', $yrdata);
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
        
        $this->_report['titles'] = [
            'opening' => 'Opening',
            'collection' => 'Collection',
            'withdrawal' => 'Withdrawal',
            'closing' => 'Balance',
        ];
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
    public function auctionChitIdsOpening($donor_id, $fmc_id)
    {
        $sql = $this->_model->getSql();
        $sql->where("`transDate` between '{$this->_startDate}' and '{$this->_endDate}'   and `fmc_id` = {$fmc_id} and `status` = '1' and action_chit_id > 0")
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
     private function getAuctioneerCollectionOpening($donorID, $headId)
    {
        $receiptSum = 0;
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "  where 1=1 and `m`.`transType` = 'R' and receiptNo > 0 and `t`.`headID` = {$headId} and `m`.`donor_id` = {$donorID}  and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}'  and `m`.`fmc_id` = {$fmc_id}";
        $data = $this->_transactionModel->queryRows($sql);   
        $receiptSum = $data[0]->sum;
        return $receiptSum;
    }
    private function getAuctioneerOpening($headID)
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id;
            $date = $this->_startDate;
        //Get Opening amount
            $sql = "SELECT opb from   " . $this->_headModel->getTableQuoted(). " `h` "
                    . "where 1=1 and `h`.`id` = {$headID} and `h`.`fmc_id` = {$fmc_id}";
            $data = $this->_headModel->queryRows($sql);   
            $opb = $data[0]->opb;
            return $opb;
    }
    
    public function auctionChitIds($donor_id, $fmc_id)
    {
        
        $sql = $this->_model->getSql();
        $fydata = $this->getFinancialYearInfo();
        $end_date = $this->getPreviousDay($this->_startDate);
        $sql->where("`transDate` between '{$fydata['year']}'  and '{$end_date}' and `fmc_id` = {$fmc_id} and `status` = '1' and action_chit_id > 0")
       // $sql->where($this->_where)
            ->whereId('donor_id', $donor_id);
        $where = $sql->getWhere();
        
        if (0 == $this->_model->selectTotal($where)) {
            return false;
        }
        
        return $this->_model->selectColumns('action_chit_id', $where);
    }
    private function getMemberTransactions($matchingHeadId = 0)
    {
         $fmc_id = $this->_fmc_id;
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
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT `h`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`,  `m`.`name`, `t`.`transDate`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`headID` = {$matchingHeadId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            return $data;    
    }
    
    //////
    
}
