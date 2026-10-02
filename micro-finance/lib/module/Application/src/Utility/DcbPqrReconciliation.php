<?php
/**
 * Application DcbPqrReconciliation Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Application\Utility\LoanHelper;

use Model\{Loan, Member, Schedule, Pqam};

use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class DcbPqrReconciliation
{
    const TOTAL = 'total';
    const ACTIVE = 'active';
    const CLOSED = 'closed';
    const TENURE_CLOSED = 'tenure_closed';
    const PRE_CLOSED = 'pre_closed';
    const PQR_PRE_CLOSED = 'pqr_pre_closed';
    const UPCOMING = 'upcoming';
    const PENDING = 'pending';
    const CLOSED_OD = 'closed_od';
    const WRITTEN_OFF = 'written_off';
    const RESCHEDULED = 'rescheduled';
    const REFINANCED = 'refinanced';
    const PQR = 'pqr';
    const INACTIVE = 'inactive';
    
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
     * @var \Model\Schedule 
     */
    private $_scheduleModel;
    
    /**
     *
     * @var \Model\Pqam 
     */
    private $_pqamModel;

    /**
     * 
     * @var \Web\Database\Sql
     */
    private $_sql;
    
    /**
     * 
     * @var array
     */
    private $_dcb = [];
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var string
     */
    private $_reportingDate;
    
    /**
     * 
     * @var array
     */
    private $_default = [];
    
    /**
     * 
     * @var array
     */
    private $_fmc = [];
    
    /**
     * 
     * @var array
     */
    private $_sources = [];
    
    /**
     * 
     * @var array
     */
    private $_types = [];
    
    /**
     * 
     * @var array
     */
    private $_loanStatus = [];
    
    /**
     * 
     * @var array
     */
    private $_keys = [];
    
    /**
     * 
     * @var array
     */
    private $_loans = [];
    
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
    private $_groups = [];
    
    /**
     * 
     * @var array
     */
    private $_where = [];
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_memberModel = new Member();
        $this->_scheduleModel = new Schedule();
        $this->_pqamModel = new Pqam();
        $this->_sql = $this->_model->getSql();
        $this->_reportingDate = LoanHelper::reportingDate();
    }
    
    public function run()
    {
        $this->_preload();
        $this->_summary();
        $this->_upcoming();
        $this->_current();
        $this->_preclosed();
        $this->_mainReport();
        $this->_typeReport();
        $this->_report['dcb'] = $this->_dcb;
        $this->_reconciled();
        $this->_alternate();
        //$this->_print();
    }
    
    public function reportingDate()
    {
        return DateHelper::datepicker($this->_reportingDate);
    }
    
    public function today()
    {
        return DateHelper::datepicker();
    }
    
    public function report()
    {
        return $this->_report;
    }
    
    public function minus($value)
    {
        if ($value > 0) {
            return '-' . $value;
        }
                                                       
        return 0;
    }
    
    public function title($title)
    {
        if (false === strpos($title, ' ')) {
            return $title;
        }
        
        $parts = preg_split('/\s+/', $title);
        if (count($parts) > 2) {
            $title = $parts[0] . '<br>';
            array_shift($parts);
            $title .= implode(' ',$parts);
            
            return $title;
        }
        
        if (strlen($parts[0]) < 3 || strlen($parts[1]) < 3) {
            return $title;
        }
        
        return implode('<br>', $parts);
    }
    
    private function _print()
    {
        echo '<pre>';
        print_r($this->_report);
        exit;
    }
    
    private function _preload()
    {
        $this->_keys = [
            self::TOTAL, self::ACTIVE, self::CLOSED, self::TENURE_CLOSED,
            self::PRE_CLOSED, self::PQR_PRE_CLOSED, self::UPCOMING, self::CLOSED_OD,
            self::PENDING, self::WRITTEN_OFF, self::RESCHEDULED, self::REFINANCED,
            self::PQR, self::INACTIVE,
        ];
        
        $this->_default = array_fill_keys($this->_keys, 0);
        $this->_dcb = $this->_default;
        $this->_loans = [
            self::PENDING => [],
            self::PRE_CLOSED => [],
            self::PQR_PRE_CLOSED => [],
            self::UPCOMING => [],
        ];
        
        $this->_loadIds();
        $this->_loanStatus = [
            self::WRITTEN_OFF => LoanHelper::STATUS_WRITTEN_OFF, 
            self::REFINANCED => LoanHelper::STATUS_REFINANCED,
            self::RESCHEDULED => LoanHelper::STATUS_RESCHEDULED, 
            self::CLOSED_OD => LoanHelper::STATUS_DELINQUENT, 
            self::PRE_CLOSED => LoanHelper::STATUS_PRECLOSED,
            self::INACTIVE => LoanHelper::STATUS_INACTIVE,
        ];
        
        $pattern = '%s `%s` ';
        $this->_tables['loan'] = sprintf($pattern, $this->_model->getTableQuoted(), 'l');
        $this->_tables['schedule'] = sprintf($pattern, $this->_scheduleModel->getTableQuoted(), 's');
        $this->_tables['pqam'] = sprintf($pattern, $this->_pqamModel->getTableQuoted(), 'p');
        $this->_tables['member'] = sprintf($pattern, $this->_memberModel->getTableQuoted(), 'm');
        
        $join = 'INNER JOIN %s ON `%s`.`id` = `%s`.`%s_id` ';
        $this->_joins['sl'] = sprintf($join, $this->_tables['loan'], 'l', 's', 'loan');
        $this->_joins['lm'] = sprintf($join, $this->_tables['member'], 'm', 'l', 'member');
        $this->_joins['pl'] = sprintf($join, $this->_tables['loan'], 'l', 'p', 'loan');
        
        $this->_groups = [
            'fmc' => ' GROUP BY `m`.`fmc_id`',
            'source' => ' GROUP BY `loan_source_id`',
        ];
        
        $this->_sql->clear();
        $this->_sql->whereEqual('pqam_date', $this->_reportingDate);
        $this->_where['pqam_date'] = $this->_sql->getWhere();
        $this->_where['pqam_date_join'] = str_replace('`pqam_date`', '`p`.`pqam_date`', $this->_where['pqam_date']);
    }
    
    private function _loadIds()
    {
        $fmcIds = array_keys(LookupStore::get('fmc'));
        sort($fmcIds, SORT_NUMERIC);
        $this->_fmc = $fmcIds;
        
        $typeIds = array_keys(LookupStore::get('type'));
        sort($typeIds, SORT_NUMERIC);
        $this->_types = $typeIds;
        
        $sourceIds = array_keys(LookupStore::get('source'));
        sort($sourceIds, SORT_NUMERIC);
        $this->_sources = $sourceIds;
    }
    
    private function _summary()
    {
        $this->_dcb[self::TOTAL] = $this->_model->selectTotal();
        $this->_sql->clear();
        $this->_sql->whereZero('closed');
        $this->_loanCount(self::ACTIVE);
        
        $this->_sql->whereOne('closed');
        $this->_loanCount(self::CLOSED);
        $this->_sql->whereOne('closed')
                   ->whereZero('loan_status_id');
        $this->_loanCount(self::TENURE_CLOSED);

        foreach ($this->_loanStatus as $key => $statusId) {
            $this->_sql->whereOne('closed')
                       ->whereId('loan_status_id', $statusId);
            $this->_loanCount($key);
        }

        $this->_sql->whereGreater('start_date', $this->_reportingDate);
        $this->_loanCount(self::UPCOMING);

        $this->_dcb[self::PQR] = $this->_pqamModel->selectTotal($this->_where['pqam_date']);
    }
    
    private function _loanCount($key)
    {
        $this->_dcb[$key] = $this->_model->selectTotal($this->_sql->getWhere());
        $this->_sql->clear();
    }
    
    
    private function _customTotal($key, $query)
    {
        $this->_dcb[$key] = $this->_model->queryTotal($query);
    }
    
    private function _loanIds($key, $query)
    {
        $this->_loans[$key] = $this->_model->queryColumns($query);
    }
    
    private function _current()
    {
        if (0 == $this->_dcb[self::ACTIVE]) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereEqual('s.instalment_date', $this->_reportingDate)
                   ->whereZero('s.has_dcb_entry')
                   ->whereZero('l.closed');
        $where = $this->_sql->getWhere();
        $query1 = 'SELECT COUNT(DISTINCT `s`.`loan_id`) FROM ' . $this->_tables['schedule']
                . $this->_joins['sl'] . 'WHERE ' . $where;
        $this->_customTotal(self::PENDING, $query1);
        
        if (0 == $this->_dcb[self::PENDING]) {
            return;
        }
        
        $query2 = 'SELECT DISTINCT `s`.`loan_id` FROM ' . $this->_tables['schedule']
                . $this->_joins['sl'] . 'WHERE ' . $where;
        $this->_loanIds(self::PENDING, $query2);
    }
    
    private function _upcoming()
    {
        if (0 == $this->_dcb[self::UPCOMING]) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereGreater('start_date', $this->_reportingDate);
        $this->_loans[self::UPCOMING] = $this->_model->selectIds($this->_sql->getWhere());
    }
    
    private function _preclosed()
    {
        if (0 == $this->_dcb[self::PRE_CLOSED]) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereOne('closed')
                   ->whereId('loan_status_id', LoanHelper::STATUS_PRECLOSED);
        $this->_loans[self::PRE_CLOSED] = $this->_model->selectIds($this->_sql->getWhere());

        $this->_sql->clear();
        $this->_sql->whereEqual('pqam_date', $this->_reportingDate)
                   ->whereIn('loan_id', $this->_loans[self::PRE_CLOSED]);
        $where = $this->_sql->getWhere();
        $this->_dcb[self::PQR_PRE_CLOSED] = $this->_pqamModel->selectTotal($where);
        $this->_loans[self::PQR_PRE_CLOSED] = $this->_pqamModel->selectColumns('loan_id', $where);
    }
    
    private function _mainReport()
    {
        $this->_report['fmc'] = [];
        foreach ($this->_fmc as $fmcId) {
            $this->_report['fmc'][$fmcId] = $this->_default;
        }
        
        $this->_report['source'] = [];
        foreach ($this->_sources as $sourceId) {
            $this->_report['source'][$sourceId] = $this->_default;
        }
        
        $query = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) FROM ' . $this->_tables['loan']
                . $this->_joins['lm'] . $this->_groups['fmc'];
        $this->_fmcGroup(self::TOTAL, $query);
        
        $this->_sql->whereZero('l.closed');
        $query = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) FROM ' . $this->_tables['loan']
                . $this->_joins['lm'] . ' WHERE ' . $this->_sql->getWhere()
                . $this->_groups['fmc'];
        $this->_fmcGroup(self::ACTIVE, $query);
        
        $this->_sourceReport(self::TOTAL, null);
        $this->_sourceReport(self::ACTIVE, '`closed` =0');
        $this->_pqrReport();
        $this->_reportClosed();

        $this->_loanReport(self::PENDING);
        $this->_loanReport(self::UPCOMING);
    }
    
    private function _fmcGroup($key, $query)
    {
        $list = $this->_model->queryPairs($query);
        foreach ($list as $id => $count) {
            $this->_report['fmc'][$id][$key] = $count;
        }
        $this->_sql->clear();
    }
    
    private function _sourceReport($key, $where = null)
    {
        $query = 'SELECT `loan_source_id`, COUNT(`id`) FROM ' . $this->_model->getTableQuoted();
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        $query .= $this->_groups['source'];
        $list = $this->_model->queryPairs($query);
        foreach ($list as $id => $count) {
            $this->_report['source'][$id][$key] = $count;
        }
    }
    
    private function _pqrReport()
    {
        if (0 == $this->_dcb[self::PQR]) {
            return;
        }
        
        $query = 'SELECT `m`.`fmc_id`, COUNT(`p`.`loan_id`) FROM ' . $this->_tables['pqam']
               . $this->_joins['pl'] . $this->_joins['lm']
               . ' WHERE ' . $this->_where['pqam_date_join'] . $this->_groups['fmc'];
        $this->_fmcGroup(self::PQR, $query);
        
        $query1 = 'SELECT `l`.`loan_source_id`, COUNT(`l`.`id`) FROM ' .  $this->_tables['pqam']
                . $this->_joins['pl'] . ' WHERE ' . $this->_where['pqam_date_join'] 
                . $this->_groups['source'];
        $list1 = $this->_model->queryPairs($query1);
        foreach ($list1 as $id => $count) {
            $this->_report['source'][$id][self::PQR] = $count;
        }
    }
    
    private function _reportClosed()
    {
        if (0 == $this->_dcb[self::CLOSED]) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereOne('l.closed')
                   ->whereZero('l.loan_status_id');
        $query = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) FROM ' . $this->_tables['loan']
                . $this->_joins['lm']  . ' WHERE ' . $this->_sql->getWhere()
                . $this->_groups['fmc'];
        $this->_fmcGroup(self::TENURE_CLOSED, $query);
        
        $this->_sql->clear();
        $this->_sql->whereOne('closed')
                   ->whereZero('loan_status_id');
        $this->_sourceReport(self::TENURE_CLOSED, $this->_sql->getWhere());
        
        foreach ($this->_loanStatus as $key => $statusId) {
            if ($this->_dcb[$key] > 0) {
                $this->_sql->clear();
                $this->_sql->whereOne('l.closed')
                           ->whereId('l.loan_status_id', $statusId);
                $query1 = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) FROM ' . $this->_tables['loan']
                        . $this->_joins['lm'] . ' WHERE ' . $this->_sql->getWhere()
                        . $this->_groups['fmc'];
                $this->_fmcGroup($key, $query1);
                
                $this->_sql->clear();
                $this->_sql->whereOne('closed')
                           ->whereId('loan_status_id', $statusId);
                $this->_sourceReport($key, $this->_sql->getWhere());
            }
        }
        
        $this->_loanReport(self::PRE_CLOSED);
        $this->_loanReport(self::PQR_PRE_CLOSED);
    }
    
    private function _loanReport($key)
    {
        if (0 == $this->_dcb[$key]) {
            return;
        }
        
        $this->_sql->clear();
        $this->_sql->whereIn('l.id', $this->_loans[$key]);
        $query = 'SELECT `m`.`fmc_id`, COUNT(`l`.`id`) FROM ' . $this->_tables['loan']
               . $this->_joins['lm'] . ' WHERE ' . $this->_sql->getWhere()
               . $this->_groups['fmc'];
        $this->_fmcGroup($key, $query);
        
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_loans[$key]);
        $this->_sourceReport($key, $this->_sql->getWhere());
    }
    
    private function _typeReport()
    {
        $default = array_fill_keys($this->_types, 0);
        $this->_report['type'] = [];
        $this->_report['type']['total'] = [
            'dcb' => $default,
            'pqr' => $default,
            'fmc_dcb' => [],
            'fmc_pqr' => [],
        ];
        
        $this->_report['type']['fmc'] = [];
        $this->_report['type']['pqr'] = [];
        foreach ($this->_fmc as $fmcId) {
            $this->_report['type']['fmc'][$fmcId] = $default;
            $this->_report['type']['pqr'][$fmcId] = $default;
            $this->_report['type']['total']['fmc_dcb'][$fmcId] = 0;
            $this->_report['type']['total']['fmc_pqr'][$fmcId] = 0;
        }
        
        $query = 'SELECT `m`.`fmc_id`, `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans` FROM '
               . $this->_tables['loan'] . $this->_joins['lm']
               . 'GROUP BY `m`.`fmc_id`, `l`.`loan_type_id`';
        $list = $this->_model->queryRows($query);
        foreach ($list as $item) {
            $this->_report['type']['fmc'][$item->fmc_id][$item->loan_type_id] = $item->loans;
            $this->_report['type']['total']['dcb'][$item->loan_type_id] += $item->loans;
            $this->_report['type']['total']['fmc_dcb'][$item->fmc_id] += $item->loans;
        }
        
        if ($this->_dcb[self::PQR] > 0) {
            $query1 = 'SELECT `m`.`fmc_id`, `l`.`loan_type_id`, COUNT(`l`.`id`) AS `loans` FROM '
                    . $this->_tables['pqam'] . $this->_joins['pl'] . $this->_joins['lm']
                    . 'WHERE ' . $this->_where['pqam_date_join'] . ' GROUP BY `m`.`fmc_id`, `l`.`loan_type_id`';
            $list1 = $this->_model->queryRows($query1);
            foreach ($list1 as $item1) {
                $this->_report['type']['pqr'][$item1->fmc_id][$item1->loan_type_id] = $item1->loans;
                $this->_report['type']['total']['pqr'][$item1->loan_type_id] += $item1->loans;
                $this->_report['type']['total']['fmc_pqr'][$item1->fmc_id] += $item1->loans;
            }
        }
    }
    
    private function _reconciled()
    {
        $sum = [
            self::PQR, self::TENURE_CLOSED, self::PRE_CLOSED, self::UPCOMING, self::PENDING, 
            self::WRITTEN_OFF, self::RESCHEDULED, self::REFINANCED, self::INACTIVE, self::CLOSED_OD,
        ];
        
        $deduct = self::PQR_PRE_CLOSED;
        
        $fmcDefault = array_fill_keys($this->_fmc, 0);
        $sourceDefault = array_fill_keys($this->_sources, 0);
        $this->_report['reconciled'] = [
            'total' => ['fmc' => 0, 'source' => 0],
            'fmc' => $fmcDefault,
            'source' => $sourceDefault,
        ];
        
        foreach ($this->_report['fmc'] as $fmcId => $list) {
            $total = 0;
            foreach ($sum as $field) {
                $total += $list[$field];
            }
            $total -= $list[$deduct];
            $this->_report['reconciled']['fmc'][$fmcId] = $total;
            $this->_report['reconciled']['total']['fmc'] += $total;
        }
        
        foreach ($this->_report['source'] as $sourceId => $list) {
            $total = 0;
            foreach ($sum as $field) {
                $total += $list[$field];
            }
            $total -= $list[$deduct];
            $this->_report['reconciled']['source'][$sourceId] = $total;
            $this->_report['reconciled']['total']['source'] += $total;
        }
    }
    
    private function _alternate()
    {
        $fmcDefault = array_fill_keys($this->_fmc, ['total' => 0, 'reconciled' => 0]);
        $sourceDefault = array_fill_keys($this->_sources, ['total' => 0, 'reconciled' => 0]);
        $this->_report['alternate'] = [
            'total' => ['fmc' => 0, 'source' => 0],
            'reconciled' => ['fmc' => 0, 'source' => 0],
            'fmc' => $fmcDefault,
            'source' => $sourceDefault,
        ];
        
        foreach ($this->_report['fmc'] as $fmcId => $list) {
            $total = $list[self::ACTIVE] + $list[self::CLOSED_OD] + $list[self::INACTIVE];
            $this->_report['alternate']['fmc'][$fmcId]['total'] = $total;
            $this->_report['alternate']['total']['fmc'] += $total;
            $reconciled = $list[self::PQR] + $list[self::UPCOMING] + $list[self::PENDING]
                        - $list[self::PQR_PRE_CLOSED];
            $this->_report['alternate']['fmc'][$fmcId]['reconciled'] = $reconciled;
            $this->_report['alternate']['reconciled']['fmc'] += $reconciled;
        }
        
        foreach ($this->_report['source'] as $sourceId => $list) {
            $total = $list[self::ACTIVE] + $list[self::CLOSED_OD] + $list[self::INACTIVE];
            $this->_report['alternate']['source'][$sourceId]['total'] = $total;
            $this->_report['alternate']['total']['source'] += $total;
            $reconciled = $list[self::PQR] + $list[self::UPCOMING] + $list[self::PENDING]
                        - $list[self::PQR_PRE_CLOSED];
            $this->_report['alternate']['source'][$sourceId]['reconciled'] = $reconciled;
            $this->_report['alternate']['reconciled']['source'] += $reconciled;
        }
    }
    
}
