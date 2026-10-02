<?php
/**
 * Shared AuctioneerAccount Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{AuctioneerAccountDaily, AuctioneerAccountMonthly, AuctioneerPayment};
use Model\{AuctioneerDaily, InitialOpeningBalance};

use Web\Utility\DateHelper;


class AuctioneerAccount
{
    /**
     * 
     * @var \Model\AuctioneerAccountDaily
     */
    private $_model;
    
     /**
     * 
     * @var \Model\AuctioneerAccountMonthly
     */
    private $_donorAccountMonthlyModel;
    
    /**
     * 
     * @var \Model\AuctioneerDaily
     */
    private $_donorDailyModel;
    
    /**
     * 
     * @var \Model\AuctioneerPayment
     */
    private $_donorPaymentModel;
    
    /**
     * 
     * @var int
     */
    private $_now;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_date;
    
    /**
     * 
     * @var array
     */
    private $_dates = [];
    
    /**
     * 
     * @var int
     */
    private $_donorId;
    
    /**
     * 
     * @var array
     */
    private $_where = [];
    
    private $_previous;
    
    private $_current;
    
    private $_catch;
    
    private $_payment;
    
    private $_months = [];
    
    /**
     * constructor
     */
    public function __construct($donorId)
    {
        $this->_donorId = $donorId;
        $this->_model = new AuctioneerAccountDaily();
        $this->_donorDailyModel = new AuctioneerDaily();
        $this->_donorPaymentModel = new AuctioneerPayment();
        $this->_donorAccountMonthlyModel = new AuctioneerAccountMonthly();
        
        $this->_sql = $this->_model->getSql();
        $this->_now = DateHelper::timestamp();
    }
    
    public function run($date)
    {
        $this->_date = $date;
        
        $this->_sql->whereId('donor_id', $this->_donorId);
        $this->_where['donor'] = $this->_sql->getWhere();
        
        $this->_loadWhere();
        $this->_update();
        $this->_updateForward();
        $this->_monthly();
    }
    
    private function _loadWhere()
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereEqual('payment_date', $this->_date);
        $this->_where['payment_date'] = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereEqual('auction_date', $this->_date);
        $this->_where['auction_date'] = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereLess('payment_date', $this->_date);
        $this->_where['previous'] = $this->_sql->getWhere();
    }
    
    private function _update()
    {
        $this->_loadCurrent();
        $this->_loadCatch();
        $this->_loadPayment();
        
        if (empty($this->_catch) && empty($this->_payment)) {
            if ($this->_current > 0) {
                $this->_model->deleteById($this->_current);
            }
        }
        $this->_loadPrevious();
        
        $daily = $this->_getDailyAccount();
        if ($this->_current > 0) {
            $this->_model->updateById($daily, $this->_current);
            $this->_previous = $this->_model->find($this->_current);
        } else {
            $daily['donor_id'] = $this->_donorId;
            $daily['payment_date'] = $this->_date;
            $id = $this->_model->insert($daily);
            $this->_previous = $this->_model->find($id);
        }
        
        $this->_loadMonth();
    }
    
    private function _updateForward()
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereGreater('payment_date', $this->_date);
        $where = $this->_sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            return;
        }
        
        $dates = $this->_model->selectColumns('payment_date', $where, 'payment_date');
        foreach ($dates as $date) {
            $this->_date = $date;
            $this->_loadWhere();
            $this->_loadCurrent();
            $this->_loadCatch();
            $this->_loadPayment();

            if (empty($this->_catch) && empty($this->_payment)) {
                if ($this->_current > 0) {
                    $this->_model->deleteById($this->_current);
                }
            }

            $daily = $this->_getDailyAccount();
            if ($this->_current > 0) {
                $this->_model->updateById($daily, $this->_current);
                $this->_previous = $this->_model->find($this->_current);
            } else {
                $daily['donor_id'] = $this->_donorId;
                $daily['payment_date'] = $this->_date;
                $id = $this->_model->insert($daily);
                $this->_previous = $this->_model->find($id);
            }

            $this->_loadMonth();
        }
    }
    
    private function _monthly()
    {
        if (empty($this->_months)) {
            return;
        }
        
        foreach ($this->_months as $year => $months) {
            foreach ($months as $month) {
                $this->_monthlyAccount($year, $month);
            }
        }
    }
    
    private function _monthlyAccount($year, $month)
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereId('payment_year', $year)
                   ->whereId('payment_month', $month);
        $where1 = $this->_sql->getWhere();
        
        $current = 0;
        if ($this->_donorAccountMonthlyModel->selectTotal($where1) > 0) {
            $current = $this->_donorAccountMonthlyModel->selectId($where1);
        }
        
        $period = $this->_getPeriod($year, $month);
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereBetween('payment_date', $period[0], $period[1]);
        $where2 = $this->_sql->getWhere();
        if (0 == $this->_model->selectTotal($where2)) {
            if ($current > 0) {
                $this->_donorAccountMonthlyModel->deleteById($current);
            }
            
            return;
        }
        
        $opening = $this->_getMonthlyBalance($year, $month);
        
        $query = 'SELECT SUM(`amount_paid`) AS `amount_paid`, '
               . 'SUM(`advance`) AS `advance`, SUM(`auction_amount`) AS `auction_amount` '
               . 'FROM ' . $this->_model->getTableQuoted() . ' WHERE ' . $where2;
        $summary = $this->_model->queryRow($query);
        
        $monthly = [
            'opening_balance' => $opening,
            'amount_paid' => $summary->amount_paid,
            'auction_amount' => $summary->auction_amount,
            'advance' => $summary->advance,
            'updated_at' => $this->_now,
        ];
        
        $monthly['balance'] = $monthly['auction_amount'] - $monthly['advance'];
        $monthly['closing_balance'] = $this->_model->selectColumn('closing_balance', $where2, 'payment_date DESC');
        
        if ($current > 0) {
            $this->_donorAccountMonthlyModel->updateById($monthly, $current);
        } else {
            $monthly['donor_id'] = $this->_donorId;
            $monthly['payment_year'] = $year;
            $monthly['payment_month'] = $month;
            $this->_donorAccountMonthlyModel->insert($monthly);
        }
    }
    
    private function _loadCurrent()
    {
        $this->_current = 0;
        if ($this->_model->selectTotal($this->_where['payment_date']) > 0) {
            $this->_current = $this->_model->selectId($this->_where['payment_date']);
        }
    }
    
    private function _loadCatch()
    {
        $this->_catch = null;
        if ($this->_donorDailyModel->selectTotal($this->_where['auction_date']) > 0) {
            $this->_catch = $this->_donorDailyModel->selectRow($this->_where['auction_date']);
        }
    }
    
    private function _loadPayment()
    {
        $this->_payment = 0;
        if ($this->_donorPaymentModel->selectTotal($this->_where['payment_date']) > 0) {
            $this->_payment = (int) $this->_donorPaymentModel->selectSum('amount', $this->_where['payment_date']);
        }
    }

    private function _loadPrevious()
    {
        $this->_previous = null;
        if ($this->_model->selectTotal($this->_where['previous']) > 0) {
            $this->_previous = $this->_model->selectRow($this->_where['previous'], 'payment_date DESC');
        }
    }
    
    private function _getDailyAccount()
    {
        $account = [
            'opening_balance' => 0,
            'amount_paid' => 0,
            'auction_amount' => 0,
            'advance' => 0,
            'balance' => 0,
            'closing_balance' => 0,
            'updated_at' => $this->_now,
        ];
        
        $closing = 0;
        if (! empty($this->_previous)) {
            $account['opening_balance'] = $this->_previous->closing_balance;
            $closing = $this->_previous->closing_balance;
        } else {
            $initialBalanceModel  = new InitialOpeningBalance();
            if ($initialBalanceModel->selectTotal($this->_where['donor'])) {
                $initial = $initialBalanceModel->selectRow($this->_where['donor']);
                $account['opening_balance'] = $initial->opening_balance;
                $closing = $initial->opening_balance;
            }
        }
        
        if (! empty($this->_catch)) {
            $account['auction_amount'] = $this->_catch->amount;
            $account['advance'] = $this->_catch->advance;
            $account['balance'] = $this->_catch->balance;
            $closing += $this->_catch->balance;
        }
        
        if ($this->_payment > 0) {
            $account['amount_paid'] = $this->_payment;
            $closing -= $this->_payment;
        }
        
        $account['closing_balance'] = $closing;
        
        return $account;
    }
    
    private function _getMonthlyBalance($year, $month)
    {
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereId('payment_year', $year)
                   ->whereLess('payment_month', $month);
        $where = $this->_sql->getWhere();
                
        if ($this->_donorAccountMonthlyModel->selectTotal($where) > 0) {
            return $this->_donorAccountMonthlyModel->selectColumn('closing_balance', $where, 'payment_month DESC');
        }
        
        $this->_sql->clear();
        $this->_sql->where($this->_where['donor'])
                   ->whereLess('payment_year', $year);
        $where2 = $this->_sql->getWhere();
        
        if ($this->_donorAccountMonthlyModel->selectTotal($where2) > 0) {
            return $this->_donorAccountMonthlyModel->selectColumn('closing_balance', $where2, 'payment_year DESC, payment_month DESC');
        }
        
        $initialBalanceModel  = new InitialOpeningBalance();
        if ($initialBalanceModel->selectTotal($this->_where['donor'])) {
            $initial = $initialBalanceModel->selectRow($this->_where['donor']);
            
            return $initial->opening_balance;
        }
        
        return 0;
    }
    
    private function _loadMonth()
    {
        $date = DateHelper::getDateTime($this->_date);
        $year = $date->format('Y');
        if (! isset($this->_months[$year])) {
            $this->_months[$year] = [];
        }
        
        $month = $date->format('n');
        if (! in_array($month, $this->_months[$year])) {
            $this->_months[$year][] = $month;
        }
    }
    
    private function _getPeriod($year, $month)
    {
        $date = DateHelper::getDateTime();
        $date->setDate($year, $month, 1);
        $period = [$date->format(DateHelper::MYSQL_DATE)];
        $days = $date->format('t');
        $date->setDate($year, $month, $days);
        $period[] = $date->format(DateHelper::MYSQL_DATE);
        
        $days += 10;
        $date->modify('-' . $days . ' days');
        $period[] = $date->format('Y');
        $period[] = $date->format('n');
        
        return $period;
    }
    
}
