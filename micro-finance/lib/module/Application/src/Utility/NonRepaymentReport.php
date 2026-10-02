<?php
/**
 * Application NonRepayment Report Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;

use Model\{Loan, Schedule, Member, LoanSummary, Fmc};

use Web\Utility\DateHelper;


class NonRepaymentReport
{
    const PAYMENT_ANY = 0;
    const PAYMENT_REQUIRED = 1;
    const NO_PAYMENT = 2;
    
    const ZERO = '0.00';

    /**
     *
     * @var \Model\Loan 
     */
    private $_model;
    
    /**
     *
     * @var \Model\Schedule 
     */
    private $_scheduleModel;
    
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
     * @var int
     */
    private $_fmcId = 0;
    
    /**
     * 
     * @var int
     */
    private $_instalment = 0;
    
    /**
     * 
     * @var int
     */
    private $_payment = 0;
    
    /**
     * 
     * @var int
     */
    private $_ageing = 0;
    
    /**
     * 
     * @var int
     */
    private $_unpaid = 0;
    
    /**
     * 
     * @var array
     */
    private $_instalmentMap;
    
    /**
     * 
     * @var array
     */
    private $_ageingMap;
    
    /**
     * 
     * @var array
     */
    private $_unpaidMap;
    
    /**
     * 
     * @var int
     */
    private $_loanId;
    
    /**
     * 
     * @var array
     */
    private $_report = [];
    
    /**
     * 
     * @var array
     */
    private $_totalUnpaid = [];
    
    /**
     * 
     * @var string
     */
    private $_fmc = 'All';
    
    public function __construct()
    {
        $this->_model = new Loan();
        $this->_scheduleModel = new Schedule();
        $this->_memberModel = new Member();
        $this->_loanSummaryModel = new LoanSummary();
        $this->_sql = $this->_model->getSql();
    }
    
    /**
     * run report
     * 
     * @return null
     */
    public function run()
    {
        $this->_report = [
            'total' => 0,
            'list' => [],
            'ids' => [],
            'schedule' => [],
            'members' => [],
            'summary' => [],
        ];
        
        $this->_preload();
        
        $this->_loadLoanIds();
        if (empty($this->_report['ids'])) {
            return;
        }
        
        $this->_report['total'] = count($this->_report['ids']);
        $this->_sql->clear();
        $this->_sql->whereIn('id', $this->_report['ids']);
        $where = $this->_sql->getWhere();
        $columns = ['id', 'member_id', 'loan_date', 'loan_amount', 'tenure', 'account_number'];
        $this->_report['list'] = $this->_model->selectFields($columns, $where, 'loan_date DESC');
        
        $memberIds = $this->_model->selectDistinct('member_id', $where);
        $this->_sql->clear();
        $this->_sql->whereIn('id', $memberIds);
        $where2 = $this->_sql->getWhere();
        $members = $this->_memberModel->selectFields(['id', 'code', 'name', 'fmc_id'], $where2);
        $this->_report['members'] = $this->_memberModel->getIndexedList($members);
        
        $this->_sql->clear();
        $this->_sql->whereIn('loan_id', $this->_report['ids']);
        $summary = $this->_loanSummaryModel->selectFields(['loan_id', 'ageing', 'balance_actual_principal_interest_outstanding'], $this->_sql->getWhere());
        $this->_report['summary'] = $this->_model->getIndexedList($summary, 'loan_id');
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
     * set instalment filter
     * 
     * @param int $instalment
     * @return $this
     */
    public function setInstalmentFilter($instalment)
    {
        $this->_instalment = $instalment;
        return $this;
    }
    
    /**
     * set payment filter
     * 
     * @param int $payment
     * @return $this
     */
    public function setPaymentFilter($payment)
    {
        $this->_payment = $payment;
        return $this;
    }
    
    /**
     * set ageing filter
     * 
     * @param int $ageing
     * @return $this
     */
    public function setAgeingFilter($ageing)
    {
        $this->_ageing = $ageing;
        return $this;
    }
    
    /**
     * set ageing filter
     * 
     * @param int $unpaid
     * @return $this
     */
    public function setUnpaidFilter($unpaid)
    {
        $this->_unpaid = $unpaid;
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
     * get total
     * 
     * @return int
     */
    public function total()
    {
        return $this->_report['total'];
    }
    
    /**
     * check report
     * 
     * @return boolean
     */
    public function hasReport()
    {
        return $this->_report['total'] > 0;
    }
    
    /**
     * get fmc
     */
    public function fmc()
    {
        return $this->_fmc;
    }
    
    /**
     * preload
     */
    private function _preload()
    {
        $this->_instalmentMap = [
            0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,  6 => 6,
            7 => 12,  8 => 18, 9 => 24, 10 => 30, 11 => 36,
        ];
        
        $this->_ageingMap = [
            0 => 0, 1 => 30, 2 => 29, 3 => 59, 4 => 89, 5 => 119, 6 => 179,
            7 => 209, 8 => 269, 9 => 365,
        ];
        
        $this->_unpaidMap = [
            1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,  6 => 6,
            7 => 12,  8 => 18, 9 => 24, 10 => 30, 11 => 36,
        ];
    }
    
    /**
     * load loan ids
     * 
     * @return null
     */
    private function _loadLoanIds()
    {
        $this->_sql->clear();
        $this->_sql->whereZero('closed');
        if ($this->_fmcId > 0) {
            $memberIds = $this->_memberModel->selectIds(['fmc_id' => $this->_fmcId]);
            $this->_sql->whereIn('member_id', $memberIds);
            $fmcModel = new Fmc();
            $this->_fmc = $fmcModel->selectColumnById('name', $this->_fmcId);
        }
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_model->selectTotal($where)) {
            return;
        }
        
        $loanIds = $this->_model->selectIds($where);
        $this->_sql->clear();
        $this->_sql->whereIn('loan_id', $loanIds)
                   ->whereOne('has_dcb_entry')
                   ->whereEqual('collection_repaid', self::ZERO);
        $where2 = $this->_sql->getWhere();
        
        if (0 == $this->_scheduleModel->selectTotal($where2)) {
            return;
        }
        
        
        $scheduleLoanIds = $this->_scheduleModel->selectDistinct('loan_id', $where2);
        $this->_totalUnpaid = $this->_scheduleModel->selectGroupCount('loan_id', $where2);
        
        if ($this->_ageing > 0) {
            $selected = $this->_ageingLoans($scheduleLoanIds);
            if (empty($selected)) {
                return;
            }
        } else {
            $selected = $scheduleLoanIds;
        }
        
        foreach ($selected as $id) {
            $this->_loanId = $id;
            switch ($this->_payment) {
                case self::PAYMENT_REQUIRED:
                    $this->_paymentRequired();
                    break;

                case self::NO_PAYMENT:
                    $this->_noPayment();
                    break;

                default:
                    $this->_paymentAny();
                    break;
            }
        }
    }
    
    /**
     * filter by ageing
     * 
     * @param array $ids
     * @return boolean|array
     */
    private function _ageingLoans($ids)
    {
        $this->_sql->clear();
        $this->_sql->whereIn('loan_id', $ids);
        if (1 == $this->_ageing) {
            $this->_sql->whereLess('ageing', $this->_ageingMap[1]);
        } else {
            $this->_sql->whereGreater('ageing', $this->_ageingMap[$this->_ageing]);
        }
        
        $where = $this->_sql->getWhere();
        if (0 == $this->_loanSummaryModel->selectTotal($where)) {
            return false;
        }

        return $this->_loanSummaryModel->selectColumns('loan_id', $where);
    }
    
    /**
     * payment required filter
     * 
     * @return null
     */
    private function _paymentRequired()
    {
        if (! $this->_checkRepayment()) {
            return;
        }
 
        $last = $this->_getLastPayment();
        $instalments = $this->_getNonPaymentInstalments($last->instalment);
        if (0 == $instalments) {
            return;
        }
        
        $this->_addLoan($instalments, $last);
    }
    
    /**
     * no payment filter
     * 
     * @return null
     */
    private function _noPayment()
    {
        if ($this->_checkRepayment()) {
            return;
        }
        
        $instalments = $this->_getNonPaymentInstalments(0);
        if (0 == $instalments) {
            return;
        }
        
        $this->_addLoan($instalments, null, $instalments);
    }
    
    /**
     * payment any filter
     * 
     * @return null
     */
    private function _paymentAny()
    {
        if ($this->_checkRepayment()) {
            $last = $this->_getLastPayment();
            $instalments = $this->_getNonPaymentInstalments($last->instalment);
            if (0 == $instalments) {
                return;
            }

            $this->_addLoan($instalments, $last);
        } else {
            $instalments = $this->_getNonPaymentInstalments(0);
            if (0 == $instalments) {
                return;
            }

            $this->_addLoan($instalments, null, $instalments);
        }
    }
    
    /**
     * check repayment
     * 
     * @return boolean
     */
    private function _checkRepayment()
    {
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_loanId)
                   ->whereOne('has_dcb_entry')
                   ->whereGreater('collection_repaid', '0.00');
        
        return $this->_scheduleModel->selectTotal($this->_sql->getWhere()) > 0;
    }
    
    /**
     * get last payment
     * 
     * @return \stdClass
     */
    private function _getLastPayment()
    {
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_loanId)
                   ->whereOne('has_dcb_entry')
                   ->whereGreater('collection_repaid', '0.00');
        
        return $this->_scheduleModel->selectRow($this->_sql->getWhere(), 'instalment DESC');
    }
    
    /**
     * get non-payment instalments
     * 
     * @param int $last last paid instalment
     * @return int
     */
    private function _getNonPaymentInstalments($last = 0)
    {
        $this->_sql->clear();
        $this->_sql->whereId('loan_id', $this->_loanId)
               ->whereOne('has_dcb_entry')
               ->whereGreater('instalment', $last);
        
        return $this->_scheduleModel->selectTotal($this->_sql->getWhere());
    }
    
    /**
     * add loan to list
     * 
     * @param int $instalments
     * @param \stdClass|null $last
     * @param int $total
     * @return null
     */
    private function _addLoan($instalments, $last = null, $total = null)
    {
        if ($instalments <= $this->_instalmentMap[$this->_instalment]) {
            return;
        }
        
        if (null === $total) {
            $total = $this->_totalUnpaid[$this->_loanId];
        }
        
        if ($this->_unpaid > 0) {
            if ($total <= $this->_unpaidMap[$this->_unpaid]) {
                return;
            }
        }
            
        $this->_report['ids'][] = $this->_loanId;
        $entry = [
            'date' => '',
            'instalment' => '',
            'pending' => $instalments,
            'unpaid' => $total,
        ];
        
        if (null !== $last) {
            $entry['date'] = DateHelper::datepicker($last->instalment_date);
            $entry['instalment'] = $last->instalment;
        }
        
        $this->_report['schedule'][$this->_loanId] = $entry;
        
    }
    
}
