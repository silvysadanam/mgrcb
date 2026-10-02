<?php
/**
 * ControlCenter DeductionDelete controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;

use Model\{AuctionChit, AuctionDaily};


class AuctionDailyController extends ControlCenterController
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AuctionDaily
     */
    private $_dailyModel;
    
    /**
     * 
     * @var \stdclass
     */
    private $_auctionChit;
    
    private $_daily;
    
    private $_fmc;
    
    private $_date;
    
    private $_deductions;
    
    private $_forwardUpdate;
    
    private $_single = true;
    
    private $_monthlyDate;
    
    private $_year;
    
    private $_month;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    public function __construct()
    {
        parent::__construct();
        $this->authenticationRequired();
        $this->_model = new AuctionChit();
        $this->_dailyModel = new AuctionDaily();
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
        $this->start('ControlCenter / Auction Daily / run');
        exit;
       
        for ($id = 1200; $id < 1500; $id++) {
        $total = $this->_model->selectTotalByIdColumn('member_id', $id);
        $this->_printLine('Member ID: ' . $id . ' total: ' . $total);
        if ($total > 0) {
            
        
        $query1 = 'SELECT DISTINCT `auction_date`, COUNT(`id`) AS `entries`, '
                . 'SUM(`amount`) AS `amount`, SUM(`quantity`) AS `quantity`, '
                . 'SUM(`advance`) AS `advance`, SUM(`balance`) AS `balance`, '
                . 'SUM(`member_paid_amount`) AS `member_paid`, SUM(`deduction_amount`) AS `deduction_amount`, '
                . 'SUM(`deduction_balance`) AS `deduction_balance`, SUM(`has_deduction_bill`) AS `bills` '
                . 'FROM `' . $this->_model->getTable() . '` WHERE `member_id` =' . $id
                . ' GROUP BY `auction_date`';
        $list = $this->_model->queryRows($query1);
        
        $query2 = 'UPDATE `' . $this->_dailyModel->getTable() . '` SET '
                . '`entries` =?, `amount` =?, `quantity` =?, `advance` =?, `balance` =?,'
                . '`member_paid` =?,`deduction_amount` =?, `deduction_balance` =? '
                . 'WHERE `member_id` =? AND `auction_date` =? LIMIT 1';
        
        $update = $this->_model->prepare($query2);
        
        try {
            $this->_model->getAdapter()->beginTransaction();
            foreach ($list as $item) {
                $this->_printLine($item->auction_date . ': ' . $item->entries . ' : ' . $item->bills);
                if ($item->entries == 1 && $item->bills > 0) {
                    $daily = [
                        $item->entries, $item->amount, $item->quantity,
                        $item->advance, $item->balance, $item->member_paid,
                        $item->deduction_amount, $item->deduction_balance,
                        $id, $item->auction_date, 
                    ];
                    $update->execute($daily);
                    //$this->out($daily);
                }
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
    
}
