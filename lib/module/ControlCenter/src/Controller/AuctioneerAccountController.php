<?php
/**
 * ControlCenter AuctioneerAccount controller
 * 
 * @category ControlCenter
 * @package  ControlCenter\Controller
 */

namespace ControlCenter\Controller;


use ControlCenter\ControlCenterController;

use Web\Utility\DateHelper;


class AuctioneerAccountController extends ControlCenterController
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
     * initial action
     * 
     * @return \Web\View\ViewModel
     */
    public function initialAction()
    {
        $this->start('ControlCenter / AuctioneerAccount / initital');
        
        $model = new \Model\AuctioneerDaily();
        if (0 == $model->selectTotal()) {
            $this->_printLine('No daily entries');
            $this->finish();
        }
        
        $now = DateHelper::timestamp();
        $list = $model->selectFields(['donor_id', 'auction_date', 'amount', 'advance', 'balance'], null, 'auction_date');
        
        $dailyModel = new \Model\AuctioneerAccountDaily();
        $dailyDefault = [
            'donor_id' => 0, 'payment_date' => '', 'opening_balance' => 0,
            'advance' => 0, 'balance' => 0, 'auction_amount' => 0,
            'amount_paid' => 0, 'closing_balance' => 0, 'updated_at' => $now,
        ];
        $insertDaily = $dailyModel->getInsertPreparedStatement(array_keys($dailyDefault));
        
        $monthlyModel = new \Model\AuctioneerAccountMonthly();
        
        $monthlyDefault = [
            'donor_id' => 0, 'payment_month' => 0, 'payment_year' => 0,
            'opening_balance' => 0, 'advance' => 0, 'balance' => 0, 'auction_amount' => 0,
            'amount_paid' => 0, 'closing_balance' => 0, 'updated_at' => $now,
        ];
        $insertMonthly = $monthlyModel->getInsertPreparedStatement(array_keys($monthlyDefault));

        $closing = [];
        $monthly = [];
        
        try {
            $model->getAdapter()->beginTransaction();
            foreach ($list as $item) {
                $donorId = $item->donor_id;
                if (isset($closing[$donorId])) {
                    $opening = $closing[$donorId];
                } else {
                    $opening = 0;
                }
                
                $dailyRecord = $dailyDefault;
                $dailyRecord['donor_id'] = $donorId;
                $dailyRecord['payment_date'] = $item->auction_date;
                $dailyRecord['opening_balance'] = $opening;
                $dailyRecord['advance'] = $item->advance;
                $dailyRecord['balance'] = $item->balance;
                $dailyRecord['auction_amount'] = $item->amount;
                $dailyRecord['closing_balance'] = $opening + $item->balance;
                $closing[$donorId] = $dailyRecord['closing_balance'];
                $insertDaily->execute(array_values($dailyRecord));
                
                $date = DateHelper::getDateTime($item->auction_date);
                $year = $date->format('Y');
                $month = $date->format('n');
                if (! isset($monthly[$donorId])) {
                    $monthly[$donorId] = [];
                }
                
                if (! isset($monthly[$donorId][$year])) {
                    $monthly[$donorId][$year] = [];
                }
                
                if (! isset($monthly[$donorId][$year][$month])) {
                    $monthly[$donorId][$year][$month] = [
                        'opening_balance' => $opening,
                        'auction_amount' => 0,
                        'advance' => 0,
                        'balance' => 0,
                        'closing_balance' => $opening,
                    ];
                }
                
                $monthly[$donorId][$year][$month]['auction_amount'] += $dailyRecord['auction_amount'];
                $monthly[$donorId][$year][$month]['advance'] += $dailyRecord['advance'];
                $monthly[$donorId][$year][$month]['balance'] += $dailyRecord['balance'];
                $monthly[$donorId][$year][$month]['closing_balance'] = $dailyRecord['closing_balance'];
            }
            
            foreach ($monthly as $id => $years) {
                foreach ($years as $yearId => $months) {
                    foreach ($months as $monthId => $entry) {
                        $monthlyRecord = $monthlyDefault;
                        $monthlyRecord['donor_id'] = $id;
                        $monthlyRecord['payment_month'] = $monthId;
                        $monthlyRecord['payment_year'] = $yearId;
                        foreach ($entry as $field => $value) {
                            $monthlyRecord[$field] = $value;
                        }
                        $insertMonthly->execute(array_values($monthlyRecord));
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
    
}
