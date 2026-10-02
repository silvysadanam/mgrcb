<?php
/**
 * ControlCenter Loan controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use Application\Utility\LoanHelper;

use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;


class LoanController extends ControlCenterController
{
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->stop();
    }
    
    /**
     * pqam action
     * 
     * @return \Web\View\ViewModel
     */
    public function summaryAction()
    {
        $this->start('ControlCenter / Loan / summary');
        $model = new \Model\Loan();
        $loans = $model->select(null, 'id');
        if (empty($loans)) {
            $this->_printLine('No loans');
            $this->finish();
        }
        
        $sql = $model->getSql();
        $scheduleModel = new \Model\Schedule();
        $summaryModel = new \Model\Summary();
        $loanSummaryModel = new \Model\LoanSummary();
        $rescheduleModel = new \Model\LoanReschedule();
         
        $direct = [
            'scheduled_demand_loan_outstanding', 'collection_penal_interest',
            'collection_interest_arrears', 'collection_current_interest', 
            'collection_principal_arrears', 'collection_current_principal', 
            'collection_prepayment', 'collection_repaid',
            'balance_interest_overdue', 'balance_principal_overdue', 'balance_total_overdue',
            'balance_scheduled_principal_outstanding', 'balance_actual_principal_outstanding',
            'balance_actual_principal_interest_outstanding', 
        ];
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($loans as $loan) {
                if (! $loanSummaryModel->checkByColumnId('loan_id', $loan->id)) {
                    $intalments = $loan->tenure;
                    if ($loan->has_broken_period > 0) {
                        ++$intalments;
                    }
                    $insert = [
                        'loan_id' => $loan->id,
                        'instalments' => $intalments,
                        'scheduled_demand_loan_outstanding' => $loan->loan_amount,
                        'as_on_date' => DateHelper::fromDatepicker($loan->loan_date),
                        'created_at' => DateHelper::timestamp(),
                        'modified_at' => DateHelper::timestamp(),
                    ];
                    
                    $sql->clear();
                    $sql->whereId('loan_id', $loan->id)
                        ->whereOne('has_dcb_entry');
                    $where = $sql->getWhere();
                    if ($scheduleModel->selectTotal($where) > 0) {
                        $schedule = $scheduleModel->selectRow($where, 'id DESC');
                        $summary = $summaryModel->selectRow(['schedule_id' => $schedule->id]);
                        if (! empty($summary)) {
                            foreach ($direct as $field) {
                                $insert[$field] = $summary->{$field};
                            }
                        }
                        $insert['paid_tenures'] = $schedule->instalment;
                        $insert['has_dcb'] = 1;
                        $insert['as_on_date'] = $schedule->instalment_date;
                        $insert['ageing'] = $summary->ageing_days;
                        if ($summary->ageing_days > 0) {
                            $insert['par'] = $summary->balance_actual_principal_interest_outstanding;
                        }
                    }
                    
                    if ($loan->loan_status_id > 0) {
                        if (LoanHelper::STATUS_WRITTEN_OFF == $loan->loan_status_id) {
                            $insert['written_off'] = 1;
                            $insert['write_off_amount'] = $summary->balance_actual_principal_interest_outstanding;
                            $insert['write_off_date'] = $insert['as_on_date'];
                        } elseif (LoanHelper::STATUS_REFINANCED == $loan->loan_status_id || LoanHelper::STATUS_RESCHEDULED == $loan->loan_status_id) {
                            $insert['refinanced'] = 1;
                            $insert['refinance_type'] = $loan->loan_status_id;
                            $insert['refinanced_date'] = $insert['as_on_date'];
                            if ($rescheduleModel->checkByColumnId('source_loan_id', $loan->id)) {
                                $rescheduled = $rescheduleModel->selectByIdColumn('source_loan_id', $loan->id);
                                $insert['refinanced_to'] = $rescheduled->new_loan_id;
                            }
                        }
                    }
                    
                    if ($rescheduleModel->checkByColumnId('new_loan_id', $loan->id)) {
                        $rescheduledFrom = $rescheduleModel->selectByIdColumn('new_loan_id', $loan->id);
                        $insert['refinanced_from'] = $rescheduledFrom->source_loan_id;
                        $model->updateById(['rescheduled_or_refinanced' => 1], $loan->id);
                    }
     
                    // `ageing`, `par`, `as_on_date`, `written_off`, `write_off_amount`, `write_off_date`, `written_off_comments`, 
                    // `member_paid_amount`, `member_paid_receipt_number`, `member_paid_date`, `fmc_paid_amount`, `fmc_paid_voucher_number`, `fmc_paid_date`,
                    //  `refinanced`, `refinanced_loan`, `refinanced_to`, `refinanced_from`, `refinance_type`, `refinanced_date`, `created_at`, `modified_at`
                    
                    $loanSummaryModel->insert($insert);
                }
            }
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    public function memberCatchAction()
    {
        $this->start('ControlCenter / Loan / member catch');
        
        $pqacModel = new \Model\Pqac();
        if (0 == $pqacModel->selectTotal()) {
            $this->_printLine('No pqac');
            $this->finish();
        }
        
        $model = new \Model\MemberCatch();
        $default = ['member_id' => 0, 'catch_date' => '', 'amount' => 0];
        $insert = $model->getInsertPreparedStatement(array_keys($default));
        $list = $pqacModel->select(null, 'pqam_date');
        $sql = $model->getSql();
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($list as $item) {
                $sql->clear();
                $sql->whereId('member_id', $item->member_id)
                    ->whereEqual('catch_date', $item->pqam_date);
                $where = $sql->getWhere();
                if ($model->selectTotal($where) > 0) {
                    $catch = $model->selectRow($where);
                    if ($catch->amount != $item->catch_amount) {
                        $model->updateById(['amount' => $item->catch_amount], $catch->id);
                    }
                } else {
                    $record = $default;
                    $record['member_id'] = $item->member_id;
                    $record['catch_date'] = $item->pqam_date;
                    $record['amount'] = $item->catch_amount;
                    $insert->execute(array_values($record));
                }
            }
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    /**
     * pqam action
     * 
     * @return \Web\View\ViewModel
     */
    public function overdueAction()
    {
        $this->start('ControlCenter / Loan / overdue');
        $model = new \Model\Loan();
        $sql = $model->getSql();
        $sql->whereOne('closed')
            ->whereGreater('loan_status_id', 0);
        $loans = $model->select($sql->getWhere(), 'id');
        if (empty($loans)) {
            $this->_printLine('No loans');
            $this->finish();
        }
        
        $reportingDate = LoanHelper::reportingDate();
        
        $default = [
            'pqam_date' => '',
            'member_id' => '',
            'loan_id' => '',
            'instalment' => '',
            'actual_outstanding' => 0,
            'overdue' => 0,
            'ageing' => 0,
            'par' => 0,
            'created_at' => DateHelper::timestamp(),
        ];
        
        $loanSummaryModel = new \Model\LoanSummary();
        $pqamModel = new \Model\Pqam();
        $insert = $pqamModel->getInsertPreparedStatement(array_keys($default));

        try {
            $model->getAdapter()->beginTransaction();
            foreach ($loans as $loan) {
                $summary = $loanSummaryModel->selectRow(['loan_id' => $loan->id]);
                $start = $loan->end_date;
                $end = $reportingDate;
                if (LoanHelper::STATUS_WRITTEN_OFF == $loan->loan_status_id) {
                    $end = LoanHelper::previousDay($summary->write_off_date);
                } elseif (in_array($loan->loan_status_id, [LoanHelper::STATUS_RESCHEDULED, LoanHelper::STATUS_REFINANCED])) {
                    $end = LoanHelper::previousDay($summary->refinanced_date);
                }
                
                $outstanding = $summary->balance_actual_principal_interest_outstanding;
                $dates = LoanHelper::overdueDates($start, $end);
                
                $sql->clear();
                $sql->whereId('loan_id', $loan->id)
                    ->whereGreater('pqam_date', $start);
                $wherePqam = $sql->getWhere();
                if ($pqamModel->selectTotal($wherePqam) > 0) {
                    $pqamModel->delete($wherePqam);
                }
                
                if (! empty($dates)) {
                    foreach ($dates as $item) {
                        $date = $item['date'];
                        $record = $default;
                        $record['pqam_date'] = $date;
                        $record['member_id'] = $loan->member_id;
                        $record['loan_id'] = $loan->id;
                        $record['instalment'] = $summary->instalments;
                        
                        if ($loan->interest_rate > 0.00) {
                            $dailyRate = LoanHelper::dailyInterestRateByDate($loan->interest_rate, $date);
                            $interest = LoanHelper::amount($outstanding * $dailyRate * $item['days']);
                            $outstanding += $interest;
                        }
                        
                        $record['actual_outstanding'] = $outstanding;
                        $record['overdue'] = $outstanding;
                        $record['par'] = $outstanding;
                        $ageing = ceil(($outstanding / $loan->emi) * 30);
                        if ($ageing > 0) {
                            $record['ageing'] = $ageing;
                        }
                        $insert->execute(array_values($record));
                    }
                }
            }
            
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    /**
     * principal action
     * 
     * @return \Web\View\ViewModel
     */
    public function principalAction()
    {
        $this->start('ControlCenter / Loan / pricipal');
        $last = [];
        $model = new \Model\Pqam();
        $list = $model->selectFields(['id', 'loan_id', 'pqam_date'], null, 'pqam_date');
        if (empty($list)) {
            $this->_printLine('No pqam');
            $this->finish();
        }
        
        $update = $model->getUpdatePreparedStatement(['principal_outstanding']);
        $scheduleModel = new \Model\Schedule();
        $sql = $scheduleModel->getSql();
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($list as $item) {
                $sql->clear();
                $sql->whereId('loan_id', $item->loan_id)
                    ->whereEqual('instalment_date', $item->pqam_date);
                $where = $sql->getWhere();
                if ($scheduleModel->selectTotal($where) > 0) {
                    $outstanding = $scheduleModel->selectColumn('balance_actual_principal_outstanding', $where);
                } else {
                    if (isset($last[$item->loan_id])) {
                        $outstanding = $last[$item->loan_id];
                    } else {
                        $sql->clear();
                        $sql->whereId('loan_id', $item->loan_id)
                            ->whereOne('has_dcb_entry');
                        $outstanding = $scheduleModel->selectColumn('balance_actual_principal_outstanding', $where, 'id DESC');
                        $last[$item->loan_id] = $outstanding;
                    }
                }
                
                $update->execute([sprintf('%0.2f', $outstanding), $item->id]);
            }
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
    /**
     * opening principal action
     * 
     * @return \Web\View\ViewModel
     */
    public function openingPrincipalAction()
    {
        $this->start('ControlCenter / Loan / opening pricipal');
        $last = [];
        $model = new \Model\Schedule();
        $sql = $model->getSql();
        $sql->whereOne('has_dcb_entry');
        $loanIds = $model->selectDistinct('loan_id', $sql->getWhere(), 'loan_id');
        $update = $model->getUpdatePreparedStatement(['actual_opening_principal_outstanding']);
        $fields = ['id', 'opening_principal', 'balance_actual_principal_outstanding'];
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($loanIds as $id) {
                $last = 0;
                $sql->clear();
                $sql->whereId('loan_id', $id)
                    ->whereOne('has_dcb_entry');
                $list = $model->selectFields($fields, $sql->getWhere(), 'instalment_date');
                $first = true;
                foreach ($list as $item) {
                    if ($first) {
                        $update->execute([$item->opening_principal, $item->id]);
                        $first = false;
                    } else {
                        $update->execute([$last, $item->id]);
                    }
                    $last = $item->balance_actual_principal_outstanding;
                }
            }
            $model->getAdapter()->commit();
        } catch (\PDOException $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        } catch (\Web\Exception $ex) {
            $model->getAdapter()->rollBack();
            echo $ex->getMessage();
            echo $ex->getTraceAsString();
        }
        
        $this->_printLine('Completed');
        $this->finish();
    }
    
}
