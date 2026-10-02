<?php
/**
 * ControlCenter AccountStatus controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;

use Application\Utility\{DcbCreator, LoanStore, PortfolioBuilder, EmiCalculator};

use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;


class DcbController extends ControlCenterController
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
        echo 'in';
        $this->stop();
    }
    
    /**
     * pqam action
     * 
     * @return \Web\View\ViewModel
     */
    public function pqamAction()
    {
        $this->start('ControlCenter / Dcb / pqam');
        $model = new \Model\Loan();
        $builder = new PortfolioBuilder();
        try {
            $model->getAdapter()->beginTransaction();
            $builder->rebuild();
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
     * ageing action
     * 
     * @return \Web\View\ViewModel
     */
    public function ageingAction()
    {
        $this->start('ControlCenter / Dcb / ageing');
        $model = new \Model\Pqam();
        if (0 == $model->selectTotal()) {
            $this->_printLine('No PQAM');
            $this->finish();
        }
        
        $loanModel = new \Model\Loan();
        $loans = $loanModel->selectPairs(['id', 'emi']);
        try {
            $model->getAdapter()->beginTransaction();
            $update = $model->getUpdatePreparedStatement(['ageing']);
            $list = $model->select(null, 'id');
            foreach ($list as $item) {
                $ageing = 0;
                if ($item->overdue > 0.00) {
                    $ageing = ceil(($item->overdue / $loans[$item->loan_id]) * 30);
                }
                
                $this->_printLine($item->id . ', ' . $item->loan_id . ', ' . $item->ageing . ' --- ' . $ageing);
                if ($ageing != $item->ageing) {
                    $update->execute([$ageing, $item->id]);
                    $this->_printLine('Updated');
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
     * schedule action
     * 
     * @return \Web\View\ViewModel
     */
    public function scheduleAction()
    {
        $this->start('ControlCenter / Dcb / schedule');
        $model = new \Model\Schedule();
        if (0 == $model->selectTotal()) {
            $this->_printLine('No Schedules');
            $this->finish();
        }
        
        try {
            $model->getAdapter()->beginTransaction();
            $model->update(['first_instalment' => 1, 'broken_period' => 1], ['instalment' => 0]);
            $model->update(['first_emi' => 1], ['instalment' => 1]);
            $query = 'SELECT MAX(`id`) AS `schedule_id` FROM `' . $model->getTable() . '` '
                    . 'GROUP BY `loan_id`';
            $ids = $model->queryColumns($query);
            $model->updateByIds(['last_instalment' => 1], $ids);
            $loanIds = $model->selectColumns('loan_id', ['instalment' => 0]);
            $this->out($loanIds);
            if (! empty($loanIds)) {
                $loanModel = new \Model\Loan();
                $loanModel->updateByIds(['has_broken_period' => 1], $loanIds);
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
    
    public function brokenPeriodAction()
    {
        $model = new \Model\Loan();
        $sql = $model->getSql();
        $sql->where('DAY(`loan_date`) > 27 AND `closed` = 1');
        $where = $sql->getWhere();
        if (0 == $model->selectTotal($where)) {
            $this->_printLine('No loans');
            $this->finish();
        }
        
        $scheduleModel = new \Model\Schedule();
        $loanIds = [];
        $list = $model->select($where);
        foreach ($list as $item) {
            if ($this->_isLastdDate($item->loan_date)) {
                $sql->clear();
                $sql->whereId('loan_id', $item->id)
                    ->whereZero('instalment');
                if ($scheduleModel->selectTotal($sql->getWhere()) > 0) {
                    $loanIds[] = $item->id;
                }
            }
        }
        
        if (empty($loanIds)) {
            $this->_printLine('No reschedule loans');
            $this->finish();
        }
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($loanIds as $loanId) {
                $loan = $model->find($loanId);
                $sql = $model->getSql();
                $sql->whereId('loan_id', $loanId)
                    ->whereOne('has_dcb_entry');
                $where = $sql->getWhere();
                $original = [];
                $brokenPeriod = null;
                $dates = [];
                $entries = [];
                if ($scheduleModel->selectTotal($where) > 0) {
                    $original = $scheduleModel->select($where, 'instalment');
                    $first = $original[0];
                    if (0 == $first->instalment) {
                        $brokenPeriod = $first;
                        $dates[] = $first->instalment_date;
                        unset($original[0]);
                    }

                    foreach ($original as $item) {
                        $dates[] = $item->instalment_date;
                        $entries[$item->instalment] = ['repayment' => $item->collection_repaid, 'catch' => $item->catch_amount];
                        if (1 == $item->instalment) {
                            if (null !== $brokenPeriod) {
                                $entries[$item->instalment]['repayment'] += $brokenPeriod->collection_repaid;
                                $entries[$item->instalment]['catch'] += $brokenPeriod->catch_amount;
                            }
                        }
                    }
                }


                $sql->clear();
                $sql->whereId('loan_id', $loanId);
                $whereLoan = $sql->getWhere();
                $scheduleModel->delete($whereLoan);
                $pqamModel = new \Model\Pqam();
                if ($pqamModel->selectTotal($whereLoan) > 0) {
                    $pqamModel->delete($whereLoan);
                }

                $calculator = new EmiCalculator();
                $calculator->amount($loan->loan_amount)
                           ->interest($loan->interest_rate)
                           ->loanDate($loan->loan_date)
                           ->tenure($loan->tenure)
                           ->emi($loan->emi);
                $schedule = $calculator->run();
                $updateLoan = [
                    'start_date' => $schedule['start_date'],
                    'end_date' => $schedule['end_date'],
                    'emi' => $schedule['emi'],
                    'total_interest_amount' => $schedule['total_interest'],
                    'total_repayment_demand' => $schedule['total_repayment'],
                    'total_emi_amount' => $schedule['total_emi'],
                    'total_principal_amount' => $schedule['total_principal'],
                    'closed' => 0,
                ];

                $model->updateById($updateLoan, $loanId);

                $default = [
                    'loan_id' => $loanId,
                    'instalment' => 0,
                    'emi' => '0.00',
                    'instalment_date' => '',
                    'interest_component' => '',
                    'principal_component' => '',
                    'opening_principal' => '',
                    'closing_principal' => '',
                    'emi_days' => 0,
                    'cumulative_interest' => '',
                    'cumulative_principal' => '',
                    'cumulative_emi' => '',
                    'first_instalment' => 0,
                    'last_instalment' => 0,
                    'broken_period' => 0,
                    'first_emi' => 0,
                    'created_at' => DateHelper::timestamp(),
                ];

                $insert = $scheduleModel->getInsertPreparedStatement(array_keys($default));
                foreach ($schedule['schedule'] as $item) {
                    $record = $default;
                    foreach ($item as $field => $value) {
                        $record[$field] = $value;
                    }
                    $insert->execute(array_values($record));
                }

                if (! empty($entries)) {
                    foreach ($entries as $values) {
                        $store = new LoanStore($loanId);
                        $creator = new DcbCreator($store);
                        $creator->repayment($values['repayment'])
                                ->catchAmount($values['catch']);
                        $creator->run();
                    }
                }

                $builder = new PortfolioBuilder();
                $builder->rebuildPeriod($loan->member_id, $dates);
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
     * pqam principal action
     * 
     * @return \Web\View\ViewModel
     */
    public function pqamPrincipalAction()
    {
        $this->start('ControlCenter / Dcb / pqam');
        $summaryModel = new \Model\Summary();
        $scheduleModel = new \Model\Schedule();
        $query = 'SELECT `s1`.`schedule_id`, `s1`.`collection_principal_arrears`, `s1`.`collection_current_principal`, '
                . '`s1`.`collection_prepayment`, `s2`.`loan_id`, `s2`.`instalment_date` '
                . ' FROM ' . $summaryModel->getTableQuoted() . ' `s1` '
                . 'INNER JOIN ' . $scheduleModel->getTableQuoted() . ' `s2` ON `s2`.`id` = `s1`.`schedule_id`';
        $summaryList = $summaryModel->queryRows($query);
        $sql = $summaryModel->getSql();
        
        $default = [
            'principal_arrears' => 0.00,
            'current_principal' => 0.00,
            'prepayment' => 0.00,
            'principal_prepaid_current' => 0.00,
        ];
        
        $model = new \Model\Pqam();

        try {
            $model->getAdapter()->beginTransaction();
            foreach ($summaryList as $summary) {
                $update = $default;
                $update['principal_arrears'] = $summary->collection_principal_arrears;
                $update['current_principal'] = $summary->collection_current_principal;
                $update['prepayment'] = $summary->collection_prepayment;
                $update['principal_prepaid_current'] = $summary->collection_prepayment + $summary->collection_current_principal;
                
                $sql->clear();
                $sql->whereId('loan_id', $summary->loan_id)
                    ->whereEqual('pqam_date', $summary->instalment_date);
                $model->update($update, $sql->getWhere(), 1);
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
     * timeline action
     * 
     * @return \Web\View\ViewModel
     */
    public function timelineAction()
    {
        $this->start('ControlCenter / Dcb / Note');
        $model = new \Model\Loan();
        if (0 == $model->selectTotal()) {
            $this->_printLine('No Loans');
            $this->finish();
        }
        $list = $model->selectPairs(['id', 'created_at']);
        $timelineModel = new \Model\LoanTimeline();
        $description = 'Loan A/C %d created';
        $default = [
            'loan_id' => 0,
            'administrator_id' => 1,
            'title' => 'Created',
            'description' => '',
            'created_at' => '',
        ];
        
        $insert = $timelineModel->getInsertPreparedStatement(array_keys($default));
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($list as $id => $created) {
                $record = $default;
                $record['loan_id'] = $id;
                $record['description'] = sprintf($description, $id);
                $record['created_at'] = $created;
                $insert->execute(array_values($record));
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
     * schedule ageing action
     * 
     * @return \Web\View\ViewModel
     */
    public function scheduleAgeingAction()
    {
        $this->start('ControlCenter / Dcb / schedule-ageing');
        $model = new \Model\Schedule();
        $sql = $model->getSql();
        $sql->whereGreater('ageing_factor', '0.0');
        $where = $sql->getWhere();
        if (0 == $model->selectTotal($where)) {
            $this->_printLine('No Schedules');
            $this->finish();
        }
        
        $list = $model->selectFields(['id', 'ageing_factor', 'ageing_days'], $where, 'id');
        try {
            $model->getAdapter()->beginTransaction();
            $update = $model->getUpdatePreparedStatement(['ageing_days']);
            foreach ($list as $item) {
                $this->_printLine($item->id . ', ' . $item->ageing_factor . ', ' . $item->ageing_days);
                if ($item->ageing_factor > 0.0) {
                    $ageing = ceil($item->ageing_factor * 30);
                    if ($ageing != $item->ageing_days) {
                        $update->execute([$ageing, $item->id]);
                        $this->_printLine('Updated to ' . $ageing);
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
     * delinquent action
     * 
     * @return \Web\View\ViewModel
     */
    public function delinquentAction()
    {
        $this->start('ControlCenter / Dcb / delinquent');
        $model = new \Model\Loan();
        $scheduleModel = new \Model\Schedule();
        $sql = $model->getSql();
        $sql->whereOne('closed')
            ->whereZero('loan_status_id');
        $where = $sql->getWhere();
        if (0 == $model->selectTotal($where)) {
            $this->_printLine('No loans');
            $this->finish();
        }
        
        
        $list = $model->selectIds($where, 'id');
        try {
            $model->getAdapter()->beginTransaction();
            $update = $model->getUpdatePreparedStatement(['loan_status_id']);
            foreach ($list as $item) {
                $this->_printLine($item);
                $sql->clear();
                $sql->whereId('loan_id', $item)
                    ->whereOne('has_dcb_entry');
                $last = $scheduleModel->selectRow($sql->getWhere(), 'id DESC');
                if (! empty($last)) {
                    if ($last->balance_actual_principal_interest_outstanding > 0.00) {
                        $update->execute([4, $item]);
                        $this->_printLine('Updated');
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
        
    private function _isLastdDate($date)
    {
        $dateTime = DateHelper::getDateTime($date);
        
        return $dateTime->format('j') == $dateTime->format('t');
    }
    
}
