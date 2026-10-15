<?php
/**
 * Application LoanTotal Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Shared\Utility;

use Model\{Member, Fmc, Boat, MemberCard, MemberDocument, MemberPhoto, Account};

use Shared\Model\PairModel;
use Shared\Constant\MemberType;
use Shared\Constant\MembershipType;
use Web\View\Support\LookupStore;
use Web\Utility\DateHelper;


class MemberReport
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
     * @var \Model\LoanSummary 
     */
    private $_loanSummaryModel;

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
    private $_mtypeID = 0;
    private $_name = '';
    private $_statusId = 0;
    private $_parishName = '';
    private $_mobile = 0;
    private $_location = '';
    private $list;
    /**
     * 
     * @var int
     */
    private $_typeId = 0;
    
    public function __construct()
    {
        $this->_model = new Member();
        $this->_fmcModel = new Fmc();
        $this->_sql = $this->_model->getSql();
    }
    
    
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }
    
    public function setParishName($name)
    {
        $this->_parishName = $name;
        return $this;
    }
    
     public function setLocation($name)
    {
        $this->_location = $name;
        return $this;
    }
    
    /**
     * set emd date
     * 
     * @param string $date
     * @return $this
     */
    public function setMembershipTypeId($mtypeID)
    {
        $this->_mtypeID = $mtypeID;
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
    
     public function setMobile($id)
    {
        $this->_mobile = $id;
        return $this;
    }
    
    /**
     * set source id
     * 
     * @param int $id
     * @return $this
     */
    public function setStatus($id)
    {
        $this->_statusId = $id;
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
        //$this->_loadTotal();
        //$this->_loadReport();
        
        return $this->list;
    }
    
    
    /**
     * preload
     */
    private function _preload()
    {
        $sql = $this->_model->getSql();
        if ($this->_statusId > 0) {
                $sql->whereId('m.disabled', $this->_statusId);
        }
        
        if (MemberType::MEMBER == $this->_mtypeID) {
            $sql->whereId('m.member_type_id', MemberType::MEMBER);
        } elseif (MemberType::DONOR == $this->_mtypeID) {
            $sql->whereId('m.member_type_id', MemberType::DONOR);
        } 
        if ($this->_mtypeID > 0) {
            if (in_array($this->_typeId, [MembershipType::LIFEMEMBER, MembershipType::ORDMEMBER])) {
                $sql->whereId('m.membership_type_id', $this->_mtypeID);
            }
        }
        if (isset($this->_typeId) > 0) {
            if (in_array($this->_typeId, [MemberType::MEMBER, MemberType::DONOR])) {
                $sql->whereId('m.member_type_id', $this->_typeId);
            }
        }
        if ($this->_mobile > 0) {
                $sql->whereId('m.mobile', $this->_mobile);
        }
        $name     = trim($this->_name ?? '');
        $parish   = trim($this->_parishName ?? '');
        $location = trim($this->_location ?? '');
        $mobile   = trim($this->_mobile ?? '');

        if ($name !== '') {
            $sql->whereLike('m.name', $name);
        }

        if ($location !== '') {
            $sql->whereLike('m.location', $location);
        }

        if ($parish !== '') {
            $sql->whereLike('m.parish', $parish);
        }

        $where = $sql->getWhere();
        
        $query = 'FROM ' . $this->_model->getTableQuoted() . ' `m` '
               . 'INNER JOIN ' . $this->_fmcModel->getTableQuoted() 
               . ' `f` ON `f`.`id` = `m`.`fmc_id`';
        if (! empty($where)) {
            $query .= ' WHERE ' . $where;
        }
        
        $query1 = 'SELECT COUNT(`m`.`id`) ' . $query;
        $total = $this->_model->queryTotal($query1);
        if ($total > 0) {
            //$paging = $this->getPaging($total);
            $query2 = 'SELECT `m`.`id`, `m`.`name`, `m`.`code`,`m`.`mobile`, `m`.`location`, `m`.`parish`, `m`.`email`, `m`.`email`, `m`.`address`, `m`.`membership_fee`,  `m`.`date_of_birth`,`m`.`approved_date`,'
                    . '`m`.`member_type_id` ' . $query 
                    . ' ORDER BY `m`.`code`, `m`.`name` ';
            $this->list = $this->_model->queryRows($query2);
        }
        
    }
    
    /**
     * load total
     */
    private function _loadTotal()
    {
        $this->_sql->whereNotIn('l.loan_status_id', [LoanHelper::STATUS_REFINANCED, LoanHelper::STATUS_RESCHEDULED]);
        $queryParams = ['fmc' => $this->_fmcId];
        if ('' != $this->_startDate || '' != $this->_endDate) {
            if ('' != $this->_startDate && '' != $this->_endDate) {
                if ($this->_startDate == $this->_endDate) {
                    $this->_sql->whereEqual('l.loan_date', $this->_startDate);
                    $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
                } else {
                    $this->_sql->whereBetween('l.loan_date', $this->_startDate, $this->_endDate);
                    $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
                    $queryParams['end'] = DateHelper::format('Ymd', $this->_endDate);
                }
            } elseif ('' != $this->_startDate) {
                $this->_sql->whereGreater('l.loan_date', $this->_startDate, true);
                $queryParams['start'] = DateHelper::format('Ymd', $this->_startDate);
            } else {
                $this->_sql->whereLess('l.loan_date', $this->_endDate, true);
                $queryParams['end'] = DateHelper::format('Ymd', $this->_endDate);
            }
        }
        
        if ($this->_fmcId > 0) {
            $sql = $this->_memberModel->getSql();
            $sql->whereId('fmc_id', $this->_fmcId);
            $memberIds = $this->_memberModel->selectIds($sql->getWhere());
            $this->_sql->whereIn('l.member_id', $memberIds);
            //$this->_sql->whereId('m.fmc_id', $this->_fmcId);
        }
        
        if ($this->_sourceId > 0) {
            $this->_sql->whereId('l.loan_source_id', $this->_sourceId);
            $queryParams['source'] = $this->_sourceId;
        }
        
        if ($this->_typeId > 0) {
            $this->_sql->whereId('l.loan_type_id', $this->_typeId);
            $queryParams['type'] = $this->_sourceId;
        }
        
        $this->_where['all'] = $this->_sql->getWhere();
        $this->_sql->whereZero('l.closed');
        $this->_where['active'] = $this->_sql->getWhere();
        $this->_sql->clear();
        if (! empty($this->_where['all'])) {
            $this->_sql->where($this->_where['all']);
        }
        $this->_sql->whereOne('l.closed');
        $this->_where['closed'] = $this->_sql->getWhere();
        
        $query1 = 'SELECT COUNT(`l`.`id`) AS `total`, SUM(`l`.`loan_amount`) AS `amount`, '
                . 'COUNT(DISTINCT `l`.`member_id`) AS `members` '
                . 'FROM ' . $this->_tables['loan'];
        if ($this->_fmcId > 0) {
            //$query1 .= $this->_joins['member'];
        }
        
        if (! empty($this->_where['all'])) {
            $query1 .= 'WHERE ' . $this->_where['all'];
        }
        
        $total = $this->_model->queryRow($query1);
        $this->_report['total'] = [
            'loans' => $total->total,
            'amount' => $total->amount,
            'members' => $total->members,
            'closed' => 0,
        ];
        
        $this->_sql->clear();
        $query2 = 'SELECT COUNT(DISTINCT `l`.`member_id`) AS `members` '
                . 'FROM ' . $this->_tables['loan'];
        
        if ($this->_fmcId > 0) {
           // $query2 .= $this->_joins['member'];
        }
        
        $query2 .= 'WHERE ' . $this->_where['closed'];
        $this->_report['total']['closed'] = $this->_model->queryTotal($query2);
        
        $this->_report['summary'] = $this->_default['default'];
        
        $this->_report['params'] = '/?' . http_build_query($queryParams);
    }
    
    /**
     * load report
     */
    private function _loadReport()
    {
        $pairModel = new PairModel();
        $titles =  $pairModel->loanStatus();
        $this->_report['status_list'] = [ 0 => 'Active' ];
        $this->_report['status'] = [
            0 => $this->_default['default'],
        ];
        
        /*
        $status = [
            LoanHelper::STATUS_WRITTEN_OFF, LoanHelper::STATUS_REFINANCED,
            LoanHelper::STATUS_RESCHEDULED, LoanHelper::STATUS_DELINQUENT,
            LoanHelper::STATUS_PRECLOSED, LoanHelper::STATUS_INACTIVE,
        ];
         * 
         */
        
        $status = [
            LoanHelper::STATUS_WRITTEN_OFF, LoanHelper::STATUS_DELINQUENT,
            LoanHelper::STATUS_PRECLOSED, LoanHelper::STATUS_INACTIVE,
        ];
        
        foreach ($status as $item) {
            $this->_report['status'][$item] = $this->_default['default'];
            $this->_report['status_list'][$item] = $titles[$item];
        }
        
        $this->_getStatusTotal(0, $this->_where['active']);
        
        foreach ($status as $key) {
            $this->_sql->clear();
            $this->_sql->where($this->_where['closed'])
                       ->whereId('l.loan_status_id', $key);
            $this->_getStatusTotal($key, $this->_sql->getWhere());
        }
        
        $this->_report['status_list'][7] = 'Closed at end of tenure';
        $this->_report['status'][7] = $this->_default['default'];
        $this->_sql->clear();
        $this->_sql->where($this->_where['closed'])
                   ->whereZero('l.loan_status_id');
        $this->_getStatusTotal($key, $this->_sql->getWhere());
    }
    
    /**
     * get status total
     * 
     * @param int $status
     * @param string $where
     * @return null
     */
    private function _getStatusTotal($status, $where)
    {  
        $query = $this->_default['query'] . ' WHERE ' . $where;
        $summary = $this->_model->queryRow($query);
        if (0 == $summary->loans) {
            return;
        }
        
        foreach ($this->_default['direct'] as $direct) {
            $this->_report['status'][$status][$direct] = $summary->{$direct};
            $this->_report['summary'][$direct] += $summary->{$direct};
        }
        
        foreach ($this->_default['sum'] as $field => $items) {
            foreach ($items as $item) {
                $this->_report['status'][$status][$field] += $summary->{$item};
            }
            $this->_report['summary'][$field] += $this->_report['status'][$status][$field];
        }
        
        foreach ($this->_default['total_paid'] as $key) {
            $this->_report['status'][$status]['total_paid'] += $this->_report['status'][$status][$key];
            $this->_report['summary']['total_paid'] += $this->_report['status'][$status][$key];
        }
        
        $query2 = $this->_default['member'] . ' WHERE ' . $where;
        $members = $this->_model->queryTotal($query2);
        $this->_report['status'][$status]['members'] = $members;
        $this->_report['summary']['members'] += $members;
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
