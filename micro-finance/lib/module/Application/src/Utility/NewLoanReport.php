<?php
/**
 * Application NewLoan Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Loan, Member, Fmc};

use Application\ViewHelper\Currency;

use Shared\Model\PairModel;

use Web\Utility\{DateHelper, CsvWriter};
use Web\View\Support\LookupStore;


class NewLoanReport
{
    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Member 
     */
    private $_memberModel;

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
    private $_default = [];
    
    /**
     * 
     * @var array
     */
    private $_tables = [];
    
    /**
     * 
     * @var array
     */
    private $_joins = [];
    
    
    /**
     * 
     * @var array
     */
    private $_where = [];
    
    /**
     * 
     * @var \Application\ViewHelper\Currency
     */
    private $_currency;
    
    /**
     * 
     * @var int
     */
    private $_sourceId = 0;
    
    /**
     * 
     * @var int
     */
    private $_typeId = 0;
    
     /**
     * 
     * @var int
     */
    private $_wise = 0;
    
    /**
     * 
     * @var \Web\Utility\CsvWriter
     */
    private $_writer;
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_memberModel = new Member();
        $this->_sql = $this->_model->getSql();
        $this->_currency = new Currency();
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
     * set source id
     * 
     * @param int $id
     * @return $this
     */
    public function setSourceId($id)
    {
        $this->_sourceId = $id;
        return $this;
    }
    
    /**
     * set type id
     * 
     * @param int $id
     * @return $this
     */
    public function setTypeId($id)
    {
        $this->_typeId = $id;
        return $this;
    }
    
    /**
     * set wise
     * 
     * @param int $id
     * @return $this
     */
    public function setWise($id)
    {
        $this->_wise = $id;
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
        $this->_loadTotal();
        if (0 == $this->_report['total']['loans']) {
            return false;
        }

        if ($this->_wise > 0) {
            $this->_monthWiseReport();
        } else {
            $this->_fmcWiseReport();
        }
        
        return true;
    }
    
    /**
     * format amount
     * 
     * @param float $amount
     * @param boolean $mask
     * @return string
     */
    public function amount($amount, $mask = false)
    {
        if ($amount > 0.00) {
            return $this->_currency->currency($amount, 0);
        }
        
        if ($amount < 0.00) {
            return '-' . $this->_currency->currency(abs($amount), 0);
        }
        
        if ($mask) {
            return '--';
        }
        
        return '0';
    }
    
    /**
     * format amount for export
     * 
     * @param float $amount
     * @return string
     */
    public function exportAmount($amount)
    {
        if ($amount > 0) {
            return intval($amount);
        }
                
        return '';
    }
    
     /**
     * format amount for print
     * 
     * @param float $amount
     * @return string
     */
    public function printAmount($amount)
    {
        return intval($amount);
    }
    
    /**
     * check fmc selected
     * 
     * @return boolean
     */
    public function fmcSelected()
    {
        return $this->_fmcId > 0;
    }
    
    /**
     * check fmc wise report
     * 
     * @return boolean
     */
    public function fmcWise()
    {
        return 0 == $this->_wise;
    }
    
    public function getLoansParams($fmcId, $year, $month)
    {
        $data = $this->_report['members'];
        $data['fmc'] = $fmcId;
        $data['year'] = $year;
        $data['month'] = $month;
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[] = $key . ':' . $value;
        }
        
        return implode(',', $params);
    }
    
    /**
     * get loans and members list
     * 
     * @param string $params
     * @param string $url
     * @return array
     */
    public function loanList($params, $url)
    {
        $raw = explode(',', $params);
        $data = [];
        foreach ($raw as $item) {
            $values = explode(':', $item);
            $data[$values[0]] = (int) $values[1];
        }
        
        $fmcModel = new Fmc();
        $this->_report['summary'] = [
            'fmc' => $fmcModel->selectColumnById('name', $data['fmc']),
            'month' => '',
            'source' => 'ALL',
            'type' => 'ALL',
            'loans' => 0,
            'amount' => 0,
        ];
        
        $this->_report['html'] = '';
        
        $pairModel = new PairModel();
        $sources = $pairModel->loanSource();
        $types = $pairModel->loanType();
        
        
        if ($data['source'] > 0) {
            $this->_report['summary']['source'] = $sources[$data['source']];
            $this->_sql->whereId('l.loan_source_id', $data['source']);
        }
        
        if ($data['type'] > 0) {
            $$this->_report['summary']['type'] = $types[$data['type']];
            $this->_sql->whereId('l.loan_type_id', $data['type']);
        }
        
        $date = DateHelper::getDateTime();
        $date->setDate($data['year'], $data['month'], 1);
        $start = $date->format(DateHelper::MYSQL_DATE);
        $this->_report['summary']['month'] = $date->format('F Y');
        $days = $date->format('t');
        $date->setDate($data['year'], $data['month'], $days);
        $end = $date->format(DateHelper::MYSQL_DATE);
        $this->_sql->whereId('m.fmc_id', $data['fmc'])
                   ->whereBetween('l.loan_date', $start, $end);
        $query1 = 'FROM ' . $this->_model->getTableQuoted() . ' `l` '
                . 'INNER JOIN ' . $this->_memberModel->getTableQuoted() . ' `m` ON `m`.`id` = `l`.`member_id` '
                . 'WHERE ' . $this->_sql->getWhere();
        $query2 = 'SELECT COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
                . $query1;
        $total = $this->_model->queryRow($query2);
        $this->_report['summary']['loans'] = $total->loans;
        $this->_report['summary']['amount'] = $this->amount($total->amount);
        
        $query3 = 'SELECT `l`.`id`, `l`.`loan_amount`, `l`.`loan_date`, `l`.`loan_source_id`, '
                . '`l`.`loan_type_id`, `l`.`account_number`, `m`.`code`, `m`.`name` '
                . $query1 . ' ORDER BY `l`.`loan_date`';
        $list = $this->_model->queryRows($query3);
        $row = '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'
             . '<td>%s</td><td>%s</td><td><a class="btn btn-outline-info btn-sm" href="' 
             . $url . '/%d" target="_blank" title="View">View Loan</a></td></tr>';
        
        foreach ($list as $record) {
            $member = $record->code . ' ' . $record->name;
            $loanDate = DateHelper::datepicker($record->loan_date);
            $this->_report['html'] .= sprintf($row, $record->id, $member, $loanDate, 
                    $this->amount($record->loan_amount), $sources[$record->loan_source_id], 
                    $types[$record->loan_type_id], $record->account_number, $record->id);
        }
        
        return $this->_report;
    }
    
    /**
     * export report
     */
    public function export()
    {
        $title = 'New Loans Report ' . $this->_report['options']['start_month']
               . ' to ' . $this->_report['options']['end_month'];
        
        $this->_writer = new CsvWriter($title);
        $this->_writer->row(['FISHERMEN WELFARE SOCIETY - KOLLAM']);
        $this->_writer->row([$title]);
        if ($this->fmcSelected())  {
            $this->_writer->row(['FMC : ' . LookupStore::find('fmc', $this->_report['filters']['fmc'])]);
        } else {
            $this->_writer->row(['FMC : ALL']);
        }
        
        if ($this->_report['filters']['source'] > 0)  {
            $this->_writer->row(['SOURCE : ' .LookupStore::find('source', $this->_report['filters']['source'])]);
        } else {
            $this->_writer->row(['SOURCE : ALL']);
        }
        
        if ($this->_report['filters']['type'] > 0)  {
            $this->_writer->row(['TYPE : ' . LookupStore::find('type', $this->_report['filters']['type'])]);
        } else {
            $this->_writer->row(['TYPE : ALL']);
        }
        
        $this->_writer->row(['']);
        
        if ($this->fmcWise()) {
            if ($this->fmcSelected()) {
                $this->_exportFmcWiseSingle();
            }
            
            $this->_exportFmcWise();
        }
        
        if ($this->fmcSelected()) {
            $this->_exportMonthWiseSingle();
        }
        
        $this->_exportMonthWise();
    }
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_report['filters'] = [
            'fmc' => $this->_fmcId,
            'start' => $this->_startDate,
            'end' => $this->_endDate,
            'source' => $this->_sourceId,
            'type' => $this->_typeId,
            'wise' => $this->_wise,
        ];
        
        $this->_report['members'] = [
            'fmc' => $this->_fmcId,
            'year' => 0,
            'month' => 0,
            'source' => $this->_sourceId,
            'type' => $this->_typeId,
        ];
        
        $this->_report['options'] = [];
        $start = DateHelper::getDateTime($this->_startDate);
        $start->setDate($start->format('Y'), $start->format('n'), 1);
        $this->_report['options']['start_date'] = $start->format(DateHelper::MYSQL_DATE);
        $this->_report['options']['start_month'] = $start->format('F Y');
        $start->modify('+10 days');
        
        $end = DateHelper::getDateTime($this->_endDate);
        $days = $end->format('t');
        $end->setDate($end->format('Y'), $end->format('n'), $days);
        $this->_report['options']['end_date'] = $end->format(DateHelper::MYSQL_DATE);
        $this->_report['options']['end_month'] = $end->format('F Y');
        $this->_report['months'] = [];
        $this->_report['total_fmc'] = [];
        $this->_report['total_month'] = [];
        $pairModel = new PairModel();
        $fmcs = $pairModel->fmc();
        $this->_default['default'] = [ 'loans' => 0, 'amount' => 0 ];
        $this->_report['total_fmc'] = array_fill_keys(array_keys($fmcs),$this->_default['default']);
        $this->_report['total'] = $this->_default['default'];

        $period = new \DatePeriod($start, new \DateInterval('P1M'), $end);
        foreach ($period as $item) {
            $year = $item->format('Y');
            if (! isset($this->_report['months'][$year])) {
                $this->_report['months'][$year] = [];
                $this->_report['total_month'][$year] = [];
            }
            
            $month = $item->format('n');
            $this->_report['months'][$year][$month] = $item->format('F Y');
            $this->_report['total_month'][$year][$month] = $this->_default['default'];
        }
        
        $this->_report['params'] = '';
        
        $pattern = '%s `%s` ';
        $this->_tables['loan'] = sprintf($pattern, $this->_model->getTableQuoted(), 'l');
        $this->_tables['member'] = sprintf($pattern, $this->_memberModel->getTableQuoted(), 'm');
        $this->_joins['member'] = 'INNER JOIN ' . $this->_tables['member'] . ' ON `m`.`id` = `l`.`member_id` ';
    }
    
    /**
     * load total
     */
    private function _loadTotal()
    {
        $queryParams = ['fmc' => $this->_fmcId];
        $this->_sql->whereBetween('l.loan_date', $this->_report['options']['start_date'], $this->_report['options']['end_date']);
        $queryParams['start'] = DateHelper::format('Ymd', $this->_report['options']['start_date']);
        $queryParams['end'] = DateHelper::format('Ymd', $this->_report['options']['end_date']);
        if ($this->_fmcId > 0) {
            $sql = $this->_memberModel->getSql();
            $sql->whereId('fmc_id', $this->_fmcId);
            $memberIds = $this->_memberModel->selectIds($sql->getWhere());
            $this->_sql->whereIn('l.member_id', $memberIds);
        }
        
        if ($this->_sourceId > 0) {
            $this->_sql->whereId('l.loan_source_id', $this->_sourceId);
            $queryParams['source'] = $this->_sourceId;
        }
        
        if ($this->_typeId > 0) {
            $this->_sql->whereId('l.loan_type_id', $this->_typeId);
            $queryParams['type'] = $this->_typeId;
        }
        
        $queryParams['wise'] = $this->_wise;
        $this->_where = $this->_sql->getWhere();
        
        $query1 = 'SELECT COUNT(`l`.`id`) AS `total`, SUM(`l`.`loan_amount`) AS `amount` '
                . 'FROM ' . $this->_tables['loan']
                . ' WHERE ' . $this->_where;
        
        $total = $this->_model->queryRow($query1);
        $this->_report['total'] = [
            'loans' => $total->total,
            'amount' => $total->amount,
        ];
        
        $this->_report['params'] = '/?' . http_build_query($queryParams);
    }
    
    private function _fmcWiseReport()
    {
        $this->_report['report'] = [];
        $query = 'SELECT `m`.`fmc_id`, YEAR(`l`.`loan_date`) AS `report_year`, '
               . 'MONTH(`l`.`loan_date`) AS `report_month`, '
               . 'COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
               . 'FROM ' . $this->_tables['loan']
               . $this->_joins['member']
               . ' WHERE ' . $this->_where 
               . ' GROUP BY `m`.`fmc_id`, `report_year`, `report_month`';
        $list = $this->_model->queryRows($query);
        foreach ($list as $item) {
            if (! isset($this->_report['report'][$item->fmc_id])) {
                $this->_report['report'][$item->fmc_id] = [];
            }
            
            if (! isset($this->_report['report'][$item->fmc_id][$item->report_year])) {
                $this->_report['report'][$item->fmc_id][$item->report_year] = [];
            }
            
            $this->_report['report'][$item->fmc_id][$item->report_year][$item->report_month] = [
                'loans' => $item->loans,
                'amount' => $item->amount,
            ];
            
            $this->_report['total_fmc'][$item->fmc_id]['loans'] += $item->loans;
            $this->_report['total_fmc'][$item->fmc_id]['amount'] += $item->amount;
        }
    }
    
    private function _monthWiseReport()
    {
        $this->_report['report'] = [];
        $query = 'SELECT YEAR(`l`.`loan_date`) AS `report_year`, '
               . 'MONTH(`l`.`loan_date`) AS `report_month`, `m`.`fmc_id`, '
               . 'COUNT(`l`.`id`) AS `loans`, SUM(`l`.`loan_amount`) AS `amount` '
               . 'FROM ' . $this->_tables['loan']
               . $this->_joins['member']
               . ' WHERE ' . $this->_where 
               . ' GROUP BY `report_year`, `report_month`, `m`.`fmc_id`';
        $list = $this->_model->queryRows($query);
        foreach ($list as $item) {
            if (! isset($this->_report['report'][$item->report_year])) {
                $this->_report['report'][$item->report_year] = [];
            }
            
            if (! isset($this->_report['report'][$item->report_year][$item->report_month])) {
                $this->_report['report'][$item->report_year][$item->report_month] = [];
            }
            
            $this->_report['report'][$item->report_year][$item->report_month][$item->fmc_id] = [
                'loans' => $item->loans,
                'amount' => $item->amount,
            ];
            
            $this->_report['total_month'][$item->report_year][$item->report_month]['loans'] += $item->loans;
            $this->_report['total_month'][$item->report_year][$item->report_month]['amount'] += $item->amount;
        }
    }
    
    private function _exportFmcWise()
    {   $fmcList = LookupStore::get('fmc');
        $this->_writer->row(['Month', 'Loans', 'Amount']);
        
        foreach ($fmcList as $fmcId => $fmcName) {
            if (! empty($this->_report['report'][$fmcId])) {
                $this->_writer->row([$fmcName]);
                foreach ($this->_report['months'] as $year => $list) {
                    foreach ($list as $month => $monthTitle) {
                        if (isset($this->_report['report'][$fmcId][$year][$month])) {
                            $row = [
                                $monthTitle,
                                $this->_report['report'][$fmcId][$year][$month]['loans'],
                                $this->exportAmount($this->_report['report'][$fmcId][$year][$month]['amount']),
                            ];
                            $this->_writer->row($row);
                        } else {
                            $this->_writer->row([$monthTitle]);
                        }
                    }
                }
                $subtotal = [
                    '', $this->_report['total_fmc'][$fmcId]['loans'],
                    $this->exportAmount($this->_report['total_fmc'][$fmcId]['amount']),
                ];
                $this->_writer->row($subtotal);
                $this->_writer->row(['']);
            }
        }
        
        $this->_writer->row(['Total', $this->_report['total']['loans'], $this->exportAmount($this->_report['total']['amount'])]);
        $this->_writer->finish();
    }
    
    private function _exportFmcWiseSingle()
    {
        $fmcId = $this->_report['filters']['fmc'];
        $this->_writer->row([LookupStore::find('fmc', $fmcId)]);
        $this->_writer->row(['Month', 'Loans', 'Amount']);
        
        foreach ($this->_report['months'] as $year => $list) {
            foreach ($list as $month => $monthTitle) {
                if (isset($this->_report['report'][$fmcId][$year][$month])) {
                    $row = [
                        $monthTitle,
                        $this->_report['report'][$fmcId][$year][$month]['loans'],
                        $this->exportAmount($this->_report['report'][$fmcId][$year][$month]['amount']),
                    ];
                    $this->_writer->row($row);
                } else {
                    $this->_writer->row([$monthTitle]);
                }
            }
        }
        
        $subtotal = [
            'Total', $this->_report['total_fmc'][$fmcId]['loans'],
            $this->exportAmount($this->_report['total_fmc'][$fmcId]['amount']),
        ];
        
        $this->_writer->row([$subtotal]);
        $this->_writer->finish();
    }
    
    private function _exportMonthWise()
    {
        $fmcList = LookupStore::get('fmc');
        $this->_writer->row(['FMC', 'Loans', 'Amount']);
        
        foreach ($this->_report['months'] as $year => $list) {
            if (! empty($this->_report['report'][$year])) {
                foreach ($list as $month => $monthTitle) {
                    if (! empty($this->_report['report'][$year][$month])) {
                        $this->_writer->row([$monthTitle]);
                        foreach ($fmcList as $fmcId => $fmcName) {
                            $row = [$fmcName, '', ''];
                            if (isset($this->_report['report'][$year][$month][$fmcId])) {
                                $row[1] = $this->_report['report'][$year][$month][$fmcId]['loans'];
                                $row[2] = $this->exportAmount($this->_report['report'][$year][$month][$fmcId]['amount']);
                            }
                            $this->_writer->row($row);
                        }

                        $subtotal = [
                            '', $this->_report['total_month'][$year][$month]['loans'],
                            $this->exportAmount($this->_report['total_month'][$year][$month]['amount']),
                        ];
                        $this->_writer->row($subtotal);
                        $this->_writer->row(['']);
                    }
                }
            }
        }

        $this->_writer->row(['Total', $this->_report['total']['loans'], $this->exportAmount($this->_report['total']['amount'])]);
        $this->_writer->finish();
    }
    
    private function _exportMonthWiseSingle()
    {
        $fmcId = $this->_report['filters']['fmc'];
        $this->_writer->row([LookupStore::find('fmc', $fmcId)]);
        $this->_writer->row(['Month', 'Loans', 'Amount']);
        
        foreach ($this->_report['months'] as $year => $list) {
            foreach ($list as $month => $monthTitle) {
                $row = [$monthTitle, '', ''];
                if (isset($this->_report['report'][$year][$month][$fmcId])) {
                    $row[1] = $this->_report['report'][$year][$month][$fmcId]['loans'];
                    $row[2] = $this->exportAmount($this->_report['report'][$year][$month][$fmcId]['amount']);
                    $this->_writer->row($row);
                }
            }
        }

        $this->_writer->row(['Total', $this->_report['total']['loans'], $this->exportAmount($this->_report['total']['amount'])]);
        $this->_writer->finish();
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
