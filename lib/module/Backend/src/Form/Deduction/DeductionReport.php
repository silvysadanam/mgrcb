<?php
/**
 * Backend Deduction Report Utility
 * 
 * @category Backend
 * @package  Backend\Utility
 */

namespace Backend\Utility;

use Model\{DeductionMemberDaily, Member, Fmc, DeductionFmc};

use Shared\Utility\DeductionHelper;
use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;
use Web\Utility\DateHelper;
use Web\View\Support\LookupStore;


class DeductionReport
{
    /**
     *
     * @var \Model\DeductionMemberDaily 
     */
    private $_model;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var string
     */
    private $_startDate = '';
    
    /**
     * 
     * @var string
     */
    private $_endDate = '';
    
    /**
     * 
     * @var int
     */
    private $_groupId = 0;
    
    /**
     * 
     * @var int
     */
    private $_fmcId = 0;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_memberIds = [];
    
    public function __construct()
    {

        $this->_model = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * set start date
     * @param string $date
     * @return $this
     */
    public function setStartDate($date)
    {
        $this->_startDate = $date;
        return $this;
    }
    
    /**
     * set emd date
     * 
     * @param string $date
     * @return $this
     */
    public function setEndDate($date)
    {
        $this->_endDate = $date;
        return $this;
    }
    
    /**
     * set group id
     * 
     * @param int $id
     * @return $this
     */
    public function setGroupId($id)
    {
        $this->_groupId = $id;
        return $this;
    }
    
    /**
     * set fmc id
     * 
     * @param int $id
     * @return $this
     */
    public function setFmcId($id)
    {
        $this->_fmcId = $id;
        return $this;
    }
    
    /**
     * get report
     * 
     * @return array
     */
    public function report()
    {
        return $this->_report;
    }
    
    /**
     * run report
     * 
     * @return boolean
     */
    public function run()
    {
        $this->_preload();
        $this->_loadReport();
        if (0 == $this->_report['total']) {
            return false;
        }
        
        return true;
    }
    
    private function _loadReport()
    {
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_memberIds)
                   ->whereIn('deduction_type_id', $this->_report['deductions'])
                   ->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where = $this->_sql->getWhere();
        
        $this->_report['total'] = $this->_model->selectTotal($where);
        
        if (0 == $this->_report['total']) {
            return;
        }
        
        $query2 = 'SELECT DISTINCT `member_id`, `deduction_type_id`, '
                . 'SUM(`deduction_amount`) AS `deduction_amount`, '
                . 'SUM(`advance`) AS `advance`, '
                . 'SUM(`withdrawal`) AS `withdrawal`, '
                . 'SUM(`repayment`) AS `repayment` '
                . 'FROM ' . $this->_model->getTableQuoted()
                . ' WHERE ' . $where . ' GROUP BY `member_id`, `deduction_type_id` '
                . 'ORDER BY `member_id`';
        $list = $this->_model->queryRows($query2);
        
        $this->_sql->clear();
        $this->_sql->whereBetween('deduction_date', $this->_startDate, $this->_endDate);
        $where2 = $this->_sql->getWhere();
        
        $query3 = 'SELECT `opening_balance` FROM ' . $this->_model->getTableQuoted()
                . ' WHERE `member_id` =? AND `deduction_type_id` =? AND ' . $where2
                . ' ORDER BY `deduction_date` ASC LIMIT 1';
        $opening = $this->_model->prepare($query3);
        
        $query4 = 'SELECT `closing_balance` FROM ' . $this->_model->getTableQuoted()
                . ' WHERE `member_id` =? AND `deduction_type_id` =? AND ' . $where2
                . ' ORDER BY `deduction_date` DESC LIMIT 1';
        $closing = $this->_model->prepare($query4);
        
        $sum = [
            'collection' => 0,
            'withdrawal' => 0,
            'opening' => 0,
            'closing' => 0,
        ];
        
        $default = [];
        $fields = array_keys($sum);
        
        $this->_report['report_total'] = [];
        foreach ($this->_report['deductions'] as $deductionItem) {
            $default[$deductionItem] = $sum;
        }
        
        $this->_report['report_total'] = $default;
        
        $this->_report['report'] = [];
        foreach ($list as $item) {
            if (! isset($this->_report['report'][$item->member_id])) {
                $this->_report['report'][$item->member_id] = $default;
            }
            
            $summary = [
                'collection' => $item->deduction_amount - $item->repayment,
                'withdrawal' => $item->advance + $item->withdrawal,
                'opening' => 0,
                'closing' => 0,
            ];
            
            $opening->execute([$item->member_id, $item->deduction_type_id]);
            $summary['opening'] = $opening->fetchColumn();
            $opening->closeCursor();
            
            $closing->execute([$item->member_id, $item->deduction_type_id]);
            $summary['closing'] = $closing->fetchColumn();
            $closing->closeCursor();
            
            $this->_report['report'][$item->member_id][$item->deduction_type_id] = $summary;
            
            foreach ($fields as $field) {
                $this->_report['report_total'][$item->deduction_type_id][$field] += $summary[$field];
            }
        }
    }
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_report['filters'] = [
            'fmc_id' => $this->_fmcId,
            'start' => $this->_startDate,
            'end' => $this->_endDate,
            'group' => $this->_groupId,
            'period' => [
                DateHelper::datepicker($this->_startDate),
                DateHelper::datepicker($this->_endDate),
            ],
        ];
        
        $fmcModel = new Fmc();
        $this->_report['filters']['fmc'] = $fmcModel->selectColumnById('name', $this->_fmcId);
        
        $start = DateHelper::getDateTime($this->_startDate);
        $end = DateHelper::getDateTime($this->_endDate);
        
        $this->_report['params'] = 'fmc=' . $this->_fmcId . '&amp;group=' . $this->_groupId
                                 . '&amp;start=' . $start->format('Ymd')
                                 . '&amp;end=' . $end->format('Ymd');
        
        $deductionFmcModel = new DeductionFmc();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId);
        
        $brachDeductionIds = $deductionFmcModel->selectColumns('deduction_type_id', $this->_sql->getWhere());
        $groupedDeductions = DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
        $this->_report['deductions'] = $groupedDeductions[$this->_groupId];
        
        $this->_report['titles'] = [
            'opening' => 'Opening',
            'collection' => 'Collection',
            'withdrawal' => 'Withdrawal',
            'closing' => 'Balance',
        ];
        
        $memberModel = new Member();
        $this->_sql->clear();
        $this->_sql->whereId('fmc_id', $this->_fmcId)
                   ->whereId('membership_type_id', MembershipType::LIFEMEMBER);
        $this->_memberIds = $memberModel->selectIds($this->_sql->getWhere());
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_memberIds);
        $where = $this->_sql->getWhere();
        
        LookupStore::register('member_code', $memberModel->selectPairs(['id', 'code'], $where, 'code'));
        LookupStore::register('member_name', $memberModel->selectPairs(['id', 'name'], $where, 'id'));
    }
    
    /*
     * print report
     */
    private function _print()
    {
        echo '<pre>';
        print_r($this->_report);
        exit;
    }
    
}
