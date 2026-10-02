<?php
/**
 * Shared AuctionSummary Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Shared\Utility\AuctioneerAccount;

use Model\{AuctionChit, AuctionChitEntry, AuctionDaily, AuctionMonthly};
use Model\{Member, AuctionFmcDaily, AuctionFmcMonthly};
use Model\{AuctioneerDaily, AuctioneerMonthly, FishDaily};

use Web\Utility\DateHelper;


class AuctionSummary
{
    /**
     * 
     * @var \Model\AuctionChit
     */
    private $_model;
    
    /**
     * 
     * @var \Model\AuctionChitEntry
     */
    private $_auctionChitEntryModel;
    
    /**
     * 
     * @var \Model\AuctionDaily
     */
    private $_auctionDailyModel;
    
    /**
     * 
     * @var \Model\AuctionMonthly
     */
    private $_auctionMonthlyModel;
    
    /**
     * 
     * @var \Model\Member
     */
    private $_memberModel;
    
    /**
     * 
     * @var \Model\AuctionFmcDaily
     */
    private $_auctionFmcDailyModel;
    
    /**
     * 
     * @var \Model\AuctionFmcMonthly
     */
    private $_auctionFmcMonthlyModel;
    
    /**
     * 
     * @var int
     */
    private $_managerId;
    
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
     * @var int
     */
    private $_memberId;
    
    /**
     * 
     * @var string
     */
    private $_auctionDate;
    
     /**
     * 
     * @var int
     */
    private $_fmcId;
    
    /**
     * 
     * @var array
     */
    private $_fmcMembers;
    
    /**
     * 
     * @var array
     */
    private $_dates = [];
    
    /**
     * 
     * @var int
     */
    private $_auctioneerId;
    
    /**
     * constructor
     */
    public function __construct($managerId = 0)
    {
        $this->_managerId = $managerId;
        $this->_model = new AuctionChit();
        $this->_auctionChitEntryModel = new AuctionChitEntry();
        $this->_auctionDailyModel = new AuctionDaily();
        $this->_auctionMonthlyModel = new AuctionMonthly();
        $this->_memberModel = new Member();
        $this->_auctionFmcDailyModel = new AuctionFmcDaily();
        $this->_auctionFmcMonthlyModel = new AuctionFmcMonthly();
        $this->_sql = $this->_model->getSql();
        $this->_now = DateHelper::timestamp();
    }
    
    /**
     * auction chit created
     * 
     * @param int $id
     */
    public function created($id)
    {
        $this->_reload($id);
    }
    
    /**
     * auction chit deleted
     * 
     * @param int $memberId
     * @param int $auctionDate
     * @param int $auctioneerId
     */
    public function deleted($memberId, $auctionDate, $auctioneerId)
    {
        $this->_memberId = $memberId;
        $this->_auctionDate = $auctionDate;
        $this->_auctioneerId = $auctioneerId;
        $this->_run();
    }
    
    /**
     * auction chit updated
     * 
     * @param int $id
     */
    public function updated($id)
    {
        $this->_reload($id);
    }
    
    /**
     * advance updated
     * 
     * @param int $id
     */
    public function advance($id)
    {
        $chit = $this->_model->find($id);
        $this->_memberId = $chit->member_id;
        $this->_auctionDate = $chit->auction_date;
        $this->_auctioneerId = $chit->auctioneer_id;
        $this->_loadMonthlyDates();
        $this->_advanceDaily();
        $this->_advanceMonthly();
        $this->_advanceFmcSummary();
        $this->_advanceAuctioneerSummary();
        $this->_auctioneerAccount();
    }
    
    /**
     * reload summary
     * 
     * @param int $id
     */
    private function _reload($id)
    {
        $chit = $this->_model->find($id);
        $this->_memberId = $chit->member_id;
        $this->_auctionDate = $chit->auction_date;
        $this->_auctioneerId = $chit->auctioneer_id;
        $this->_sql->whereId('auction_chit_id', $id);
        $summary = $this->_getEntriesSummary($this->_sql->getWhere());
        $summary['balance'] = $summary['amount'] - $chit->advance;
        $this->_model->updateById($summary, $id);
        $this->_run();
    }
    
    /**
     * run summary update
     */
    private function _run()
    {
        $this->_loadMonthlyDates();
        $this->_memberSummary();
        $this->_fmcSummary();
        $this->_auctioneerSummary();
        $this->_medicineSummary();
        $this->_auctioneerAccount();
    }
    
    /**
     * update auctioneer account
     */
    private function _auctioneerAccount()
    {
        $account = new AuctioneerAccount($this->_auctioneerId);
        $account->run($this->_auctionDate);
    }
    
    /**
     * member summary
     */
    private function _memberSummary()
    {
        $this->_daily();
        $this->_monthly();
    }
    
    /**
     * daily summary
     * 
     * @return null
     */
    private function _daily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        
        $current = false;
        if ($this->_auctionDailyModel->selectTotal($where) > 0) {
            $current = $this->_auctionDailyModel->selectId($where);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $this->_auctionDailyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['updated_at'] = $this->_now;
        
        if (false !== $current) {
            $this->_auctionDailyModel->updateById($summary, $current);
            return;
        }
        
        $summary['member_id'] = $this->_memberId;
        $summary['auction_date'] = $this->_auctionDate;
        $this->_auctionDailyModel->insert($summary);
    }
    
    /**
     * monthly summary
     * 
     * @return null
     */
    private function _monthly()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        $current = false;
        if ($this->_auctionMonthlyModel->selectTotal($whereMonthly) > 0) {
            $current = $this->_auctionMonthlyModel->selectId($whereMonthly);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $this->_auctionMonthlyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['updated_at'] = $this->_now;
        $summary['auction_days'] = $this->_auctionDailyModel->selectTotal($where);

        if (false !== $current) {
            $this->_auctionMonthlyModel->updateById($summary, $current);
            return;
        }
        
        $summary['member_id'] = $this->_memberId;
        $summary['auction_date'] = $this->_dates[1];
        $this->_auctionMonthlyModel->insert($summary);
    }
    
    /**
     * daily advance summary
     * 
     * @return null
     */
    private function _advanceDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if (0 == $this->_auctionDailyModel->selectTotal($where)) {
            $this->_daily();
            return;
        }
        
        $current = $this->_auctionDailyModel->selectRow($where);
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        $this->_auctionDailyModel->updateById($summary, $current->id);
    }
    
    /**
     * monthly advance summary
     * 
     * @return null
     */
    private function _advanceMonthly()
    {
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        if (0 == $this->_auctionMonthlyModel->selectTotal($whereMonthly)) {
            $this->_monthly();
            return;
        }
        
        $current = $this->_auctionMonthlyModel->selectRow($whereMonthly);
        $this->_sql->clear();
        $this->_sql->whereId('member_id', $this->_memberId)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        
        $this->_auctionMonthlyModel->updateById($summary, $current->id);
    }
    
    /**
     * medicine summary
     */
    private function _fmcSummary()
    {
        $this->_loadFmc();
        $this->_fmcDaily();
        $this->_fmcMonthly();
    }
    
    /**
     * daily fmc summary
     * 
     * @return null
     */
    private function _fmcDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_fmcMembers)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $whereDaily = $this->_sql->getWhere();
        
        $current = false;
        if ($this->_auctionFmcDailyModel->selectTotal($whereDaily) > 0) {
            $current = $this->_auctionFmcDailyModel->selectId($whereDaily);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $this->_auctionFmcDailyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['updated_at'] = $this->_now;
        $summary['members'] = $this->_getMemberCount('member_id', $where);
        $summary['auctioneers'] = $this->_getMemberCount('auctioneer_id', $where);
        
        if (false !== $current) {
            $this->_auctionFmcDailyModel->updateById($summary, $current);
            return;
        }
        
        $summary['fmc_id'] = $this->_fmcId;
        $summary['auction_date'] = $this->_auctionDate;
        $this->_auctionFmcDailyModel->insert($summary);
    }
    
    /**
     * monthly fmc summary
     * 
     * @return null
     */
    private function _fmcMonthly()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_fmcMembers)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        $current = false;
        if ($this->_auctionFmcMonthlyModel->selectTotal($whereMonthly) > 0) {
            $current = $this->_auctionFmcMonthlyModel->selectId($whereMonthly);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $this->_auctionFmcMonthlyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['updated_at'] = $this->_now;
        $summary['members'] = $this->_getMemberCount('member_id', $where);
        $summary['auctioneers'] = $this->_getMemberCount('auctioneer_id', $where);
        
        if (false !== $current) {
            $this->_auctionFmcMonthlyModel->updateById($summary, $current);
            return;
        }
        
        $summary['fmc_id'] = $this->_fmcId;
        $summary['auction_date'] = $this->_dates[1];
        $this->_auctionFmcMonthlyModel->insert($summary);
    }
    
    /**
     * fmc advance summary
     */
    private function _advanceFmcSummary()
    {
        $this->_loadFmc();
        $this->_advanceFmcDaily();
        $this->_advanceFmcMonthly();
    }
    
    /**
     * daily fmc summary
     * 
     * @return null
     */
    private function _advanceFmcDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $whereDaily = $this->_sql->getWhere();
        
        if (0 == $this->_auctionFmcDailyModel->selectTotal($whereDaily)) {
            $this->_fmcDaily();
            return;
        }
        
        $current = $this->_auctionFmcDailyModel->selectRow($whereDaily);
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_fmcMembers)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        $this->_auctionFmcDailyModel->updateById($summary, $current->id);
    }
    
    /**
     * monthly fmc summary
     * 
     * @return null
     */
    private function _advanceFmcMonthly()
    {
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_memberId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        if (0 == $this->_auctionFmcMonthlyModel->selectTotal($whereMonthly)) {
            $this->_fmcMonthly();
            return;
        }
        
        $current = $this->_auctionFmcMonthlyModel->selectRow($whereMonthly);
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_fmcMembers)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        
        $this->_auctionFmcMonthlyModel->updateById($summary, $current->id);
    }
    
    /**
     * auctioneer summary
     */
    private function _auctioneerSummary()
    {
        $this->_auctioneerDaily();
        $this->_auctioneerMonthly();
    }
    
    /**
     * daily auctioneer summary
     * 
     * @return null
     */
    private function _auctioneerDaily()
    {
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        
        $current = false;
        $auctioneerDailyModel = new AuctioneerDaily();
        if ($auctioneerDailyModel->selectTotal($where) > 0) {
            $current = $auctioneerDailyModel->selectId($where);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $auctioneerDailyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['members'] = $this->_getMemberCount('member_id', $where);
        $summary['updated_at'] = $this->_now;
        
        if (false !== $current) {
            $auctioneerDailyModel->updateById($summary, $current);
            return;
        }
        
        $summary['auctioneer_id'] = $this->_auctioneerId;
        $summary['auction_date'] = $this->_auctionDate;
        $auctioneerDailyModel->insert($summary);
    }
    
    /**
     * monthly auctioneer summary
     * 
     * @return null
     */
    private function _auctioneerMonthly()
    {
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        $auctioneerMonthlyModel = new AuctioneerMonthly();
        $current = false;
        if ($auctioneerMonthlyModel->selectTotal($whereMonthly) > 0) {
            $current = $auctioneerMonthlyModel->selectId($whereMonthly);
        }
        
        if (0 == $this->_model->selectTotal($where)) {
            if (false !== $current) {
                $auctioneerMonthlyModel->deleteById($current);
            }
            
            return;
        }
        
        $summary = $this->_getSummary($where);
        $summary['balance'] = $summary['amount'] - $summary['advance'];
        $summary['updated_at'] = $this->_now;
        
        $auctioneerDailyModel = new AuctioneerDaily();
        $summary['auction_days'] = $auctioneerDailyModel->selectTotal($where);
        $summary['members'] = $this->_getMemberCount('member_id', $where);

        if (false !== $current) {
            $auctioneerMonthlyModel->updateById($summary, $current);
            return;
        }
        
        $summary['auctioneer_id'] = $this->_auctioneerId;
        $summary['auction_date'] = $this->_dates[1];
        $auctioneerMonthlyModel->insert($summary);
    }
    
    /**
     * auctioneer advance summary
     */
    private function _advanceAuctioneerSummary()
    {
        $this->_advanceAuctioneerDaily();
        $this->_advanceAuctioneerMonthly();
    }
    
    /**
     * daily auctioneer advance summary
     * 
     * @return null
     */
    private function _advanceAuctioneerDaily()
    {
        $auctioneerDailyModel = new AuctioneerDaily();
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if (0 == $auctioneerDailyModel->selectTotal($where)) {
            $this->_auctioneerDaily();
            return;
        }
        
        $current = $auctioneerDailyModel->selectRow($where);
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        $auctioneerDailyModel->updateById($summary, $current->id);
    }
    
    /**
     * monthly auctioneer advance summary
     * 
     * @return null
     */
    private function _advanceAuctioneerMonthly()
    {
        $auctioneerMonthlyModel = new AuctioneerMonthly();
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereEqual('auction_date', $this->_dates[1]);
        $whereMonthly = $this->_sql->getWhere();
        
        if (0 == $auctioneerMonthlyModel->selectTotal($whereMonthly)) {
            $this->_auctioneerMonthly();
            return;
        }
        
        $current = $auctioneerMonthlyModel->selectRow($whereMonthly);
        $this->_sql->clear();
        $this->_sql->whereId('auctioneer_id', $this->_auctioneerId)
                   ->whereBetween('auction_date', $this->_dates[0], $this->_dates[1]);
        $where = $this->_sql->getWhere();
        $advance = $this->_model->selectSum('advance', $where);
        $summary = [
            'advance' => $advance,
            'balance' => $current->amount - $advance,
            'updated_at' => $this->_now,
        ];
        
        $auctioneerMonthlyModel->updateById($summary, $current->id);
    }
    
    /**
     * medicine summary
     * 
     * @return null
     */
    private function _medicineSummary()
    {
        $medicineDailyModel = new FishDaily();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $whereFmc = $this->_sql->getWhere();
        
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_fmcMembers)
                   ->whereEqual('auction_date', $this->_auctionDate);
        $where = $this->_sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            if ($medicineDailyModel->selectTotal($whereFmc) > 0) {
                $medicineDailyModel->delete($whereFmc);
            }
            
            return;
        }
        
        $entryIds = $this->_model->selectIds($where);
        $this->_sql->clear();
        $this->_sql->whereIn('auction_chit_id', $entryIds);
        $whereEntries = $this->_sql->getWhere();
        $query = 'SELECT `medicine_id`, COUNT(`id`) AS `entries`, SUM(`amount`) AS `amount`, '
               . 'SUM(`quantity`) AS `quantity` '
               . 'FROM ' . $this->_auctionChitEntryModel->getTableQuoted()
               . ' WHERE ' . $whereEntries . ' GROUP BY `medicine_id`';
        
        $list = $this->_model->queryRows($query);
        $medicineIds = [];
        foreach ($list as $item) {
            $medicineIds[] = $item->medicine_id;
            $summary = [
                'entries' => $item->entries,
                'amount' => $item->amount,
                'quantity' => $item->quantity,
                'updated_at' => $this->_now,
            ];
            
            $this->_sql->clear();
            $this->_sql->where($whereEntries)
                       ->whereId('medicine_id', $item->medicine_id);
            $whereFishEntries = $this->_sql->getWhere();
            $query1 = 'SELECT COUNT(DISTINCT `auction_chit_id`) FROM '
                    . $this->_auctionChitEntryModel->getTableQuoted()
                    . ' WHERE ' . $whereFishEntries;
            $summary['auction_chits'] = $this->_model->queryTotal($query1);
            $auctionChitIds = $this->_auctionChitEntryModel->selectDistinct('auction_chit_id', $whereFishEntries);
            
            $this->_sql->clear();
            $this->_sql->whereIn('id', $auctionChitIds);
            $summary['members'] = $this->_getMemberCount('member_id', $this->_sql->getWhere());
            
            $this->_sql->clear();
            $this->_sql->where($whereFmc)
                       ->whereId('medicine_id', $item->medicine_id);
            $whereFish = $this->_sql->getWhere();
            if ($medicineDailyModel->selectTotal($whereFish) > 0) {
                $id = $medicineDailyModel->selectId($whereFish);
                $medicineDailyModel->updateById($summary, $id);
            } else {
                $summary['fmc_id'] = $this->_fmcId;
                $summary['auction_date'] = $this->_auctionDate;
                $summary['medicine_id'] = $item->medicine_id;
                $medicineDailyModel->insert($summary);
            }
        }
        
        $this->_sql->clear();
        $this->_sql->where($whereFmc)
                   ->whereNotIn('medicine_id', $medicineIds);
        $whereExcluded = $this->_sql->getWhere();
        if ($medicineDailyModel->selectTotal($whereExcluded) > 0) {
            $ids = $medicineDailyModel->selectIds($whereExcluded);
            $medicineDailyModel->delete($ids);
        }
    }
    
    /**
     * get auction chit summary
     * 
     * @param string $where
     * @return array
     */
    private function _getSummary($where)
    {
        $query = 'SELECT COUNT(`id`) AS `entries`, SUM(`amount`) AS `amount`, '
               . 'SUM(`quantity`) AS `quantity`, SUM(`advance`) AS `advance` '
               . 'FROM ' . $this->_model->getTableQuoted() . ' WHERE ' . $where;
        
        return (array) $this->_model->queryRow($query);
    }
    
    /**
     * auction chit entries summary
     * 
     * @param string $where
     * @return array
     */
    private function _getEntriesSummary($where)
    {
        $query = 'SELECT COUNT(`id`) AS `entries`, SUM(`amount`) AS `amount`, '
               . 'SUM(`quantity`) AS `quantity` FROM ' 
               . $this->_auctionChitEntryModel->getTableQuoted()
               . ' WHERE ' . $where;
        return (array) $this->_model->queryRow($query);
    }
    
    /**
     * get member count
     * 
     * @param string $field
     * @param string $where
     * @return int
     */
    private function _getMemberCount($field, $where)
    {
        $query = 'SELECT COUNT(DISTINCT `' . $field . '`) FROM ' 
               . $this->_model->getTableQuoted() . ' WHERE ' . $where;
        
        return $this->_model->queryTotal($query);
    }
    
    /**
     * load fmc
     */
    private function _loadFmc()
    {
        $this->_fmcId = $this->_memberModel->selectColumnById('fmc_id', $this->_memberId);
        $this->_fmcMembers = $this->_memberModel->selectIds(['fmc_id' => $this->_fmcId]);
    }
    
    /**
     * load dates
     */
    private function _loadMonthlyDates()
    {
        $dateTime = DateHelper::getDateTime($this->_auctionDate);
        $year = $dateTime->format('Y');
        $month = $dateTime->format('n');
        $days = $dateTime->format('t');
        $dateTime->setDate($year, $month, 1);
        $this->_dates[] = $dateTime->format(DateHelper::MYSQL_DATE);
        $dateTime->setDate($year, $month, $days);
        $this->_dates[] = $dateTime->format(DateHelper::MYSQL_DATE);
    }
    
}
