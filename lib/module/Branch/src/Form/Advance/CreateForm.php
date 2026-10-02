<?php
/**
 * Branch Advance Create Form
 * 
 * @category    Branch
 * @package     Branch\Form
 * @subpackage  Branch\Form\Advance
 */

namespace Branch\Form\Advance;


use Web\Form\Form;
use Web\Utility\DateHelper;

use Model\{DeductionMemberDaily, DeductionMemberMonthly, AdvanceWithdrawalRepayment, DeductionType, RPHeads, RPTransactionDetails};
use Shared\Utility\AccountSettings;
use Shared\Utility\Transactions;

class CreateForm extends Form
{  
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var \Model\DeductionMemberDaily
     */
    private $_deductionMemberDailyModel;
    
    /**
     * 
     * @var \Model\DeductionMemberMonthly
     */
    private $_deductionMemberMonthlyModel;
    
    /**
     * 
     * @var \stdClass
     */
    private $_entry;
    
    /**
     * 
     * @var Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var boolean
     */
    private $_forwardRepayment = false;
    
    /**
     * 
     * @var string
     */
    private $_monthlyDate;
    
    /**
     * 
     * @var array
     */
    private $_options = [];
    
    private $_memberId;
    
    private $_deductionTypeId;
    
    private $_advanceDate;
    
    private $_advanceAmount;
    
    private $_withdrawal = true;
    
    private $_overdueAmount = 0;
    
    private $_advanceId;
    
    private $_fmcid;
    
    private $_deductionModel;
    
    private $_rpHeadModel;
    
    public function __construct($fmcid = 0)
    {
        parent::__construct();
        $fields = [
            'member_id', 'advance_amount', 'advance_date', 'deduction_type_id', 
            'description', 'note', 'auto_collect_overdue', 'payment_type', 'bank_id',
        ];
        
        $this->addFields($fields);
        
        $this->_deductionMemberDailyModel = new DeductionMemberDaily();
        $this->_deductionMemberMonthlyModel = new DeductionMemberMonthly();
        $this->_sql = $this->_deductionMemberDailyModel->getSql();
        $this->_fmcid = $fmcid;
        $this->_deductionModel = new DeductionType();
        $this->_rpHeadModel = new RPHeads();
    }
    
    /**
     * validate form
     *
     * @param array $advanceDeductions
     * @return boolean
     */
    public function isValid($advanceDeductions = [])
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        
        if ($this->validator->isNull($this->_formValues['advance_date'])) {
            $this->error->required('Date');
        }
        if ($this->validator->notSelected($this->_formValues['member_id'])) {
            $this->error->notSelected('Member');
        }
        
        if ($this->validator->notSelected($this->_formValues['deduction_type_id'])) {
            $this->error->notSelected('Deduction type');
        }
        
        if (! $this->_validateEntry($this->_formValues['advance_amount'])) {
            $this->error->invalid('Amount');
        }
        
        if ($this->validator->isNull($this->_formValues['description'])) {
            $this->error->required('Description');
        }
        
        if ($this->noError()) {
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $this->_formValues['member_id'])
                       ->whereId('deduction_type_id', $this->_formValues['deduction_type_id'])
                       ->whereGreater('advance_date', DateHelper::fromDatepicker($this->_formValues['advance_date']), true);
            if ($this->getModel()->selectTotal($this->_sql->getWhere()) > 0) {
                $this->error->add('Advance/Withdrawal exists on or after selected date');
            }
        }
        
        if ($this->noError()) {
            $this->_sql->clear();
            $this->_sql->whereId('member_id', $this->_formValues['member_id'])
                       ->whereId('deduction_type_id', $this->_formValues['deduction_type_id'])
                       ->whereLess('deduction_date', DateHelper::fromDatepicker($this->_formValues['advance_date']));
            $where = $this->_sql->getWhere();
            if (0 == $this->_deductionMemberDailyModel->selectTotal($where)) {
                $this->error->add('Previous deduction bill not found');
            } else {
                $this->_entry = $this->_deductionMemberDailyModel->selectRow($where, 'deduction_date DESC');
                if ($this->_formValues['advance_amount'] > $this->_entry->closing_balance) {
                    if (! in_array($this->_formValues['deduction_type_id'], $advanceDeductions)) {
                        $this->error->add('Advance NOT Allowed for Withdrawal only deduction type');
                    }
                }
            }
        }
        return $this->noError();
    }
    
    /**
     * create deduction bill
     * 
     * @return int
     */
    public function create($userId)
    {
        $transModel = new RPTransactionDetails();
        $this->_memberId = (int) $this->_formValues['member_id'];
        $this->_deductionTypeId = (int) $this->_formValues['deduction_type_id'];
        $this->_advanceDate = DateHelper::fromDatepicker($this->_formValues['advance_date']);
        $this->_advanceAmount = (int) $this->_formValues['advance_amount'];
        if ($this->_entry->closing_balance < $this->_advanceAmount) {
            $this->_withdrawal = false;
            $this->_overdueAmount = $this->_advanceAmount - $this->_entry->closing_balance;
            $this->_forwardRepayment = $this->_formValues['auto_collect_overdue'] > 0;
        }
        
        $this->_monthlyDate = $this->_getMonthlyDate($this->_entry->deduction_date);
        
        $insert = [
            'member_id' => $this->_memberId,
            'deduction_type_id' => $this->_deductionTypeId,
            'advance_date' => $this->_advanceDate,
            'advance_amount' => $this->_advanceAmount,
            'overdue_amount' => $this->_overdueAmount,
            'balance' => $this->_advanceAmount,
            'overdue_balance' => $this->_overdueAmount,
            'deduction_bill_date' => $this->_entry->deduction_date,
            'adjusted' => 1,
            'description' => $this->_formValues['description'],
            'note' => $this->_formValues['note'],
            'created_at' => DateHelper::timestamp(),
            'created_by' => $userId,
            'withdrawal' => 0,
            'auto_collect_overdue' => $this->_formValues['auto_collect_overdue'],
        ];
        
        if ($this->_withdrawal) {
            $insert['withdrawal'] = 1;
            $deduction = $this->_advanceAmount;
            $balance = $this->_entry->closing_balance - $deduction;
        } else {
            $deduction = $this->_entry->closing_balance;
            $balance = 0;
        }
        
        $this->_advanceId = $this->save($insert);
        
        $update = [
            'advance' => $this->_entry->advance + $deduction,
            'closing_balance' => $balance,
        ];
        
        $this->_deductionMemberDailyModel->updateById($update, $this->_entry->id);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $this->_deductionTypeId)
                   ->whereGreater('deduction_date', $this->_entry->deduction_date);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMemberDailyModel->selectTotal($where) > 0) {
            $query1 = 'UPDATE `' . $this->_deductionMemberDailyModel->getTable()
                    . '` SET `opening_balance` = `opening_balance` - ' . $deduction . ', '
                    . '`closing_balance` = `closing_balance` - ' . $deduction 
                    . ' WHERE ' . $where;
            $this->_deductionMemberDailyModel->exec($query1);
        }
        
        if ($this->_forwardRepayment && $this->_overdueAmount > 0) {
            $this->_collectDailyOverdue($userId);
        }
        
        $this->_updateMonthlyBalance();
        //Payment Voucher Generation
        $trans = new Transactions();
        $voucherNo =  $trans->getNextVoucherNo($this->_fmcid);
        $fmcCode = $trans->getFmcCode($this->_fmcid);
        $transBy =0;
        $paymentType = $this->_formValues['payment_type'];
        if($paymentType === 'Bank'){
            $transBy = $this->_formValues['bank_id'];
        }
        $deductionQuery = "select code from  " . $this->_deductionModel->getTableQuoted()." where id = {$this->_deductionTypeId}";
        $deductionTypeCode = $this->_deductionModel->queryRows($deductionQuery);
        $code = $deductionTypeCode[0]->code;
        //Get Head ID
        $headQuery = "select id from  " . $this->_rpHeadModel->getTableQuoted()." where code = 'exp_{$code}' and fmc_id = {$this->_fmcid}";
        $heads = $this->_rpHeadModel->queryRows($headQuery);
        $headID = $heads[0]->id;
        //$amount = $this->_advanceAmount + $this->_overdueAmount;
        $amount = $this->_advanceAmount;
        $now = DateHelper::timestamp();
        $insert = [
            'transNo' => $voucherNo,
            'transDate' => $this->_advanceDate,
            'member_id' => $this->_memberId,
            'amount'  => $amount,
            'headID' => $headID,
            'particulars' => $this->_formValues['description'],
            'code_no' =>  $fmcCode,
            'page_no' =>  1,
            'created_by' => $userId,
            'fmc_id' => $this->_fmcid,
            'transBy' => $transBy,
            'transType' => 'P',
            'created_at' => $now,
            'updated_at' => $now,            
        ];
        $transModel->insert($insert);
        
        return $this->_advanceId;
    }
    
     public function recalc($userId)
    {
         $now = DateHelper::timestamp();
       // $deductionTypeId = (int) $this->_formValues['deduction_type_id'];
        $advanceDate = DateHelper::fromDatepicker($this->_formValues['advance_date']);
       // $account = new DeductionRecalcSupport($deductionTypeId, $advanceDate);
       // $account->created();
        $this->_sql->clear();
        $this->_sql->whereGreater('deduction_date', $advanceDate);
        $where = $this->_sql->getWhere();
        if ($this->_deductionMemberDailyModel->selectTotal($where) > 0) {
            $query1 = 'UPDATE `' . $this->_deductionMemberDailyModel->getTable()
                    . '` SET `origin_member_id` = `member_id`, '
                    . '`member_id` = 9999, `advance_reset_date`= '. $now
                    . ' WHERE ' . $where .' and origin_member_id <= 0 ';
            $this->_deductionMemberDailyModel->exec($query1);
        }
        if ($this->_deductionMemberMonthlyModel->selectTotal($where) > 0) {
            $query1 = 'UPDATE `' . $this->_deductionMemberMonthlyModel->getTable()
                    . '` SET `origin_member_id` = `member_id`, '
                    . '`member_id` = 9999, `advance_reset_date`= '. $now
                    . ' WHERE ' . $where .' and origin_member_id  <= 0 ';
            $this->_deductionMemberMonthlyModel->exec($query1);
        }
        
    }
    private function _collectDailyOverdue($userId)
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $this->_deductionTypeId)
                   ->whereGreater('deduction_date', $this->_entry->deduction_date);
        $where = $this->_sql->getWhere();
        if (0 == $this->_deductionMemberDailyModel->selectTotal($where)) {
            return;
        }
        
        $advanceModel = $this->getModel();
        $repaymentModel = new AdvanceWithdrawalRepayment();
        $overdue = $this->_overdueAmount;
        $repaid = 0;
        $balance = $this->_advanceAmount;
        $odBalance = $this->_overdueAmount;
        
        $paymentDefault = [
            'advance_withdrawal_id' => $this->_advanceId,
            'amount' => '',
            'payment_date' => DateHelper::today(),
            'overdue_repayment' => '',
            'deduction_bill_date' => '',
            'adjusted' => 1,
            'note' => 'Overdue collection auto adjusted when created',
            'deduction_bill_adjusted' => 1,
            'created_at' => DateHelper::timestamp(),
            'created_by' => $userId,
        ];
        
        $insert = $repaymentModel->getInsertPreparedStatement(array_keys($paymentDefault));
        
        $list = $this->_deductionMemberDailyModel->selectIds($where, 'deduction_date');
        foreach ($list as $id) {
            $item = $this->_deductionMemberDailyModel->find($id);
            if ($item->closing_balance > 0) {
                $available = $item->deduction_amount - $item->repayment;
                if ($available > 0) {
                    if ($available >= $overdue) {
                        $collected = $overdue;
                    } else {
                        $collected = $available;
                    }

                    $overdue -= $collected;
                    $payment = $paymentDefault;
                    $payment['amount'] = $collected;
                    $payment['overdue_repayment'] = $collected;
                    $payment['deduction_bill_date'] = $item->deduction_date;
                    $insert->execute(array_values($payment));

                    $repaid = $repaid + $collected;
                    $balance -= $collected;
                    $odBalance -= $collected;
                    $advanceUpdate = [
                        'repaid' => $repaid ,
                        'balance' => $balance,
                        'overdue_balance' => $odBalance,
                    ];

                    if (0 == $odBalance) {
                        $advanceUpdate['closed'] = 1;
                        $advanceUpdate['closing_date'] = $item->deduction_date;
                    }
                    $advanceModel->updateById($advanceUpdate, $this->_advanceId);

                    $update = [
                        'repayment' => $item->repayment + $collected,
                        'closing_balance' => $item->closing_balance - $collected,
                        'adjustment' => $item->adjustment - $collected,
                    ];
                    $this->_deductionMemberDailyModel->updateById($update, $item->id);
                    
                    $this->_sql->clear();
                    $this->_sql->whereId('member_id', $this->_memberId)
                               ->whereId('deduction_type_id', $this->_deductionTypeId)
                               ->whereGreater('deduction_date', $item->deduction_date);
                    $where2 = $this->_sql->getWhere();
                    if (0 == $this->_deductionMemberDailyModel->selectTotal($where2)) {
                        break;
                    }
                    
                    $query = 'UPDATE `' . $this->_deductionMemberDailyModel->getTable()
                           . '` SET `opening_balance` = `opening_balance` - ' . $collected . ', '
                           . '`closing_balance` = `closing_balance` - ' . $collected 
                           . ' WHERE ' . $where2;
                    $this->_deductionMemberDailyModel->exec($query);
                    if (0 == $odBalance) {
                        break;
                    }
                }
            }
        }
    }
    
    private function _updateMonthlyBalance()
    {
        $dates = $this->_getDates($this->_entry->deduction_date);
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $this->_deductionTypeId)
                   ->whereBetween('deduction_date', $dates[0], $dates[1]);
        $where1 = $this->_sql->getWhere();
        
        $query1 = 'SELECT SUM(`repayment`) AS `repayment`, SUM(`adjustment`) AS `adjustment` '
                . 'FROM `' . $this->_deductionMemberDailyModel->getTable() . '` '
                . 'WHERE ' . $where1;
        $sum = $this->_deductionMemberDailyModel->queryRow($query1);
        $update = [
            'repayment' => $sum->repayment,
            'adjustment' => $sum->adjustment,
        ];
        
        $last = $this->_deductionMemberDailyModel->selectColumn('closing_balance', $where1, 'deduction_date DESC');
        $update['closing_balance'] = $last;
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $this->_deductionTypeId)
                   ->whereEqual('deduction_date', $dates[1]);
        $where2 = $this->_sql->getWhere();
        
        $this->_deductionMemberMonthlyModel->update($update, $where2, 1);
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereId('deduction_type_id', $this->_deductionTypeId)
                   ->whereGreater('deduction_date', $dates[1]);
        $where3 = $this->_sql->getWhere();
        if ($this->_deductionMemberMonthlyModel->selectTotal($where3) > 0) {
            $list = $this->_deductionMemberMonthlyModel->selectPairs(['id', 'deduction_date'], $where3, 'deduction_date');
            foreach ($list as $key => $item) {
                $month = $this->_getDates($item);
                $this->_sql->clear();
                $this->_sql->whereId('member_id', $this->_memberId)
                           ->whereId('deduction_type_id', $this->_deductionTypeId)
                           ->whereBetween('deduction_date', $month[0], $month[1]);
                $where4 = $this->_sql->getWhere();

                $query4 = 'SELECT SUM(`repayment`) AS `repayment`, SUM(`adjustment`) AS `adjustment` '
                        . 'FROM `' . $this->_deductionMemberDailyModel->getTable() . '` '
                        . 'WHERE ' . $where4;
                $sum4 = $this->_deductionMemberDailyModel->queryRow($query4);
                $update4 = [
                    'repayment' => $sum4->repayment,
                    'adjustment' => $sum4->adjustment,
                ];
                $update4['opening_balance'] = $this->_deductionMemberDailyModel->selectColumn('opening_balance', $where4, 'deduction_date');
                $update4['opening_balance'] = $this->_deductionMemberDailyModel->selectColumn('closing_balance', $where4, 'deduction_date DESC ');
                $this->_deductionMemberMonthlyModel->updateById($update4, $key);
            }
        }
    }
   
    private function _getMonthlyDate($date)
    {
        $dateTime = DateHelper::getDateTime($date);
        $year = $dateTime->format('Y');
        $month = $dateTime->format('n');
        $days = $dateTime->format('t');
        
        $dateTime->setDate($year, $month, $days);
        
        return $dateTime->format(DateHelper::MYSQL_DATE);
    }
    
    /**
     * validate entry
     * 
     * @param string $value
     * @return boolean
     */
    private function _validateEntry($value)
    {
        if ($this->validator->isNull($value)) {
            return false;
        }
        
        if ('0' == $value) {
            return false;
        }
        
        return $this->validator->isPositiveNumber($value);
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
    
}
