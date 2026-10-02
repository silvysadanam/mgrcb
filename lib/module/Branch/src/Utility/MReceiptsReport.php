<?php
/**
 * Branch Deduction Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;
use Model\{RPTransMember, RPTransMemberDetails, RPTransactionDetails, RPHeads, Member, InitialDeductionBalance};
use Model\DeductionMemberDaily;
use Shared\Utility\ReportHelper;
use Web\Utility\DateHelper;
use Branch\Form\Deduction\DeductionReportForm;
use Branch\Utility\DeductionReport;
use Model\DeductionType;

class MReceiptsReport
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
    private $_reportType;
    private $_membTrans;
    private $_headModel;
    private $_transactionModel;
    private $_member;
    private $_deductionType;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
     private $_data = [];
    
    public function __construct($branch)
    {
        $this->_branch = $branch;
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_member = new Member();
        $this->_deductionType = new DeductionType();
        $this->_deductionBalance = new InitialDeductionBalance();
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
    public function setReporType($id)
    {
        $this->_reportType = $id;
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
    
     public function data()
    {
        return $this->_data;
    }
    
    /**
     * run report
     * 
     * @return boolean
     */
    public function run()
    {
        $this->_loadReport();
       // if (0 == $this->_report['total']) {
       //     return false;
       // }
        $this->_report['total'] = 1;
        return true;
    }
    public function loadMReport()
    {
        $this->_sql->clear();
        $fmc_id = $this->_branch->id();
        $paymentTransactions = [];
        $this->_report['opb'] = 0;
        $this->_report['report'] = [];
        $bankTransTotal = [];
        $x = 0;      
        $this->_reportType = 'group';
        $reportTotal = 100;
        $memberQuery = "select * from " . $this->_member->getTableQuoted()." where id='{$this->_headId}' and fmc_id = {$fmc_id}";
        $member = $this->_member->queryRows($memberQuery);
        $deductionQuery = "select * from " . $this->_deductionType->getTableQuoted()."  order by deduction_type";
        $deductionHeads = $this->_deductionType->queryRows($deductionQuery); 
        $totalCatch = $this->getMemberTotalCatch($this->_headId);
        $totalBill = $this->getMemberTotalBill($this->_headId);
        foreach ($deductionHeads as $deductionItem) {       
            //Member Receipts
            $fmcReQFields = ['self_savings', 'eng_savings', 'death_fund', 'xmas_fund', 'ret_fund', 'fmc_loan', 'relief_loan', 'thattumadi', 'kerosene'];
            if (!in_array($deductionItem->code, $fmcReQFields)) {
                continue;
            }
            $expenseCode = 'exp_'.$deductionItem->code;
            $memberTransaction = $this->getMobMemberTransactions($this->_headId, $deductionItem->code);
            $cashTotal = 0;
            $bankTotal = 0;
            $membCashTotal = 0;
            $membBankTotal = 0;
            $balance =0;
            $paymentTransactions = $this->getMPaymentTransactions($this->_headId, $expenseCode);
            $deductionTypeOpening = $this->getDeductionOpeningByID($this->_headId, $deductionItem->id, $fmc_id);
            if($memberTransaction){ 
                 if($deductionTypeOpening > 0 || $memberTransaction[0]->amount >0) {
                    if($deductionTypeOpening > 0) {
                         $this->_report['report'][$x]['opening'] = (int) $deductionTypeOpening;
                    } else {
                        $this->_report['report'][$x]['opening'] = 0;
                    }
                   $reportTotal += $memberTransaction[0]->amount;     
                   $this->_report['report'][$x]['collection'] = (int) $memberTransaction[0]->amount;     
                   $this->_report['report'][$x]['particulars'] = $deductionItem->malayalam;
                   $this->_report['report'][$x]['withdrawal'] = 0;
                  // $this->_report['report'][$x]['balance'] =0;   
                  if($paymentTransactions){
                      $this->_report['report'][$x]['withdrawal'] = (int) $paymentTransactions[0]->amount;  
                  }
                  if($deductionItem->deduction_group_id == 2){
                      $balance = ($this->_report['report'][$x]['opening'] + $this->_report['report'][$x]['withdrawal']) - $this->_report['report'][$x]['collection'];
                   $this->_report['report'][$x]['balance'] = (int) $balance; 
                      /*  $this->_report['report'][$x]['balance'] = ($balance < 0)
                                                                ? '(' . number_format(abs($balance)) . ')'
                                                                : number_format($balance);*/
                  } else {
                    $balance = ($this->_report['report'][$x]['opening'] + $this->_report['report'][$x]['collection']) - $this->_report['report'][$x]['withdrawal'];
                    $this->_report['report'][$x]['balance'] = (int) $balance;
                    /* $this->_report['report'][$x]['balance'] = ($balance < 0)
                                                                ? '(' . number_format(abs($balance)) . ')'
                                                                : number_format($balance);*/
                  }
                 }
            } else {
                if($paymentTransactions){ 
                    if($deductionTypeOpening > 0 || $paymentTransactions[0]->amount > 0) {
                    if($deductionTypeOpening > 0) {
                         $this->_report['report'][$x]['opening'] = (int) $deductionTypeOpening;
                    } else {
                        $this->_report['report'][$x]['opening'] = 0;
                    }
                   $reportTotal += $memberTransaction[0]->amount; 
                   $this->_report['report'][$x]['collection'] = 0;      
                   $this->_report['report'][$x]['particulars'] = $deductionItem->malayalam;
                   $this->_report['report'][$x]['withdrawal'] = (int) $paymentTransactions[0]->amount ?? 0;
                    if($deductionItem->deduction_group_id == 2){
                      $balance = ($this->_report['report'][$x]['opening'] + $this->_report['report'][$x]['withdrawal']) - $this->_report['report'][$x]['collection'];
                      $this->_report['report'][$x]['balance'] = (int) $balance;
                     /* $this->_report['report'][$x]['balance'] = ($balance < 0)
                                                                ? '(' . number_format(abs($balance)) . ')'
                                                                : number_format($balance);*/
                  } else {
                    $balance = ($this->_report['report'][$x]['opening'] + $this->_report['report'][$x]['collection']) - $this->_report['report'][$x]['withdrawal'];
                    $this->_report['report'][$x]['balance'] =  (int) $balance;
                    /*$this->_report['report'][$x]['balance'] = ($balance < 0)
                                                                ? '(' . number_format(abs($balance)) . ')'
                                                                : number_format($balance);*/
                  }
                  // $this->_report['report'][$x]['balance'] = ($this->_report['report'][$x]['opening'] + $this->_report['report'][$x]['collection']) - $this->_report['report'][$x]['withdrawal'];
                  }
                }
                }
                if (
                    $deductionTypeOpening > 0 &&
                    (
                        !isset($this->_report['report'][$x]['particulars']) ||
                        $this->_report['report'][$x]['particulars'] !== $deductionItem->malayalam
                    )
                ) {
                    $reportTotal += $memberTransaction[0]->amount;
                    $this->_report['report'][$x]['particulars'] = $deductionItem->malayalam;
                    $this->_report['report'][$x]['opening']     = (int) $deductionTypeOpening;
                    $this->_report['report'][$x]['collection'] = 0; 
                    $this->_report['report'][$x]['withdrawal'] = 0;
                    $this->_report['report'][$x]['balance'] = (int) $deductionTypeOpening;
                }
                
                $x++;
            }
          $this->_data['memberName'] = $member[0]->name;
          $this->_data['totalCatch'] = $totalCatch[0]->amount;
          $this->_data['totalBill'] = $totalBill[0]->total_action_chits;
  }
  
   private function getMemberTotalCatch($headId)
        {
        
            $fmc_id = $this->_branch->id();
            $exta_condition = " and `h`.code in ('memb_advance','memb_payment')";
            $sql = "SELECT  SUM(`t`.`amount`) as amount from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' and t.transType ='P' "
                    . "and `t`.`member_id` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and t.action_chit_id > 0 and `t`.`fmc_id` = {$fmc_id} {$exta_condition}";
            $data = $this->_transactionModel->queryRows($sql); 
          
            return $data;          
    }
    
     private function getMemberTotalBill($headId)
        {
        
            $fmc_id = $this->_branch->id();
            $exta_condition = " and `h`.code in ('memb_advance','memb_payment')";
            $sql = "SELECT  COUNT(DISTINCT t.action_chit_id) AS total_action_chits from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' and t.transType ='P' "
                    . "and `t`.`member_id` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and t.action_chit_id > 0 and `t`.`fmc_id` = {$fmc_id} {$exta_condition}";
            $data = $this->_transactionModel->queryRows($sql); 
          
            return $data;          
    }
  
   private function getMPaymentTransactions($headId, $code)
        {
        
            $fmc_id = $this->_branch->id();
            $exta_condition = " and `h`.code='{$code}'";
            if($code === 'exp_fmc_loan'){
                $exta_condition = " and `h`.code in ('{$code}','personal')";
            } elseif($code === 'exp_relief_loan'){
                $exta_condition = " and `h`.code in ('{$code}','relief', 'relief2')";
            }
            $sql = "SELECT  SUM(`t`.`amount`) as amount from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' and t.transType ='P' "
                    . "and `t`.`member_id` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id} {$exta_condition}";
            $data = $this->_transactionModel->queryRows($sql); 
          
            return $data;          
    }
    private function getMobMemberTransactions($matchingHeadId = 0, $code = null)
    {
        
         $fmc_id = $this->_branch->id();
         $exta_condition = " and `h`.code='{$code}'";
         if($code === 'relief_loan'){
                $exta_condition = " and `h`.code in ('{$code}','DCB_re_finance')";
            } 
         if($matchingHeadId > 0 ){              
           $sql = "SELECT  SUM(`t`.`amount`) as amount FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1 and `m`.`transType` = 'R' and `m`.`member_id` = {$matchingHeadId} and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id} {$exta_condition}";
            $data = $this->_transactionModel->queryRows($sql);   
         
           return $data; 
         }
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
        $memberQuery = "select * from " . $this->_member->getTableQuoted()." where id='{$this->_headId}' and fmc_id = {$fmc_id}";
        $member = $this->_member->queryRows($memberQuery);
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
        $donorQuery = "select * from " . $this->_member->getTableQuoted()." where id='{$this->_headId}' and fmc_id = {$fmc_id} and member_type_id = 2";
        $donor = $this->_member->queryRows($donorQuery);
        $reportTotal = 100;
        if(isset($donor[0]->id)){
           //
        } else {
                
                $this->_report['opb'] =  $headOpening;
                //Member Receipts
                $paymentTransactions = $this->getPaymentTransactions($this->_headId);
                $memberTransaction = $this->getMemberTransactions($this->_headId);
              
                // $incomeTransaction = $this->getIncomeTransactions($this->_headId);
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
                     //  $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['particulars'] = $item->remarks;
                       if($item->action_chit_id){
                        $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
                       } else if (isset($item->loan_id)){
                           $this->_report['report'][$x]['transNo'] = $item->loan_id;
                       }
                       $this->_report['report'][$x]['bybank'] = 0;
                       $this->_report['report'][$x]['bycash'] =  0;
                       $reportTotal += $item->amount;
                       $cashReciptTotal += $item->amount;
                       $x++;
                    }
                }

                if($paymentTransactions){ 
                    foreach ($paymentTransactions as $item) 
                    {              
                       $this->_report['report'][$x]['cash'] =  0;
                       $this->_report['report'][$x]['bank'] =  0;
                       $this->_report['report'][$x]['transNo'] = $item->transNo;
                       $this->_report['report'][$x]['transDate'] = $item->transDate;
                       $this->_report['report'][$x]['name'] = $item->name;
                       $this->_report['report'][$x]['opening'] = 0;
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
                   // array_multisort(array_column($this->_report['report'], 'transDate'), SORT_ASC, $this->_report['report']);
                }
               $receiptPaymentTotal = $this->_report['opb'] + $cashReciptTotal;
               $this->_report['opb'] = number_format($this->_report['opb'], 2);
        }
       
        
        $month = date("F", strtotime($this->_endDate));
        $year = date('Y', strtotime($this->_endDate));
       
      
          $this->_report['Month'] = strtoupper($month);
          $this->_report['Year'] = $year;
         // $this->_report['cashClosing'] = $cashClosing;
          $this->_report['transactions'] = $this->_report;
          $this->_report['cashPaymentTotal'] =  number_format($cashPaymentTotal, 2);
          $this->_report['reportTotal'] = number_format($reportTotal, 2);
          $this->_report['cashTotal'] = number_format($cashTotal, 2);
        //  $this->_report['cashClosing'] = number_format($cashClosing, 2);
          $this->_report['byBankTotal'] = number_format($byBankTotal, 2);
          $this->_report['bankTotal'] = number_format($bankTotal, 2);
          $this->_report['bycashTotal'] = number_format($bycashTotal, 2);          
          $this->_report['bankClosing'] = number_format($bankClosing, 2);
          $this->_report['toBankPaymentTotal'] = number_format($toBankPaymentTotal, 2);
          $this->_report['ledgerName'] = $member[0]->name;
//          $this->_report['auctionChitAmount'] = $auctionChitopening;
          $this->_report['donorCashTotal'] = number_format($donorCashTotal, 2);
          $this->_report['catchTotal'] = number_format($catchTotal, 2);
          $this->_report['memberAdvancePaid'] = number_format($memberAdvancePaid, 2);
          //$this->_report['reciptBankTotal'] = number_format($receiptBankTotal, 2);
          
    }
  public function loadMReportXX()
    {
        $this->_sql->clear();
        $fmc_id = $this->_branch->id();
        $paymentTransactions = [];
        $this->_report['opb'] = 0;
        $this->_report['report'] = [];
        $bankTransTotal = [];
        $x = 0;
       
        /*$memberQuery = "select * from " . $this->_member->getTableQuoted()." where id='{$this->_headId}' and fmc_id = {$fmc_id}";
        $member = $this->_member->queryRows($memberQuery);
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
        $totalAmount = 0;
        //Check donor ledger
        $donorQuery = "select * from " . $this->_member->getTableQuoted()." where id='{$this->_headId}' and fmc_id = {$fmc_id} and member_type_id = 2";
        $donor = $this->_member->queryRows($donorQuery);*/
        $this->_reportType = 'group';
        $reportTotal = 100;
                $fmcSavingsFields = ['self_savings', 'eng_savings', 'death_fund', 'xmas_fund', 'ret_fund'];
                $fmcLoanFields = ['fmc_loan', 'relief_loan', 'thattumadi', 'kerosene'];
                $fmcMFFields = ['UBI', 'IOB', 'MF', 'CB'];
                $groupedTransactions = [
                    'savings' => [],
                    'loan'    => [],
                    'mf'      => [],
                    'other'   => []
                ];
               // $this->_report['opb'] =  $headOpening;
                //Member Receipts
                $paymentTransactions = $this->getPaymentTransactions($this->_headId);
                $memberTransaction = $this->getMemberTransactions($this->_headId);
                $fmcReceiptsGroups  = $this->groupMemberTransactions($memberTransaction, 'collection');
                $fmcPaymentsGroups  = $this->groupMemberTransactions($paymentTransactions, 'payments');
                $finalGroups = $this->mergeGroups($fmcReceiptsGroups, $fmcPaymentsGroups);
                $this->_report = $finalGroups;
    
  }
    
    public function groupMemberTransactions(array $transactions, string $type)
    {
        $fmcSavingsFields = ['self_savings', 'eng_savings', 'death_fund', 'xmas_fund', 'ret_fund'];
        $fmcLoanFields    = ['fmc_loan', 'relief_loan', 'thattumadi', 'kerosene'];
        $fmcMFFields      = ['UBI', 'IOB', 'MF', 'CB'];

        $groups = [
            'savings' => [],
            'loan'    => [],
            'mf'      => [],
            'other'   => [],
        ];

        foreach ($transactions as $item) {

            $amount = (float) $item->amount;
            $field  = $item->code;   // e.g. self_savings, fmc_loan, UBI, etc.

            // Decide which bucket this field belongs to
            if (in_array($field, $fmcSavingsFields, true)) {
                $bucket = 'savings';
            } elseif (in_array($field, $fmcLoanFields, true)) {
                $bucket = 'loan';
            } elseif (in_array($field, $fmcMFFields, true)) {
                $bucket = 'mf';
            } else {
                $bucket = 'other';
            }

            // Initialize if not set
            if (!isset($groups[$bucket][$field])) {
                $groups[$bucket][$field] = [
                    'collection' => 0,
                    'payments'   => 0,
                ];
            }

            // Add amount to the correct type
            $groups[$bucket][$field][$type] += $amount;
    }

    return $groups;
}
    public function mergeGroups(array $a, array $b)
    {
        foreach ($b as $bucket => $fields) {
            foreach ($fields as $field => $values) {

                if (!isset($a[$bucket][$field])) {
                    $a[$bucket][$field] = [
                        'collection' => 0,
                        'payments'   => 0,
                    ];
                }

                $a[$bucket][$field]['collection'] += $values['collection'];
                $a[$bucket][$field]['payments']   += $values['payments'];
            }
        }

        return $a;
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
    
     public function getDeductionOpeningByID($member_id, $deductionTypeID, $endDate = null)
    {
        $this->_sql->clear();
        $fydata = $this->getFinancialYearInfo();
       // $this->_startDate = $fydata['year'];
        $openingBalance = 0;
       /* if($endDate){
            $this->_endDate = DateHelper::fromDatepicker($endDate);
        } else {
            $this->_endDate = DateHelper::today();
        }*/
        $openingQuery = "select * from " . $this->_deductionBalance->getTableQuoted()." where member_id={$member_id} and deduction_type_id={$deductionTypeID}";
        $data = $this->_headModel->queryRows($openingQuery);      
        if(isset($data[0]->opening_balance)){
             $openingBalance =  $data[0]->opening_balance;
        }
       return $openingBalance;
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
           if($this->_reportType === 'group')
           {
            $sql = "SELECT MAX(t.transBy) AS transBy, MAX(t.action_chit_id) AS action_chit_id, MAX(t.transNo) AS transNo, MAX(m.name) AS name, MAX(t.transDate) AS transDate, GROUP_CONCAT(DISTINCT t.particulars SEPARATOR ', ') AS particulars,  SUM(`t`.`amount`) as amount, h.code  from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' and t.transType ='P' "
                    . "and `t`.`member_id` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id} GROUP BY h.id, h.transType";
            $data = $this->_transactionModel->queryRows($sql); 
           }else {
                 $sql = "SELECT `h`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "where 1=1 and `t`.`status` = '1' and t.transType ='P' "
                    . "and `t`.`member_id` = {$headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
           }
            return $data;          
    }
    
    private function getMemberCashReceiptTransactions($headId)
    {
           $fmc_id = $this->_branch->id();
          if($this->_reportType === 'group')
           {
            $sql = "SELECT MAX(`t`.`transNo`) as transNo, MAX(t.action_chit_id) AS action_chit_id, max(`t`.`loan_id`) as loan_id, MAX(t.transDate) AS transDate,  MAX(m.name) AS name, max(`t`.`transBy`) as transBy, GROUP_CONCAT(DISTINCT t.particulars SEPARATOR ', ') AS particulars, SUM(`t`.`amount`) as amount, h.code from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`member_id` = {$headId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id} GROUP BY t.id, t.transType";
           }else {
            $sql = "SELECT `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`member_id` = {$headId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
           }
            $data = $this->_transactionModel->queryRows($sql);
            return $data;
    }
    
    private function getMemberTransactions($matchingHeadId = 0)
    {
         $fmc_id = $this->_branch->id();
         if($matchingHeadId > 0 ){    
           if($this->_reportType === 'group')
           {
            $sql = "SELECT MAX(m.transBy) AS transBy, MAX(m.action_chit_id) AS action_chit_id, MAX(m.receiptNo) AS receiptNo, MAX(a.name) AS name, MAX(m.transDate) AS transDate, GROUP_CONCAT(DISTINCT t.remarks SEPARATOR ', ') AS remarks,  SUM(`t`.`amount`) as amount,h.code FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1 and `m`.`transType` = 'R' and `m`.`member_id` = {$matchingHeadId} and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id} GROUP BY h.id, h.transType";
            $data = $this->_transactionModel->queryRows($sql);   
           }else {
           $sql = "SELECT `h`.`transType`,`m`.`receiptNo`, `m`.`action_chit_id`, `m`.`loan_id`, `a`.`name`, `m`.`transDate`,  `t`.`remarks`, `t`.`amount`, `m`.`transBy` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1 and `m`.`transType` = 'R' and `m`.`member_id` = {$matchingHeadId} and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
           }
           return $data; 
         }
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
    
   
    
    private function getHeadOpening($headID, $date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_branch->id();
            $date = $this->_startDate;
            $opb = 0;
            $cashPayment = 0;
        //Get Opening amount
        //Cash Receipts start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`member_id` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $receiptSum = $data[0]->sum;
            $sql = "SELECT sum(amount) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                    . "  where 1=1  and `m`.`member_id` = $headID and `m`.`status` = '1' and `m`.`transDate` < '$date' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            $memberReceipt = $data[0]->sum;
            $cashTotal = $receiptSum + $memberReceipt + $opb;
        //Cash Receipts ends here
        //Cash Payments start here
            $sql = "SELECT sum(amount) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`member_id` = $headID and `t`.`transDate` < '$date' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql); 
           // $cashPayment =  $data[0]->sum;
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
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 and `t`.`member_id` ={$headID} "
                . "and `m`.`transDate` between '{$fydata['year']}' and '{$end_date}'  and `m`.`status` = '1' and `m`.`fmc_id` = {$fmc_id}";
        $data = $this->_membTrans->queryRows($sql);
        if(!$data[0]->sum){
            $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "where 1=1 and `t`.`member_id` = {$headID} and `t`.`transType` ='R' and `t`.`status` = '1' "
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
                . "where 1=1 and `t`.`member_id`={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$fydata['year']}' and '{$end_date}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
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
