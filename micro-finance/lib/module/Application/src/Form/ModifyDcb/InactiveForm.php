<?php
/**
 * Application ModifyDcb Inactive Form
 * 
 * @category   Application
 * @package    Application\Form
 * @subpackage Application\Form\ModifyDcb
 */

namespace Application\Form\ModifyDcb;

use Application\Utility\LoanHelper;
use Application\Helper\{CommentLogger, TimelineLogger};

use Model\{LoanSummary, Inactive, Schedule, Summary};

use Web\Form\Form;
use Web\Utility\DateHelper;


class InactiveForm extends Form
{    
    /**
     * 
     * @var int
     */
    private $_loanId;
    
    /**
     * 
     * @var array
     */
    private $_formValues;
    
    /**
     * 
     * @var int
     */
    private $_administaratorId;
    
    /**
     * 
     * @var \Application\Utility\LoanStore
     */
    private $_store;

    public function __construct()
    {
        parent::__construct();
        $fields = [
            'last_instalment',
            'reason',
            'comments',
        ];
        $this->addFields($fields);
    }
    
    /**
     * set administrator id
     * 
     * @param int $id
     */
    public function setAdministratorId($id)
    {
        $this->_administaratorId = $id;
    }
    
    /**
     * set store
     * 
     * @param \Application\Utility\LoanStore $store
     */
    public function setStore($store)
    {
        $this->_store = $store;
    }
    
    /**
     * validate form
     *
     * @return boolean
     */
    public function isValid()
    {
        $this->csrf();
        $this->_formValues = $this->postback();
        
        if ($this->validator->isNull($this->_formValues['reason'])) {
            $this->error->required('Reason');
        }
        
        return $this->noError();
    }
    
    /**
     * update to inactive
     * 
     * @return boolean
     */
    public function change()
    {
        $this->_loanId = $this->_store->id();
        $loanSummary = $this->_store->loanSummary();
        $schedule = $this->_store->schedule();
        $summaryAll = $this->_store->summaryAll();
        $lastSchedule = $schedule[$this->_formValues['last_instalment']];
        $options = $this->_store->inactiveOptions();
        $now = DateHelper::timestamp();
        
        $inactiveModel = new Inactive();
        
        $insert = [
            'loan_id' => $this->_loanId,
            'last_instalment' => $lastSchedule->instalment,
            'inactive_date' => $lastSchedule->instalment_date,
            'outstanding_amount' => $loanSummary->balance_actual_principal_interest_outstanding,
            'inactive_amount' => $lastSchedule->balance_actual_principal_interest_outstanding,
            'inactive_principal' => $lastSchedule->balance_actual_principal_outstanding,
            'paid_instalments' => $options['paid'],
            'unpaid_instalments' => $options['successive'],
            'schedule' => serialize($schedule),
            'summary' => serialize($summaryAll),
            'loan_summary' => serialize($loanSummary),
            'reason' => $this->_formValues['reason'],
            'comments' => $this->_formValues['comments'],
            'created_by' => $this->_administaratorId,
            'created_at' => $now,
        ];
        $inactiveModel->insert($insert);
        
        $update = [
            'closed' => 1,
            'loan_status_id' => LoanHelper::STATUS_INACTIVE,
            'updated_at' => $now,
            'last_updated' => DateHelper::today(),
            'paid_tenures' => $lastSchedule->instalment,
        ];
        $this->getModel()->updateById($update, $this->_loanId);
        
        $sql = $inactiveModel->getSql();
        $sql->whereId('loan_id', $this->_loanId)
            ->whereOne('has_dcb_entry')
            ->whereGreater('id', $lastSchedule->id);
        $where = $sql->getWhere();
        $scheduleModel = new Schedule();
        if ($scheduleModel->selectTotal($where) > 0) {
            $scheduleIds = $scheduleModel->selectIds($where);
            $scheduleModel->deleteByIds($scheduleIds);
            $sql->clear();
            $sql->whereIn('schedule_id', $scheduleIds);
            $summaryModel = new Summary();
            $summaryModel->delete($sql->getWhere());
            $newSummary = $summaryModel->selectRow(['schedule_id' => $lastSchedule->id]);
                   
            $loanSummaryUpdate = [
                'modified_at' => $now,
                'paid_tenures' => $lastSchedule->instalment,
                'ageing' => $lastSchedule->ageing_days,
                'as_on_date' => $lastSchedule->instalment_date,
            ];

            $direct = [
                'scheduled_demand_loan_outstanding', 'collection_penal_interest',
                'collection_interest_arrears', 'collection_current_interest', 
                'collection_principal_arrears', 'collection_current_principal', 
                'collection_prepayment', 'collection_repaid',
                'balance_interest_overdue', 'balance_principal_overdue', 'balance_total_overdue',
                'balance_scheduled_principal_outstanding', 'balance_actual_principal_outstanding',
                'balance_actual_principal_interest_outstanding', 'par',
            ];

            foreach ($direct as $field) {
                $loanSummaryUpdate[$field] = $newSummary->{$field};
            }

            $loanSummaryModel = new LoanSummary();
            $loanSummaryModel->updateById($loanSummaryUpdate, $loanSummary->id);
        }
        
        $this->_store->clearPqam($lastSchedule->instalment_date);
        
        $logger = new TimelineLogger($this->_administaratorId);
        $logger->id($this->_loanId)
               ->save('DCB moved to Inactive status', 'Loan set as Inactive as on ' . DateHelper::datepicker($lastSchedule->instalment_date));
        
        $this->_saveComment();

        return true;
    }
    
    
    /**
     * save comment
     * 
     * @return null
     */
    private function _saveComment()
    {
        if (empty($this->_formValues['comments'])) {
            return;
        }
        
        $logger = new CommentLogger($this->_administaratorId);
        $logger->id($this->_loanId)
               ->save($this->_formValues['comments']);
    }
    
}
