<?php
/**
 * Backend DeductionBillReport Utility
 *
 * @category   Backend
 * @package    Backend\Utility
 */

namespace Backend\Utility;

use Model\{AuctionChit, Member, Fmc, DeductionBill};

use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;

use Web\Utility\DateHelper;


class DeductionBillReport
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\Member
     */
    private $_memberModel;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_fmc;
    
    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_hasDeductionBill;
    
    /**
     * 
     * @var string
     */
    private $_pending;
    
    /**
     * 
     * @var array
     */
    private $_default = [];
    
    /**
     * 
     * @var string
     */
    private $_memberType;
    
    /**
     * 
     * @var int
     */
    private $_fmcId;
    
    /**
     * 
     * @var \DateTime
     */
    private $_time;
    
    /**
     * 
     * @var \Model\DeductionBill
     */
    private $_deductionBillModel;
    
    public function __construct()
    {
        $this->_model = new AuctionChit();
        $this->_memberModel = new Member();
        
        $fmcModel = new Fmc();
        $this->_fmc = $fmcModel->selectIds();
        $this->_sql = $this->_model->getSql();
        $this->_sql->whereOne('has_deduction_bill');
        $this->_hasDeductionBill = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereZero('has_deduction_bill');
        $this->_pending = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereId('member_type_id', MembershipType::LIFEMEMBER);
        $this->_memberType = $this->_sql->getWhere();
        
        $this->_deductionBillModel = new DeductionBill();
    }
    
    /**
     * get summary report
     */
    public function summary()
    {
        $this->_report['summary'] = [
            'chits' => 0, 'bills' => 0, 'pending' => 0, 'backlog' => 0,
            'auction_date' => '', 'deduction_date' => '',
        ];
        
        $this->_report['summary']['chits'] = $this->_model->selectTotal();
        $this->_report['summary']['bills'] = $this->_model->selectTotal($this->_hasDeductionBill);
        $this->_report['summary']['pending'] = $this->_report['summary']['chits'] - $this->_report['summary']['bills'];
        $this->_report['summary']['auction'] = $this->_model->selectMax('auction_date');
        $this->_report['summary']['deduction'] = $this->_model->selectMax('auction_date', $this->_hasDeductionBill);
        
        $this->_default['fmc'] = [
            'chits' => 0, 'bills' => 0, 'pending' => 0, 'backlog' => 0,
            'auction' => '', 'deduction' => '', 'last' => '',
        ];
        
        $this->_report['fmc'] = [];
        foreach ($this->_fmc as $fmcId) {
            $this->_fmcId = $fmcId;
            $this->_fmcSummary();
        }
        
        return $this->_report;
    }
    
    public function monthly($fmcId)
    {
        $this->_fmcId = $fmcId;
        $members = $this->_fmcMembers();
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $members);
        $where = $this->_sql->getWhere();
        $this->_default['fmc'] = [
            'chits' => 0, 'bills' => 0, 'pending' => 0,
        ];
        
        $this->_report['summary'] = [
            'chits' => 0, 'bills' => 0, 'pending' => 0,
        ];
        $query1 = 'SELECT COUNT(`id`) AS `total`, YEAR(`auction_date`) AS `auction_year`, '
                . 'MONTH(`auction_date`) AS `auction_month` FROM ' . $this->_model->getTableQuoted()
                . ' WHERE ';
        $query2 = ' GROUP BY YEAR(`auction_date`), MONTH(`auction_date`) '
                . 'ORDER BY `auction_year` DESC, `auction_month` DESC';
        $query3 = $query1 . $where . $query2;
        $list = $this->_model->queryRows($query3);
        $this->_report['dates'] = [];
        $this->_report['fmc'] = [];
        $this->_time = DateHelper::getDateTime();
        if (empty($list)) {
            return $this->_report;
        }
        
        foreach ($list as $item) {
            if (! isset($this->_report['fmc'][$item->auction_year])) {
                $this->_report['fmc'][$item->auction_year] = [];
            }
            $summary = $this->_default['fmc'];
            $summary['chits'] = $item->total;
            $summary['pending'] = $item->total;
            $this->_report['summary']['chits'] += $item->total;
            $this->_report['fmc'][$item->auction_year][$item->auction_month] = $summary;
            $this->_loadDate($item->auction_year, $item->auction_month);
        }
        
        $this->_sql->clear();
        $this->_sql->where($where)
                   ->where($this->_hasDeductionBill);
        $query4 = $query1 . $this->_sql->getWhere() . $query2;
        $pending = $this->_model->queryRows($query4);
        if (! empty($pending)) {
            foreach ($pending as $date) {
                $this->_report['fmc'][$date->auction_year][$date->auction_month]['bills'] = $date->total;
                $this->_report['fmc'][$date->auction_year][$date->auction_month]['pending'] = $this->_report['fmc'][$date->auction_year][$date->auction_month]['chits'] - $date->total;
                $this->_report['summary']['bills'] += $date->total;
            }
        }
        
        $this->_report['summary']['pending'] = $this->_report['summary']['chits'] - $this->_report['summary']['bills'];
        
        return $this->_report;
    }
    
    private function _loadDate($year, $month)
    {
        if (! isset($this->_report['dates'][$year])) {
            $this->_report['dates'][$year] = [];
        }
        
        if (! isset($this->_report['dates'][$year][$month])) {
            $this->_time->setDate($year, $month, 10);
            $this->_report['dates'][$year][$month] = $this->_time->format('F Y');
        }
    }
    
    private function _fmcSummary()
    {
        $this->_report['fmc'][$this->_fmcId] = $this->_default['fmc'];
        $members = $this->_fmcMembers();
        if (empty($members)) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $members);
        $where = $this->_sql->getWhere();
        $total = $this->_model->selectTotal($where);
        if (0 == $total) {
            return;
        }
        
        $this->_report['fmc'][$this->_fmcId]['chits'] = $total;
        $this->_report['fmc'][$this->_fmcId]['auction'] = $this->_model->selectMax('auction_date', $where);
        $this->_sql->clear();
        $this->_sql->where($where)
                   ->where($this->_hasDeductionBill);
        $where2 = $this->_sql->getWhere();
        $this->_report['fmc'][$this->_fmcId]['bills'] = $this->_model->selectTotal($where2);
        $this->_report['fmc'][$this->_fmcId]['pending'] = $total - $this->_report['fmc'][$this->_fmcId]['bills'];
        $this->_report['fmc'][$this->_fmcId]['deduction'] = $this->_model->selectMax('auction_date', $where2);
        if ($this->_report['fmc'][$this->_fmcId]['bills'] < $total) {
            $this->_sql->clear();
            $this->_sql->where($where)
                       ->where($this->_pending)
                       ->whereLess('auction_date', $this->_report['fmc'][$this->_fmcId]['deduction']);
            $backlog = $this->_model->selectTotal($this->_sql->getWhere());
            if ($backlog > 0) {
                $this->_report['fmc'][$this->_fmcId]['backlog'] = $backlog;
                $this->_report['summary']['backlog'] += $backlog;
            }
        }
        
        $this->_report['fmc'][$this->_fmcId]['last'] = $this->_deductionBillModel->selectMax('deduction_date', $where);
    }
    
    private function _fmcMembers()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId);
        
        return $this->_memberModel->selectIds($this->_sql->getWhere());
        
    }
    
}
