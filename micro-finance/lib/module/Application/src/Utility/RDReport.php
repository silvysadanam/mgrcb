<?php
/**
 * Branch Deduction Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Application\Utility;
use Model\{RPTransMember, RPTransMemberDetails, RPTransactionDetails, RPHeads};
use Model\DeductionMemberDaily;
use Shared\Utility\ReportHelper;
use Web\Utility\DateHelper;


class RDReport
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
    
    public function __construct($fmc_id)
    {
        $this->_model = new RPTransMember();
        $this->_membTrans = new RPTransMemberDetails();
        $this->_headModel = new RPHeads();
        $this->_transactionModel = new RPTransactionDetails();
        $this->_sql = $this->_model->getSql();
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
        /*$this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereIn('deduction_type_id', $this->_report['deductions'])
                   ->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where = $this->_sql->getWhere();
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }*/
        $bankOpening = [];
        $bankClosing = [];
        $receipts = [];
        $payments = [];
        $month = date("F", strtotime($this->_endDate));
        $year = date('Y', strtotime($this->_endDate));
        $paymentsQuery = "select * from " . $this->_headModel->getTableQuoted()." where TransType in ('Direct Expense','Indirect Expense') and fmc_id = {$fmc_id} order by head_name";
        $paymentHeads = $this->_headModel->queryRows($paymentsQuery);
        //$NumRows = mysql_num_rows($result);
        $incomeQuery = "select * from  " . $this->_headModel->getTableQuoted()." where TransType !='Direct Expense' and fmc_id = {$fmc_id} order by head_name";
        $incomeHeads = $this->_headModel->queryRows($incomeQuery);
        $bankQuery = "select * from  " . $this->_headModel->getTableQuoted()." where TransType ='bank' and fmc_id = {$fmc_id} order by head_name";
        $bankHeads = $this->_headModel->queryRows($bankQuery);        
        $cashOpening = $this->getCashOpening();
        $subTotalOpening = $cashOpening;       
        $subTotalClosing = 0;
        foreach ($bankHeads as $rows) {
            $bank_opening = $this->getBankOpening($rows->id);
            if(isset($bank_opening)) {
                $bankOpening[$rows->id]['head_name'] = $rows->head_name;
                $bankOpening[$rows->id]['amount'] =  $bank_opening;
                $subTotalOpening += $bank_opening;
            }
        }
        
        //Bank transactions for closing
        foreach ($bankHeads as $rows) {
            $bank_closing = $this->getBankTransactions($rows->id, $bankOpening[$rows->id]['amount']);
            if(isset($bank_closing)) {
                $bankClosing[$rows->id]['head_name'] = $rows->head_name;
                $bankClosing[$rows->id]['amount'] =  $bank_closing;
                $subTotalClosing += $bank_closing;
            }
        }
        
        $receiptCount  = 0;
        $receiptsCurrentTotal = $subTotalOpening;
        $receiptsCumilativeTotal = 0;
        $receiptSubTotal = 0;
        $receiptTotal = $subTotalOpening;
        foreach($incomeHeads as $item){
            $headSum = 0;
            $monthTotal = 0;
            $cumulativeTotal = 0;
            $headSum = $this->getSumByHeadID($item->id);           
            $cumulativeSum = $this->getCumulativeByHeadID($item->id);
            if($headSum > 0 || $cumulativeSum >0) {
                $receipts[$receiptCount]['head_name'] = $item->head_name;
                $receipts[$receiptCount]['current'] = $headSum;
                $receipts[$receiptCount]['cumulative'] = $cumulativeSum;
                $receiptsCurrentTotal += $headSum;
                $receiptSubTotal += $headSum;
                $receiptsCumilativeTotal += $cumulativeSum;
                $receiptCount ++;
            }
        }
        
        $paymentCount = 0;
        $paymentsCurrenTotal = 0;
        $paymentsCumilativeTotal = 0;
        $paymentTotal = 0;
        $paymentSubTotal = 0;
        foreach($paymentHeads as $item){
            $headSum = 0;
            $monthTotal = 0;
            $cumulativeTotal = 0;
            $headSum = $this->getPaymentyHeadID($item->id);
            $cumulativeSum = $this->cumulativePaymentyHeadID($item->id);
            if($headSum > 0 || $cumulativeSum >0) {
                $payments[$paymentCount]['head_name'] = $item->head_name;
                $payments[$paymentCount]['current'] = $headSum;
                $payments[$paymentCount]['cumulative'] = $cumulativeSum;
                $paymentsCurrenTotal += $headSum;
                $paymentsCumilativeTotal += $cumulativeSum;
                $paymentSubTotal += $headSum;
                $paymentCount ++;
            }
        }
        if($receiptCount > $paymentCount){
            for ($x = 0; $x < $receiptCount; $x++) 
            {
               $this->_report['report'][$x]['receipt_head_name'] = $receipts[$x]['head_name'];
               $this->_report['report'][$x]['receipt_current'] = $receipts[$x]['current'];
               $this->_report['report'][$x]['receipt_cumulative'] = $receipts[$x]['cumulative'];
               $this->_report['report'][$x]['receipt_total'] =  $receipts[$x]['current'] + $receipts[$x]['cumulative'];
               $receiptTotal += $receipts[$x]['current'] + $receipts[$x]['cumulative'];
               if(isset($payments[$x])) {
                    $this->_report['report'][$x]['payment_head_name'] = $payments[$x]['head_name'];
                    $this->_report['report'][$x]['payment_current'] = $payments[$x]['current'];
                    $this->_report['report'][$x]['payment_cumulative'] = $payments[$x]['cumulative']; 
                    $this->_report['report'][$x]['payment_total'] = $payments[$x]['current'] + $payments[$x]['cumulative']; 
                    $paymentTotal += $payments[$x]['current'] + $payments[$x]['cumulative'];
               }
            }
        }
        else {
            for ($x = 0; $x < $paymentCount; $x++) 
            {
               $this->_report['report'][$x]['payment_head_name'] = $payments[$x]['head_name'];
               $this->_report['report'][$x]['payment_current'] = $payments[$x]['current'];
               $this->_report['report'][$x]['payment_cumulative'] = $payments[$x]['cumulative']; 
               $this->_report['report'][$x]['payment_total'] = $payments[$x]['current'] + $payments[$x]['cumulative']; 
               $paymentTotal += $payments[$x]['current'] + $payments[$x]['cumulative'];              
               if(isset($receipts[$x])) {
                    $this->_report['report'][$x]['receipt_head_name'] = $receipts[$x]['head_name'];
                    $this->_report['report'][$x]['receipt_current'] = $receipts[$x]['current'];
                    $this->_report['report'][$x]['receipt_cumulative'] = $receipts[$x]['cumulative'];
                    $this->_report['report'][$x]['receipt_total'] =  $receipts[$x]['current'] + $receipts[$x]['cumulative'];
                    $receiptTotal += $receipts[$x]['current'] + $receipts[$x]['cumulative'];
               }
        }
        }
        $cashClosing = $this->getCashTransactions($cashOpening);
        $grandPaymentTotal = $subTotalClosing + $paymentSubTotal + $cashClosing;
        $receipts['subTotalOpening'] = $subTotalOpening;
        $payments['subTotalClosing'] = sprintf('%0.2f', $grandPaymentTotal);      
        $receipts['receiptsCurrentTotal'] = sprintf('%0.2f', $receiptsCurrentTotal);
        $receipts['receiptSubTotal'] = sprintf('%0.2f', $receiptSubTotal);
        
        $receipts['receiptsCumilativeTotal'] = sprintf('%0.2f', $receiptsCumilativeTotal);
        $receipts['receiptTotal'] = sprintf('%0.2f', $receiptTotal);
        $payments['paymentsCurrenTotal'] = sprintf('%0.2f', $paymentsCurrenTotal);
        $payments['paymentsCumilativeTotal'] = sprintf('%0.2f', $paymentsCumilativeTotal);
        $payments['paymentTotal'] = sprintf('%0.2f', $paymentTotal);
        $payments['paymentSubTotal'] = sprintf('%0.2f', $paymentSubTotal);
        
        /*$query2 = 'SELECT DISTINCT `member_id`, `deduction_type_id`, '
                . 'SUM(`deduction_amount`) AS `deduction_amount`, '
                . 'SUM(`advance`) AS `advance`, '
                . 'SUM(`withdrawal`) AS `withdrawal`, '
                . 'SUM(`repayment`) AS `repayment` '
                . 'FROM ' . $this->_model->getTableQuoted()
                . ' WHERE ' . $where . ' GROUP BY `member_id`, `deduction_type_id` '
                . 'ORDER BY `member_id`';
        $list = $this->_model->queryRows($query2);
        
        $this->_sql->clear();
        $this->_sql->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where2 = $this->_sql->getWhere();
        
        $query3 = 'SELECT `opening_balance` FROM ' . $this->_model->getTableQuoted()
                . ' WHERE `member_id` =? AND `deduction_type_id` =? AND ' . $where2
                . ' ORDER BY `deduction_date` ASC LIMIT 1';
        $opening = $this->_model->prepare($query3);
        
        $query4 = 'SELECT `closing_balance` FROM ' . $this->_model->getTableQuoted()
                . ' WHERE `member_id` =? AND `deduction_type_id` =? AND ' . $where2
                . ' ORDER BY `deduction_date` DESC LIMIT 1';
        $closing = $this->_model->prepare($query4);
        
        $sum = [
            'collection' => 0,
            'withdrawal' => 0,
            'opening' => 0,
            'closing' => 0,
        ];
        
        $default = [];
        $fields = array_keys($sum);
        
        $this->_report['report_total'] = [];
        foreach ($this->_report['deductions'] as $deductionItem) {
            $default[$deductionItem] = $sum;
        }
        
        $this->_report['report_total'] = $default;
        
        $this->_report['report'] = [];
        foreach ($list as $item) {
            if (! isset($this->_report['report'][$item->member_id])) {
                $this->_report['report'][$item->member_id] = $default;
            }
            
            $summary = [
                'collection' => $item->deduction_amount - $item->repayment,
                'withdrawal' => $item->advance + $item->withdrawal,
                'opening' => 0,
                'closing' => 0,
            ];
            
            $opening->execute([$item->member_id, $item->deduction_type_id]);
            $summary['opening'] = $opening->fetchColumn();
            $opening->closeCursor();
            
            $closing->execute([$item->member_id, $item->deduction_type_id]);
            $summary['closing'] = $closing->fetchColumn();
            $closing->closeCursor();
            
            $this->_report['report'][$item->member_id][$item->deduction_type_id] = $summary;
            
            foreach ($fields as $field) {
                $this->_report['report_total'][$item->deduction_type_id][$field] += $summary[$field];
            }
        }*/
        
          $this->_report['bankOpening'] = $bankOpening;
          $this->_report['cashOpening'] = $cashOpening;
          $this->_report['receipts'] = $receipts;
          $this->_report['payments'] = $payments;
          $this->_report['Month'] = strtoupper($month);
          $this->_report['Year'] = $year;
          $this->_report['cashClosing'] = $cashClosing;
          $this->_report['bankClosing'] = $bankClosing;
    }
    
    private function getCashOpening($date = null )
    {
            $cashTotal = 0;
            $fmc_id = $this->_fmc_id;
            $date = $this->_startDate;
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
        $fmc_id = $this->_fmc_id;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID` "
                . "left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` "
                . "where 1=1 and `t`.`headID` ={$headID} and `m`.`fmc_id` = {$fmc_id} and `m`.`status` = '1' "
                . "and `m`.`transDate` between '{$this->_startDate}' and '{$this->_endDate}'";
        $data = $this->_membTrans->queryRows($sql);
        if(!$data[0]->sum) {
             $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                     . " where 1=1 and `t`.headID='{$headID}' and `t`.`transType` ='R' and `t`.`transBy` >0 and `t`.`status` = '1' "
                     . "and `t`.`transDate` between '{$this->_startDate}'  and '{$this->_endDate}' and `t`.`fmc_id` = {$fmc_id}";
            $data = $this->_transactionModel->queryRows($sql);
        }
        return $data[0]->sum;
    }
    private function getCumulativeByHeadID($headID)
    {
        $fmc_id = $this->_fmc_id;
        $cumHeadAmount = 0;
      //  $fydata = $this->getFinancialYearInfo();
        $fydata =  $this->getFinancialYearInfoByDate();
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
                  //  . "and `t`.`transBy` >0 and `t`.`transDate` between '{$fydata['year']}'  and '{$end_date}' and `t`.`fmc_id` = {$fmc_id} "
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
        //$fydata = $this->getFinancialYearInfo();
        $fydata =  $this->getFinancialYearInfoByDate();
        $end_date = $this->getPreviousDay($this->_startDate);
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`headID`={$headID} and `t`.`status` = '1' "
                . "and `t`.`transType` = 'P' and `t`.`transDate` between '{$fydata['year']}' and '{$end_date}' and `t`.`fmc_id` = {$fmc_id}";
        $data = $this->_headModel->queryRows($sql);
        return $data[0]->sum;
    }

    /*private function getCashOpening($date)
    {
    //Get Opening amount
        $opb = 0;
        $sql = "SELECT `h`.`opb` from  " . $this->_headModel->getTableQuoted(). " `h` "
                . "where 1=1 and head_name ='Cash'";
        $data = $this->_headModel->queryRows($sql);
        if(isset($data[0]->opb)){
            $opb = $data[0]->opb;
        }
    //Cash Receipts start here
        $sql = "SELECT sum(`t`.`amount`) as sum from  ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`transType` = 'R' "
                . "and `t`.`transBy` = 0 and `t`.`transDate` < '{$date}'";
        $data = $this->_headModel->queryRows($sql);
        $receiptSum = $data[0]->sum;
        $sql = "SELECT sum(`t`.`amount`) as sum FROM ". $this->_membTrans->getTableQuoted() . " `t` "
                . "inner join " . $this->_model->getTableQuoted()." `m` ON `m`.`id` = `t`.`transID`"
                . " left join " . $this->_headModel->getTableQuoted()." `h` ON `h`.`id` = `t`.`headID` where 1=1 "
                . "and `m`.`transBy` = 0 and `m`.`transDate` < '{$date}'";
        $data = $this->_headModel->queryRows($sql);
        $memberReceipt =  $data[0]->sum;
        $cashTotal = $receiptSum + $memberReceipt + $opb;
    //Cash Receipts ends here
    //Cash Payments start here
        $sql = "SELECT sum(`t`.`amount`) as sum from ". $this->_transactionModel->getTableQuoted() . " `t` "
                . "where 1=1 and `t`.`transType` = 'P' "
                . "and `t`.`transBy` = 0 and `t`.`transDate` < '{$date}'";
        $data = $this->_headModel->queryRows($sql);
        $cashPayment =  $data[0]->sum;
    //Cash payment ends here
        return round(($cashTotal - $cashPayment),2);
    }*/
    
    private function getFinancialYearInfo()
    {
    
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
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);
    }
    private function getFinancialYearInfoByDate()
    {
    
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
