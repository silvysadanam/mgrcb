<?php
/**
 * Branch Deduction Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Application\Utility;
use Model\{RPTransMember, RPTransMemberDetails, RPTransactionDetails, RPHeads, Member};
use Model\DeductionMemberDaily;
use Shared\Utility\ReportHelper;
use Web\Utility\DateHelper;


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
    private $_fmc_id;
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    public function __construct($fmc_id)
    {
        //$this->_branch = $branch;
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
        $this->_member = new Member();
        $this->_fmc_id = $fmc_id;
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
        $fmc_id = $this->_fmc_id;
        $paymentTransactions = [];
        $bankTransTotal = [];
        $cashTotal = 0;
        $reportTotal = 0;
        $cashTransTotal = [];
        $cashPaymentTotal = 0;
        $receiptBankTotal = 0;
        $cashClosing = 0;
        $byBankTotal = 0;
        $bankClosing = 0 ;
        $toBankPaymentTotal = 0;
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
        $headQuery = "select * from " . $this->_headModel->getTableQuoted()." where id={$this->_headId} and fmc_id = {$fmc_id} order by head_name";
        $heads = $this->_headModel->queryRows($headQuery);
        $bankOpening = $this->getBankOpening($this->_headId);
        if($heads[0]->transType == 'Direct Expense' || $heads[0]->transType == 'Indirect Expense'){
            $paymentTransactions = $this->getPaymentTransactions($fmc_id);
        } else  if($heads[0]->transType == 'Direct Income' || $heads[0]->transType == 'Indirect Income'){
            //Member Receipts
            $receiptTotal = $this->getMemberReceipts($fmc_id);
            $cashTotal = $receiptTotal['cashTotal'];
            $reportTotal = $receiptTotal['cashTotal'] + $receiptTotal['bankTotal'];
            $receiptBankTotal = $receiptTotal['bankTotal'];            
        } else if($heads[0]->transType == 'Cash'){
            $cashTransTotal = $this->getCashTransactions($cashOpening);
            $reportTotal = $cashTransTotal['cashTotal'] + $cashTransTotal['cashPaymentTotal'] + $cashOpening;
            $cashTotal = $cashTransTotal['cashTotal'];
            $cashPaymentTotal = $cashTransTotal['cashPaymentTotal'];
            $cashClosing = $cashTotal - $cashPaymentTotal;  
            $this->_report['opb'] = number_format($cashOpening, 2);
        } else if($heads[0]->transType == 'Bank'){
            $bankTransTotal = $this->getBankTransactions($this->_headId);
            $reportTotal = $bankTransTotal['toBankPaymentTotal'] + $bankTransTotal['byBankPaymentTotal'] + $bankOpening;
            $toBankPaymentTotal = $bankTransTotal['toBankPaymentTotal'];
            $byBankTotal = $bankTransTotal['byBankPaymentTotal'];
            $bankClosing = ($toBankPaymentTotal + $bankOpening) - $byBankTotal;  
            $this->_report['opb'] = number_format($bankOpening, 2);
        }
         
        
        if($paymentTransactions){           
            foreach ($paymentTransactions as $item) 
            {              
               $this->_report['report'][$x]['transNo'] = $item->transNo;
               $this->_report['report'][$x]['transDate'] = $item->transDate;
               $this->_report['report'][$x]['name'] = $item->name;
               $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
               $this->_report['report'][$x]['particulars'] = $item->particulars;
               if($item->action_chit_id){
                $this->_report['report'][$x]['transNo'] = $item->action_chit_id;    
               } else if ($item->loan_id){
                   $this->_report['report'][$x]['transNo'] = $item->loan_id;
               }
               if($item->transBy > 0) {
                 $this->_report['report'][$x]['bank'] =  $item->amount;
               } else {
                    $this->_report['report'][$x]['cash'] =  $item->amount;
               }
               $this->_report['report'][$x]['bycash'] =  0;
               $this->_report['report'][$x]['bybank'] =  0;
               $reportTotal += $item->amount;
               $cashTotal += $item->amount;
               $x++;
            }
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
          $this->_report['cashClosing'] = number_format($cashClosing, 2);
          $this->_report['byBankTotal'] = number_format($byBankTotal, 2);
          $this->_report['bankClosing'] = number_format($bankClosing, 2);
          $this->_report['toBankPaymentTotal'] = number_format($toBankPaymentTotal, 2);
          $this->_report['ledgerName'] = $heads[0]->head_name;
          //$this->_report['reciptBankTotal'] = number_format($receiptBankTotal, 2);
          
    }
    
    private function getPaymentTransactions()
    {
            $fmc_id = $this->_fmc_id;
            $sql = "SELECT `m`.`fmc_id`,`t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`status` = '1' "
                    . "and `t`.`headID` = {$this->_headId} and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            return $data;          
    }
    
     
    private function getMemberReceipts()
    {
            $fmc_id = $this->_fmc_id;
            $receiptBankTotal = 0;
            $receiptTotal = [];
            $reportTotal = 0;
            $x = 0;
            $sql = "SELECT `a`.`fmc_id`, `m`.`receiptNo`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
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
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT  `m`.`fmc_id`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                     . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1'  and `t`.`headID` = {$this->_headId} "
                    . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $fmc_id = $this->_fmc_id;
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

    private function getCashTransactions($opb)
    {
            $cashTotal = [];
            $x = 0;
            $fmc_id = $this->_fmc_id;
            $reportCashPaymentTotal = 0;
        //Cash Receipts start here
            $sql = "SELECT `m`.`fmc_id`,`t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
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
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT `m`.`receiptNo`, `a`.`fmc_id`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id` FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                    . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` "
                    . "  where 1=1  and `m`.`transBy` = 0 and `m`.`transType` = 'R' and `m`.`status` = '1' and  `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);   
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $fmc_id = $this->_fmc_id;
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
            $fmc_id = $this->_fmc_id;       
            $byBankPaymentTotal = 0;
            $toBankPaymentTotal = 0;
            $x = 0;
            //Cash Deposit
            $sql = "SELECT  `m`.`fmc_id`, `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy`  from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' AND `t`.transBy = 0 " 
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT  `m`.`fmc_id`, `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT `a`.`fmc_id`, `m`.`receiptNo`, `m`.`action_chit_id`, `m`.`transBy`,  `a`.`name`, `m`.`member_id`, `m`.`transDate`, `t`.`amount`, `t`.`remarks`, `m`.`donor_id`  FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                    . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` " 
                     . "left join " . $this->_member->getTableQuoted()." `a` ON `a`.`id` = `m`.`member_id` where 1=1 "
                    . "and `m`.`transBy` = $headID and `m`.`status` = '1' and `m`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `m`.`fmc_id` = {$fmc_id}";
            $data = $this->_membTrans->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->receiptNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT `m`.`fmc_id`, `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`transBy` from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                    . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'P' and `t`.`status` = '1' "
                    . "and `t`.`transBy` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
            $sql = "SELECT  `t`.`transType`, `t`.`transNo`, `t`.`action_chit_id`, `t`.`loan_id`, `t`.`transDate`, `m`.`name`, `t`.`particulars`, `t`.`amount`, `t`.`fmc_id`, `t`.`transBy` from ". $this->_transactionModel->getTableQuoted() . " `t` "
                     . "left join " . $this->_member->getTableQuoted()." `m` ON `m`.`id` = `t`.`member_id` "
                    . "where 1=1 and `t`.`transType` = 'R' and `t`.`status` = '1' "
                    . "and `t`.`headID` = $headID and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
            if($data){
                foreach ($data as $item) 
                {
                    $this->_report['report'][$x]['transNo'] = $item->transNo;
                    $this->_report['report'][$x]['transDate'] = $item->transDate;
                    $this->_report['report'][$x]['fmc_id'] = $item->fmc_id;
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
        
        //$deductions = $this->_branch->deductionTypes();
        //$brachDeductionIds = array_keys($deductions);
        //$groupedDeductions = ReportHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
       // $this->_report['deductions'] = $groupedDeductions[$this->_groupId];
        
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
    
}
