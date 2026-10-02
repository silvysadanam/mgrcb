<?php
/**
 * FilterStatus View helper
 * 
 * @category   Application
 * @package    Application\ViewHelper
 */

namespace Application\ViewHelper;

use Web\View\ViewHelper;

use Application\Utility\LoanHelper;


class FilterStatus extends ViewHelper
{
    /**
     * get loan status for filter
     * 
     * @param \stdClass $loan
     * @return string
     */
    public function filterStatus($loan)
    {
        if (0 == $loan->closed) {
            return 'Active';
        }
        
        if (0 == $loan->loan_status_id) {
            return 'Closed at end of tenure';
        }
        
        if (LoanHelper::STATUS_PRECLOSED == $loan->loan_status_id) {
            return 'Pre-closed';
            //return 'Preclosed';
        }
        
        if (LoanHelper::STATUS_WRITTEN_OFF == $loan->loan_status_id) {
            return 'Written off';
        }
        
        if (LoanHelper::STATUS_REFINANCED == $loan->loan_status_id) {
            return 'Refinanced &amp; Rescheduled';
        }
        
        if (LoanHelper::STATUS_RESCHEDULED == $loan->loan_status_id) {
            return 'Rescheduled';
        }
        
        if (LoanHelper::STATUS_DELINQUENT == $loan->loan_status_id) {
            return 'Tenure ended with OD';
        }
        
        if (LoanHelper::STATUS_INACTIVE == $loan->loan_status_id) {
            return 'Inactive';
        }
        
        return '';
    }
    
}
