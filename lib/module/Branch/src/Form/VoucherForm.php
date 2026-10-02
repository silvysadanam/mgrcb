<?php
/**
 * Branch TransactionForm Form
 * 
 * @category   Branch
 * @package    Branch\Form
 */

namespace Branch\Form;


use Web\Form\Form;
use Web\Utility\DateHelper;
use Web\Utility\FormHelper;
use Shared\Utility\AccountsHelper;
use Model\{RPTransMemberDetails, Fmc, RPHeads, DeductionMemberMonthly};
use Model\DeductionMemberDaily;
use Shared\Utility\AuctionSummary;
use Shared\Utility\Transactions;
use Branch\Utility\{BranchStore};
use Shared\Utility\AccountSettings;
use DateTime;

class VoucherForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    private $_sql;
    private $_branch;
    /**
     * 
     * @var boolean
     */
    private $_allowDuplicate = true;

    public function __construct($fmc_id)
    {
        parent::__construct();
        $fields = ['receipt_date', 'page_no', 'code', 'committee_approval', 'voucher_number', 'donor_id', 'member_id', 'non_member', 'payment_type', 'note', 'bank_id', 'transfer_id', 'collectionID'];
        $multiple = ['head', 'amount', 'total'];
        $this->addFields($fields);
        $this->addFields($multiple);
        $this->multiselect($multiple);
        $this->_headModel =  new RPHeads();
        $this->_model = new DeductionMemberDaily();
        $this->_monthlyModel = new DeductionMemberMonthly();
        $this->_sql = $this->_model->getSql();
        $this->_branch = new BranchStore($fmc_id);
        $this->_montlysql = $this->_monthlyModel->getSql();
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
        if ($this->validator->isNull($this->_formValues['receipt_date'])) {
            $this->error->notSelected('Date');
        }
       // if ($this->validator->isNull($this->_formValues['page_no'])) {
       //     $this->error->notSelected('Page No');
       // }
        if ($this->validator->notSelected($this->_formValues['donor_id']) && $this->validator->notSelected($this->_formValues['member_id']) && $this->validator->isNull($this->_formValues['non_member'])) {
            $this->error->notSelected('Donor/Member/Non Member option');
        }
        
        if($this->_formValues['payment_type'] == 'Bank') {
            if ($this->validator->notSelected($this->_formValues['bank_id'])) {
                $this->error->notSelected('Bank option');
            }
        }
        
        $this->_validateEntries();
        
        return $this->noError();
    }
    
    /**
     * create receipt
     * 
     * @param int $managerId
     * @return int
     */
    public function create($managerId, $fmc_id)
    {
        
        $now = DateHelper::timestamp();
        $transBy =0;
        $bank_heads = [];
        $receipt = [];
        $deductionTypes = ['exp_fws' => 2, 'exp_union' => 3, 'exp_auction' => 4];
        $paymentType = $this->_formValues['payment_type'];
        if($paymentType === 'Bank'){
            $transBy = $this->_formValues['bank_id'];
            $bankHeads = $this->_headModel->checkByColumnByFmc('id', $transBy, $fmc_id);
            $bank_heads['head_name'] = $bankHeads->head_name;
            $bank_heads['code'] = $bankHeads->code;
        }
        if(!$this->_formValues['page_no']){
            $this->_formValues['page_no'] = 0;
        }
        //get Head details
        $rpheads = $this->_headModel->checkByColumnByFmc('id', $this->_formValues['head'][0], $fmc_id);
        $heads['head_name'] = $rpheads->head_name;
        $heads['code'] = $rpheads->code;
        $fmcModel = new Fmc();
        $fmc = $fmcModel->find($fmc_id);
        $insert = [
            'transNo' => $this->_formValues['voucher_number'],
            'transDate' => DateHelper::fromDatepicker($this->_formValues['receipt_date']),
            'donor_id' => $this->_formValues['donor_id'],
            'member_id' => $this->_formValues['member_id'],
            'non_member' => $this->_formValues['non_member'],
            'amount'  => $this->_formValues['amount'][0],
            'headID' => $this->_formValues['head'][0],
            'particulars' =>  $this->_formValues['note'],
            'code_no' =>  $this->_formValues['code'],
            'page_no' =>  $this->_formValues['page_no'],
            'committee_approval' =>  $this->_formValues['committee_approval'],
            'created_by' => $managerId,
            'fmc_id' => $fmc_id,
            'transBy' => $transBy,
            'transType' => 'P',
            'created_at' => $now,
            'updated_at' => $now,
            'trans_to' => $this->_formValues['transfer_id']
            
        ];
        $id = $this->save($insert);
        if($this->_formValues['transfer_id'] > 0){           
            $trans = new Transactions();            
            $branch_id = $this->_formValues['transfer_id'];
            $bank_heads['transType'] = 'Bank';            
            if($transBy > 0){
               $bankDetails = $trans->getHeadID($bank_heads, $branch_id);
               $transBy = $bankDetails['id'];
            }
            $particulars = $heads['head_name'] ." - Fund Transfer";
            $heads['code'] = "inc_".str_replace(" ", "_", $rpheads->head_name);
            $heads['transType'] = 'Direct Income';
            $options = new AccountSettings($branch_id);
            $options->load();
            $receiptNo = $options->getNextFWSReceiptNo();
            $receipt = [
                'receipt' => $receiptNo,
                'transDate' => DateHelper::fromDatepicker($this->_formValues['receipt_date']),
                'non_member' => $fmc->name,
                'member_id' => 0,
                'amount'  => $this->_formValues['amount'][0],
                'remarks' => $particulars,
                'created_by' => $managerId,
                'transBy' => $transBy,
                'created_at' => $now,
                'updated_at' => $now,
                'trans_from' => $fmc_id,
                'voucher_id' => $id
            ];
            $trans->generateReceipt($heads, $receipt, $branch_id);
        }
        //Contribution balance caluclation
        if($this->_formValues['collectionID'] > 0) {
             $head = $this->_headModel->checkByColumnByFmc('id', $this->_formValues['collectionID'], $fmc_id);
              if(isset($deductionTypes[$head->code])){
                 $deductionTypeID = $deductionTypes[$head->code];
                 $this->balanceCalculator($deductionTypeID, $id, $this->_formValues['receipt_date']);
              }
        }
        /*$default = [
            'transID' => $id,
            'headID' => 0,
            'amount' => 0,
            'remarks' => ''
        ];
        $accountHelper = new AccountsHelper($fmc_id);
        $heads = $accountHelper->getHeads();
        $accountHeads = [];
        foreach ($heads as $head) {
            $accountHeads[$head->id] = $head->head_name;
        }
        $entryModel = new RPTransMemberDetails();
        $entry = $entryModel->getInsertPreparedStatement(array_keys($default));
        
        foreach ($this->_formValues['head'] as $index => $headId) {
            $record = $default;
            $record['headID'] = $headId;
            $record['amount'] = intval($this->_formValues['amount'][$index]);
            $record['remarks'] = $accountHeads[$headId];
            $entry->execute(array_values($record));
        }*/
        return $id;
    }
    
    private function balanceCalculator($deductionTypeID, $voucher_id, $endDate)
    {
        $this->_sql->clear();
        $fydata = $this->getFinancialYearInfo($endDate);
        $this->_startDate = $fydata['year'];
        if($endDate){
            $this->_endDate = DateHelper::fromDatepicker($endDate);
        } else {
            $this->_endDate = DateHelper::today();
        }
        $this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereId('deduction_type_id', $deductionTypeID)
                   ->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where = $this->_sql->getWhere();
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }
        
        $query2 = 'SELECT `id`, `deduction_type_id`, '
                . '`deduction_amount`, '
                . '`advance`, `opening_balance`, '
                . '`withdrawal`, '
                . '`closing_balance`'
                . 'FROM ' . $this->_model->getTableQuoted()
                . ' WHERE ' . $where ;
        $list = $this->_model->queryRows($query2);
        
        foreach($list as $items) {
            if($items->opening_balance > 0 ){
                $openingQuery = " `opening_balance` = 0 ,";                
            } else {
                $openingQuery = "";
            }
            $opening = $items->opening_balance + $items->deduction_amount;
            $query1 = 'UPDATE `' . $this->_model->getTable()
                    . '` SET `closing_balance` = `closing_balance` - ' . $opening .',' . $openingQuery
                    . '`withdrawal` = ' . $items->deduction_amount .', `deduction_amount` = `deduction_amount` - ' . $items->deduction_amount .', `voucher_id` = '. $voucher_id
                    . ' WHERE id= '. $items->id;
            $this->_model->exec($query1);
        }
        $dates = $this->_getDates($this->_endDate);
        $this->_montlysql->clear();
        $this->_montlysql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereId('deduction_type_id', $deductionTypeID)
                   ->whereEqual('deduction_date', $dates[1]);
        $where = $this->_montlysql->getWhere();
       
        $query3 = 'SELECT `id`, `deduction_type_id`, '
                . '`deduction_amount`, '
                . '`advance`, `opening_balance`, '
                . '`withdrawal`, '
                . '`closing_balance`'
                . 'FROM ' . $this->_monthlyModel->getTableQuoted()
                . ' WHERE ' . $where ;
        $list1 = $this->_model->queryRows($query3);
        foreach($list1 as $mitems) {
            if($mitems->opening_balance > 0 ){
                $openingQuery = " `opening_balance` = 0 ,";                
            } else {
                $openingQuery = "";
            }
            $opening = $mitems->opening_balance + $mitems->deduction_amount;
            $query1 = 'UPDATE `' . $this->_monthlyModel->getTable()
                    . '` SET `closing_balance` = `closing_balance` - ' . $opening .',' . $openingQuery
                    . '`withdrawal` = ' . $mitems->deduction_amount .', `deduction_amount` = `deduction_amount` - ' . $mitems->deduction_amount .', `voucher_id` = '. $voucher_id
                    . ' WHERE id= '. $mitems->id;
            $this->_monthlyModel->exec($query1);
        }
    }
    
    /* private function getFinancialYearInfo()
    {
        $today = date("Y-m-d");
         $date=date_create($today);
        if (date_format($date,"m") >= 4) {//On or After April (FY is current year - next year)
            $financial_year = (date_format($date,"Y")) . '-' . (date_format($date,"y")+1);
            $currentYear = (date_format($date,"Y"));
        } else {//On or Before March (FY is previous year - current year)
            $financial_year = (date_format($date,"Y")-1) . '-' . date_format($date,"y");
            $currentYear = (date_format($date,"Y")-1);
        }
        $currentFinancialYearWithDate = $currentYear."-04-01";
        return array('year' => $currentFinancialYearWithDate, 'FY' => $financial_year);
    }*/
    private function getFinancialYearInfo($endDate = null)
{
    if ($endDate) {
        $date = DateTime::createFromFormat('d/m/Y', $endDate);

        // Handle invalid date safely
        if (!$date) {
            throw new Exception("Invalid date format. Expected d/m/Y");
        }
    } else {
        $date = new DateTime();
    }

    $month = (int)$date->format("m");
    $year  = (int)$date->format("Y");

    if ($month >= 4) {
        $currentYear = $year;
        $financial_year = $year . '-' . substr($year + 1, -2);
    } else {
        $currentYear = $year - 1;
        $financial_year = ($year - 1) . '-' . substr($year, -2);
    }

    return [
        'year' => $currentYear . "-04-01",
        'FY'   => $financial_year
    ];
}
    
    private function _getDates($date)
    {
        $dateTime = DateHelper::getDateTime($date);
        $year = $dateTime->format('Y');
        $month = $dateTime->format('n');
        $days = $dateTime->format('t');
        $dateTime->setDate($year, $month, 1);
        $startDate = $dateTime->format(DateHelper::MYSQL_DATE);
        $dateTime->setDate($year, $month, $days);
        $endDate = $dateTime->format(DateHelper::MYSQL_DATE);
        
        return [$startDate, $endDate];
        
    }
    private function _validateEntries()
    {
        if (0 == count($this->_formValues['head'])) {
            $this->error->add('No entries added');
            
            return;
        }
        
        $counter = 0;
        foreach ($this->_formValues['head'] as $index => $fishId) {
            ++$counter;
            if ($this->validator->notSelected($fishId)) {
                $this->error->add('Entry ' . $counter . ': Head not selected');
            }
            
            if ($this->validator->isNull($this->_formValues['amount'][$index])) {
                $this->error->add('Entry ' . $counter . ': Amount required');
            } elseif (!is_numeric($this->_formValues['amount'][$index]) || $this->_formValues['amount'][$index] <= 0) {
                $this->error->add('Entry ' . $counter . ': Amount is invalid');
            }
        }
    }
}
