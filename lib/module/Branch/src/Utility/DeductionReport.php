<?php
/**
 * Branch Deduction Report Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;

use Model\{DeductionMemberDaily, InitialDeductionBalance};
use Shared\Utility\DeductionHelper;
use Web\Utility\DateHelper;
use DateTime;

class DeductionReport
{
    /**
     * 
     * @var \Branch\Utility\BranchStore
     */
    private $_branch;
    
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
    private $_initalBalance;
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    public function __construct($branch)
    {
        $this->_branch = $branch;
        $this->_model = new DeductionMemberDaily();
        $this->_sql = $this->_model->getSql();
        $this->_initalBalance = new InitialDeductionBalance();
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
        //FMC contribution skipped
        if (($key = array_search(1, $this->_report['deductions'])) !== false) {
            unset($this->_report['deductions'][$key]);
        }
        $this->_sql->clear();
        $this->_sql->whereIn('member_id', $this->_branch->memberIds())
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
            'start' => $this->_startDate,
            'end' => $this->_endDate,
            'group' => $this->_groupId,
            'period' => [
                DateHelper::datepicker($this->_startDate),
                DateHelper::datepicker($this->_endDate),
            ],
        ];
        
        $start = DateHelper::getDateTime($this->_startDate);
        $end = DateHelper::getDateTime($this->_endDate);
        
        $this->_report['params'] = 'group=' . $this->_groupId
                                 . '&amp;start=' . $start->format('Ymd')
                                 . '&amp;end=' . $end->format('Ymd');
        
        $deductions = $this->_branch->deductionTypes();
        $brachDeductionIds = array_keys($deductions);
        $groupedDeductions = DeductionHelper::groupedDeductionTypeIdsSelection($brachDeductionIds);
        $this->_report['deductions'] = $groupedDeductions[$this->_groupId];
        
        $this->_report['titles'] = [
            'opening' => 'Opening',
            'collection' => 'Collection',
            'withdrawal' => 'Withdrawal',
            'closing' => 'Balance',
        ];
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
    
    public function getReport($deductionTypeID, $endDate = null)
    {
        $this->_sql->clear();

        $fydata = $this->getFinancialYearInfo($endDate);
        $this->_startDate = $fydata['year'];
        if($endDate){
            $this->_endDate = DateHelper::fromDatepicker($endDate);
        } else {
            $this->_endDate = DateHelper::today();
        }
        $this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereId('deduction_type_id', $deductionTypeID)
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
        $this->_report['deductions'][0] = $deductionTypeID;
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
        return $this->_report['report_total'];
    }
    
    public function getDeductionOpening($deductionTypeID, $endDate = null)
    {
        $this->_sql->clear();
        $fydata = $this->getFinancialYearInfo($endDate);
        $this->_startDate = $fydata['year'];
        $openingBalance = 0;
        if($endDate){
            $this->_endDate = DateHelper::fromDatepicker($endDate);
        } else {
            $this->_endDate = DateHelper::today();
        }
        $this->_sql->whereIn('member_id', $this->_branch->memberIds())
                   ->whereId('deduction_type_id', $deductionTypeID);
        $where = $this->_sql->getWhere();
        $query2 = 'SELECT  SUM(`opening_balance`) AS `opening_balance` '
                . 'FROM ' . $this->_initalBalance->getTableQuoted()
                . ' WHERE ' . $where . ' GROUP BY `deduction_type_id` ';
       $data = $this->_initalBalance->queryRows($query2);
       if(isset($data[0]->opening_balance)){
            $openingBalance =  $data[0]->opening_balance;
       }
       return $openingBalance;
    }
    
private function getFinancialYearInfo($endDate = null)
{
    if ($endDate) {
        $date = DateTime::createFromFormat('d/m/Y', $endDate);

        // Handle invalid date safely
        if (!$date) {
            throw new Exception("Invalid date format. Expected d/m/Y");
        }
    } else {
        $date = new DateTime();
    }

    $month = (int)$date->format("m");
    $year  = (int)$date->format("Y");

    if ($month >= 4) {
        $currentYear = $year;
        $financial_year = $year . '-' . substr($year + 1, -2);
    } else {
        $currentYear = $year - 1;
        $financial_year = ($year - 1) . '-' . substr($year, -2);
    }

    return [
        'year' => $currentYear . "-04-01",
        'FY'   => $financial_year
    ];
}
}
