<?php
/**
 * ControlCenter Monthly Deduction controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;

use Model\{DeductionMemberDaily, DeductionMemberMonthly};


class MonthlyDeductionController extends ControlCenterController
{
    /**
     * 
     * @var \Model\DeductionMemberMonthly
     */
    private $_model;
    
    /**
     * 
     * @var \Model\DeductionMemberDaily
     */
    private $_dailyModel;
    
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
        $this->_model = new DeductionMemberMonthly();
        $this->_dailyModel = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
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
     * run action
     * 
     * @return \Web\View\ViewModel
     */
    public function runAction()
    {
        exit;
        $this->start('ControlCenter / Monthly Deduction / run');
        $total = $this->_model->selectTotal();
        $this->_printLine('TOTAL: ' . $total);
        if ($total > 0) {
            $offset = 47000;
            $limit = 1000;
            $this->_printLine('Offset: ' . $offset . ' limit: ' . $limit);
            $list = $this->_model->select(null, 'id', $offset . ',' . $limit);
            if (! empty($list)) {
                $query1 = 'SELECT SUM(`deduction_amount`) AS `deduction_amount` '
                        . 'FROM `' . $this->_dailyModel->getTable() . '` '
                        . 'WHERE `member_id` =? AND `deduction_type_id` =? AND `deduction_date` BETWEEN ? AND ?';
                $amount = $this->_model->prepare($query1);

                $query2 = 'SELECT `opening_balance` '
                        . 'FROM `' . $this->_dailyModel->getTable() . '` '
                        . 'WHERE `member_id` =? AND `deduction_type_id` =? AND `deduction_date` BETWEEN ? AND ? ORDER BY `deduction_date` LIMIT 1';
                $opening = $this->_model->prepare($query2);

                $query3 = 'SELECT `closing_balance` '
                        . 'FROM `' . $this->_dailyModel->getTable() . '` '
                        . 'WHERE `member_id` =? AND `deduction_type_id` =? AND `deduction_date` BETWEEN ? AND ? ORDER BY `deduction_date` DESC LIMIT 1';
                $closing = $this->_model->prepare($query3);

                $query4 = 'UPDATE `' . $this->_model->getTable() . '` SET '
                        . '`opening_balance` =?, `deduction_amount` =?, `closing_balance` =?  '
                        . 'WHERE `id` =? LIMIT 1';

                $update = $this->_model->prepare($query4);

                try {
                    $this->_model->getAdapter()->beginTransaction();
                    foreach ($list as $item) {
                        $dates = $this->_getDates($item->deduction_date);
                        $params = [$item->member_id, $item->deduction_type_id, $dates[0], $dates[1]];
                        
                        $this->_printLine($item->deduction_date . ': ' . $item->member_id . ' : ' . $item->deduction_type_id);
                        $amount->execute($params);
                        $deduction = $amount->fetchColumn();
                        $amount->closeCursor();
                        
                        $opening->execute($params);
                        $openingBalance = $opening->fetchColumn();
                        $opening->closeCursor();
                        
                        $closing->execute($params);
                        $closingBalance = $closing->fetchColumn();
                        $closing->closeCursor();
                        $update->execute([$openingBalance, $deduction, $closingBalance, $item->id]);
                    }
                    $this->_model->getAdapter()->commit();
                } catch (\PDOException $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                } catch (\Web\Exception $ex) {
                    $this->_model->getAdapter()->rollBack();
                    echo $ex->getMessage();
                    echo $ex->getTraceAsString();
                }
            }
        }
        
        $this->_printLine('Completed');
        $this->finish();
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
