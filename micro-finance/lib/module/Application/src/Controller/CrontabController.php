<?php
/**
 * Application Crontab Controller
 * 
 * @category Application
 * @package  Application\Controller
 */

namespace Application\Controller;

use Application\ApplicationController;
use Application\Utility\LoanHelper;

use Web\Utility\DateHelper;


class CrontabController extends ApplicationController
{
    public function __construct()
    {
        parent::__construct();
        $this->layout->setLayout('');
        if ('123459' != $this->getParamId()) {
            exit;
        }
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        exit;
    }
    
    /**
     * overdue action
     * 
     * @return \Web\View\ViewModel
     */
    public function overdueAction()
    {
        $model = new \Model\Loan();
        $sql = $model->getSql();
        $sql->whereOne('closed')
            ->whereId('loan_status_id', LoanHelper::STATUS_DELINQUENT);
        $loans = $model->select($sql->getWhere(), 'id');
        if (empty($loans)) {
            die('No Loans');
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
        
        $pqamModel = new \Model\Pqam();
        $insert = $pqamModel->getInsertPreparedStatement(array_keys($default));

        try {
            $model->getAdapter()->beginTransaction();
            foreach ($loans as $loan) {
                $sql->clear();
                $sql->whereId('loan_id', $loan->id)
                    ->whereEqual('pqam_date', $reportingDate);
                if (0 == $pqamModel->selectTotal($sql->getWhere())) {
                    $sql->clear();
                    $sql->whereId('loan_id', $loan->id);
                    $last = $pqamModel->selectRow($sql->getWhere(), 'pqam_date DESC');                    
                    $dates = LoanHelper::overdueDates($last->pqam_date, $reportingDate);
                    if (! empty($dates)) {
                        $outstanding = $last->actual_outstanding;
                        $instalments = $last->instalment;
                        foreach ($dates as $item) {
                            $date = $item['date'];
                            $record = $default;
                            $record['pqam_date'] = $date;
                            $record['member_id'] = $loan->member_id;
                            $record['loan_id'] = $loan->id;
                            $record['instalment'] = $instalments;

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
        
        echo 'done';
        exit;
    }
    
    
}
