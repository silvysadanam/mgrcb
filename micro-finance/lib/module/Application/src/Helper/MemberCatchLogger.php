<?php
/**
 * Application Helper MemberCatchLogger
 * 
 * @category Application
 * @package  Application\Helper
 */

namespace Application\Helper;

use Model\{MemberCatch, Schedule, Loan};

use Application\Utility\LoanHelper;


class MemberCatchLogger
{
    /**
     * 
     * @var int
     */
    private $_id;
    /**
     * 
     * @var \Model\MemberCatch
     */
    private $_model;
    
    /**
     * 
     * @var string
     */
    private $_date;
    
    /**
     * constructor
     * 
     * @param int $memberId
     */
    public function __construct($memberId = 0)
    {
        $this->_id = $memberId;
        $this->_model = new MemberCatch();
        $this->_date = LoanHelper::reportingDate();
    }
    
    /**
     * set date
     * 
     * @param string $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->_date = $date;
        
        return $this;
    }
    
    /**
     * set member id
     * 
     * @param int $memberId
     * @return $this
     */
    public function setMemberId($memberId)
    {
        $this->_id = $memberId;
        
        return $this;
    }
    
    /**
     * get catch by date
     * 
     * @param string $date
     * @return int
     */
    public function get($date = null)
    {
        if (null === $date) {
            $date = $this->_date;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id)
            ->whereEqual('catch_date', $date);
        $where = $sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            return $this->_model->selectColumn('amount', $where);
        }
        
        return 0;
    }
    
    /**
     * get catch of members
     * 
     * @param array $memberIds
     * @param string $date
     * @return array
     */
    public function members($memberIds, $date = null)
    {
        if (null === $date) {
            $date = $this->_date;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereIn('member_id', $memberIds)
            ->whereEqual('catch_date', $date);
        $where = $sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            return $this->_model->selectPairs(['member_id', 'amount'], $where);
        }
        
        return [];
    }
    
    /**
     * save catch
     * 
     * @param int $amount
     * @param string $date
     * @return null
     */
    public function save($amount, $date = null)
    {
        if (null === $date) {
            $date = $this->_date;
        }
        
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id)
            ->whereEqual('catch_date', $date);
        $where = $sql->getWhere();
        if ($this->_model->selectTotal($where) > 0) {
            $current = $this->_model->selectRow($where);
            if ($amount > $current->amount) {
                $this->_model->updateById(['amount' => $amount], $current->id);
            }
            
            return;
        }
        
        $record = [
            'member_id' => $this->_id,
            'catch_date' => $date,
            'amount' => $amount,
        ];
        
        return $this->_model->insert($record);
    }
    
    /**
     * reset period
     * 
     * @param array $period
     * @return null
     */
    public function resetPeriod($period)
    {
        $loanModel = new Loan();
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id);
        $where = $sql->getWhere();
        if (0 == $loanModel->selectTotal($where)) {
            if ($this->_model->selectTotal($where) > 0) {
                $this->_model->delete($where);
            }
            
            return;
        }
        
        $loanIds = $loanModel->selectIds($where);
        $sql->clear();
        $sql->whereIn('loan_id', $loanIds)
            ->whereOne('has_dcb_entry');
        $whereCatch = $sql->getWhere();
        
        $scheduleModel = new Schedule();
        foreach ($period as $date) {
            $sql->clear();
            $sql->whereId('member_id', $this->_id)
                ->whereEqual('catch_date', $date);
            $whereDate = $sql->getWhere();
            $sql->clear();
            $sql->where($whereCatch)
                ->whereEqual('instalment_date', $date);
            $whereEntry = $sql->getWhere();
            if ($scheduleModel->selectTotal($whereEntry) > 0) {
                $amount = $scheduleModel->selectMax('catch_amount', $whereEntry);
                if ($this->_model->selectTotal($whereDate) > 0) {
                    $entry = $this->_model->selectRow($whereDate);
                    if ($amount != $entry->amount) {
                        $this->_model->updateById(['amount' => $amount], $entry->id);
                    }
                } else {
                    $record = [
                        'member_id' => $this->_id,
                        'catch_date' => $date,
                        'amount' => $amount,
                    ];
                    $this->_model->insert($record);
                }
            } else {
                if ($this->_model->selectTotal($whereDate) > 0) {
                    $this->_model->delete($whereDate, 1);
                }
            }  
        }
    }
    
    /**
     * get period sum
     * 
     * @param string $from
     * @param string $to
     * @return int
     */
    public function periodSum($from, $to)
    {
        $sql = $this->_model->getSql();
        $sql->whereId('member_id', $this->_id)
            ->whereBetween('catch_date', $from, $to);
        $where = $sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            return 0;
        }
        
        return $this->_model->selectSum('amount', $where);
    }
    
    /**
     * get loan catch
     * 
     * @param int $loanId
     * @return int
     */
    public function loanCatch($loanId)
    {
        $loanModel = new Loan();
        $sql = $this->_model->getSql();
        $sql->whereId('loan_id', $loanId)
            ->whereOne('has_dcb_entry');
        $where = $sql->getWhere();
        $scheduleModel = new Schedule();
        if (0 == $scheduleModel->selectTotal($where)) {
            return 0;
        }
        
        $loan = $loanModel->selectFieldsById(['member_id', 'start_date'], $loanId);
        $endDate = $scheduleModel->selectMax('instalment_date', $where);
        $this->_id = $loan->member_id;
        
        return $this->periodSum($loan->start_date, $endDate);
    }
    
    /**
     * get schedule catch
     * 
     * @param int $loanId
     * @return array
     */
    public function scheduleCatch($loanId)
    {
        $loanModel = new Loan();
        $sql = $this->_model->getSql();
        $sql->whereId('loan_id', $loanId)
            ->whereOne('has_dcb_entry');
        $where = $sql->getWhere();
        $scheduleModel = new Schedule();
        if (0 == $scheduleModel->selectTotal($where)) {
            return [];
        }
        
        $loan = $loanModel->selectFieldsById(['member_id', 'start_date'], $loanId);
        $entries = $scheduleModel->selectPairs(['id', 'instalment_date'], $where, 'instalment_date');
        
        $sql->clear();
        $sql->whereId('member_id', $loan->member_id)
            ->whereBetween('catch_date', $loan->start_date, end($entries));
        $list = $this->_model->select($sql->getWhere(), 'catch_date');
        
        $catch = array_fill_keys(array_keys($entries), 0);
        foreach ($list as $item) {
            $key = array_search($item->catch_date, $entries);
            $catch[$key] = $item->amount;
        }
        
        return $catch;
    }
    
}
