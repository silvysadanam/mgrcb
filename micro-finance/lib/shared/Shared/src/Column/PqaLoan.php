<?php
/**
 * Shared Column PqaLoan
 * 
 * @category Shared
 * @package  Shared\Column
 */

namespace Shared\Column;


class PqaLoan
{
    const LF = " \n";
    
    const C1 = 1;
    const C2 = 2;
    const C3 = 3;
    const C4 = 4;
    const C5 = 5;
    const C6 = 6;
    const C7 = 7;
    const C8 = 8;
    const C9 = 9;
    const C10 = 10;
    const C11 = 11;
    const C12 = 12;
    const C13 = 13;
    const C14 = 14;
    const C15 = 15;
    const C16 = 16;
    const C17 = 17;
    const C18 = 18;
    const C19 = 19;
    const C20 = 20;
    const C21 = 21;
    const C22 = 22;
    const C23 = 23;
    const C24 = 24;
    const C25 = 25;
    const C26 = 26;
    const C27 = 27;
    
    const A = 1;
    const B = 2;
    const C = 3;
    const D = 4;
    const E = 5;
    const F = 6;
    const G = 7;
    const H = 8;
    const I = 9;
    const J = 10;
    const K = 11;
    const L = 12;
    const M = 13;
    const N = 14;
    const O = 15;
    const P = 16;
    const Q = 17;
    const R = 18;
    const S = 19;
    const T = 20;
    const U = 21;
    const V = 22;
    const W = 23;
    const X = 24;
    const Y = 25;
    const Z = 26;
    const AA = 27;
    
    const SERIAL = 1;
    const MEMBER = 2;
    const CODE = 3;
    const FMC = 4;
    const LOAN_ACCOUNT = 5;
    const LOAN_TYPE = 6;
    const LOAN_DATE = 7;
    const LOAN_AMOUNT = 8;
    const LOAN_SOURCE = 9;
    const TENURE = 10;
    const INTEREST_RATE = 11;
    const EMI = 12;
    const INSTALMENT = 13;
    const PRINCIPAL_PREPAID = 14;
    const PRINCIPAL_ARREARS = 15;
    const TOTAL_PRINCIPAL = 16;
    const INTEREST_PAID = 17;
    const TOTAL_PAID = 18;
    const SCHEDULED_OUTSTANDING = 19;
    const PRINCIPAL_OUTSTANDING = 20;
    const ACTUAL_OUTSTANDING = 21;
    const OVERDUE = 22;
    const AGEING = 23;
    const PAR = 24;
    const CATCH_AMOUNT = 25;
    const REPAID = 26;
    const CATCH_PERCENTAGE = 27;
    
    const HEADING_MAIN = 'Credit Portfolio Analysis - Loans Level';
    
    const COLUMNS = 27;
    
    /**
     * 
     * @var array
     */
    private $_columns = [];
    
    public function __construct()
    {
        $this->_load();
    }
    
    /**
     * get columns
     * 
     * @return type
     */
    public function get()
    {
        return $this->_columns;
    }
    
    
    /**
     * load columns
     */
    private function _load()
    {
        $this->_member();
        $this->_loan();
        $this->_instalment();
        $this->_paid();
        $this->_outstanding();
        $this->_overdue();
        $this->_catch();
    }
    
    /**
     * member
     */
    private function _member()
    {
        // 1 
        $this->_columns[self::C1] = [
            'column' => 1, 'cell' => 'A', 'title' => '', 
            'field' => '', 'background' => '', 
            'html' => '', 'csv' => '', 'label' => '',
        ];
        
        // 2 Member
        $this->_columns[self::C2] = [
            'column' => 2, 'cell' => 'B', 'title' => 'Member',
            'field' => '', 'background' => 'grey', 
            'html' => 'Member', 'csv' => 'Member', 'label' => 'Member',
        ];
        
        // 3 Code
        $this->_columns[self::C3] = [
            'column' => 3, 'cell' => 'C', 'title' => 'Code', 
            'field' => '', 'background' => 'grey', 'html' => 'Code', 
            'csv' => 'Code',  'label' => 'Code',
        ];
        
        // 4 FMC
        $this->_columns[self::C4] = [
            'column' => 4, 'cell' => 'D', 'title' => 'FMC', 
            'field' => '', 'background' => 'grey', 'html' => 'FMC', 
            'csv' => 'FMC', 'label' => 'FMC',
        ]; 
    }
    
    /**
     * loan
     */
    private function _loan()
    {
        // 5 Loan A/C
        $this->_columns[self::C5] = [
            'column' => 5, 'cell' => 'E', 'title' => 'Loan A/C', 
            'field' => '', 'background' => 'grey', 'html' => 'Loan A/C', 
            'csv' => 'Loan A/C',  'label' => 'Loan A/C',
        ];
        
        
        // 6 Typ Loan
        $this->_columns[self::C6] = [
            'column' => 6, 'cell' => 'F', 'title' => 'Typ Loan', 
            'field' => '', 'background' => 'grey', 'html' => 'Typ Loan', 
            'csv' => 'Typ Loan',  'label' => 'Loan Type',
        ];
        
        // 7 Ln Date
        $this->_columns[self::C7] = [
            'column' => 7, 'cell' => 'G', 'title' => 'Ln Date', 
            'field' => '', 'background' => 'grey', 'html' => 'Ln Date', 
            'csv' => 'Ln Date',  'label' => 'Loan Date',
        ];
        
        // 8 Ln Amt
        $this->_columns[self::C8] = [
            'column' => 8, 'cell' => 'H', 'title' => 'Ln Amt', 
            'field' => '', 'background' => 'grey', 'html' => 'Ln Amt',
            'csv' => 'Ln Amt',  'label' => 'Loan Amount',
        ];
        
        // 9 Source
        $this->_columns[self::C9] = [
            'column' => 9, 'cell' => 'I', 'title' => 'Source', 
            'field' => '', 'background' => 'grey', 'html' => 'Source', 
            'csv' => 'Source',  'label' => 'Loan Source',
        ];
        
        // 10 Tenure
        $this->_columns[self::C10] = [
            'column' => 10, 'cell' => 'J', 'title' => 'Tenure', 
            'field' => '', 'background' => 'grey', 'html' => 'Tenure', 
            'csv' => 'Tenure', 'label' => 'Tenure',
        ];
        
        // 11 ROI
        $this->_columns[self::C11] = [
            'column' => 11, 'cell' => 'K', 'title' => 'ROI', 
            'field' => '', 'background' => 'grey', 'html' => 'ROI', 
            'csv' => 'ROI', 'label' => 'Interest Rate',
        ];
        
        // 12 EMI
        $this->_columns[self::C12] = [
            'column' => 12, 'cell' => 'L', 'title' => 'EMI', 
            'field' => '', 'background' => 'grey', 'html' => 'EMI', 
            'csv' => 'EMI', 'label' => 'EMI',
        ];
    }
    
    /**
     * instalment
     */
    private function _instalment()
    {
        // 13 Current Intallment
        $this->_columns[self::C13] = [
            'column' => 13, 'cell' => 'M', 'title' => 'Current Intallment', 
            'field' => 'instalment', 'background' => '', 
            'html' => 'Current<br>Intallment', 'csv' => 'Current' . self::LF . 'Intallment',
            'label' => 'Current Intalment',
        ];
    }
    
    /**
     * paid
     */
    private function _paid()
    {
        // 14 Prncpl Pre-Paid/Paid  On Time
        $this->_columns[self::C14] = [
            'column' => 14, 'cell' => 'N', 'title' => 'Prncpl Pre-Paid/Paid On Time', 
            'field' => 'principal_prepaid_current', 'background' => '', 
            'html' => 'Prncpl Pre-Paid/ <br>Paid On Time', 
            'csv' => 'Prncpl' . self::LF . 'Pre-Paid/' . self::LF . 'Paid On Time', 
            'label' => 'Prncipal Pre-Paid/Paid On Time',
        ];
        
        // 15 Prncpl Paid As Arrears
        $this->_columns[self::C15] = [
            'column' => 15, 'cell' => 'O', 'title' => 'Prncpl Paid As Arrears', 
            'field' => 'principal_arrears', 'background' => '', 'html' => 'Prncpl Paid<br>As Arrears',
            'csv' => 'Prncpl Paid' . self::LF . ' As Arrears', 'label' => 'Principal Arrears Paid',
        ];
        
        // 16 Total Principal Paid
        $this->_columns[self::C16] = [
            'column' => 16, 'cell' => 'P', 'title' => 'Total Principal Paid', 
            'field' => 'principal', 'background' => '', 'html' => 'Total<br>Principal<br>Paid', 
            'csv' => 'Total Principal Paid', 'label' => 'Total Principal Paid',
        ];
        
        // 17 Interest Paid
        $this->_columns[self::C17] = [
            'column' => 17, 'cell' => 'Q', 'title' => 'Interest Paid', 
            'field' => 'interest', 'background' => '', 'html' => 'Interest<br>Paid',
            'csv' => 'Interest' . self::LF . 'Paid', 'label' => 'Interest Paid',
        ];
        
        // 18 Tot. Pd
        $this->_columns[self::C18] = [
            'column' => 18, 'cell' => 'R', 'title' => 'Tot. Pd', 
            'field' => 'total', 'background' => '', 'html' => 'Tot. Pd',
            'csv' => 'Tot. Pd', 'label' => 'Total Paid',
        ];
    }
    
    /**
     * outstanding
     */
    private function _outstanding()
    {
        // 19 Scheduled OS
        $this->_columns[self::C19] = [
            'column' => 19, 'cell' => 'S', 'title' => 'Scheduled OS', 
            'field' => 'scheduled_outstanding', 'background' => '', 'html' => 'Scheduled<br> OS',
            'csv' => 'Scheduled OS', 'label' => 'Scheduled Outstanding',
        ];
        
        // 20 Actual Principal OS
        $this->_columns[self::C20] = [
            'column' => 20, 'cell' => 'T', 'title' => 'Actual Principal OS', 
            'field' => 'principal_outstanding', 'background' => '', 'html' => 'Actual <br>Principal<br>OS',
            'csv' => 'Actual Principal OS', 'label' => 'Actual Principal Outstanding',
        ];
        
        // 21 Actual Principal/  Interest OS
        $this->_columns[self::C21] = [
            'column' => 21, 'cell' => 'U', 'title' => 'Actual Principal/Interest OS', 
            'field' => 'actual_outstanding', 'background' => '', 'html' => 'Actual <br>Principal/<br>Interest OS',
            'csv' => 'Actual Principal/Interest OS', 'label' => 'Actual Principal/Interest Outstanding',
        ];
    }
    
    /**
     * Overdue
     */
    private function _overdue()
    {
        // 22 Overdue
        $this->_columns[self::C22] = [
            'column' => 22, 'cell' => 'V', 'title' => 'Overdue', 
            'field' => 'overdue', 'background' => 'gold', 'html' => 'Overdue',
            'csv' => 'Overdue', 'label' => 'Overdue',
        ];
        
        // 23 Ageing
        $this->_columns[self::C23] = [
            'column' => 23, 'cell' => 'W', 'title' => 'Ageing', 
            'field' => 'ageing', 'background' => 'gold', 'html' => 'Ageing', 
            'csv' => 'Ageing', 'label' => 'Ageing',
        ];
        
        // 24 Portfolio at Risk (PAR)
        $this->_columns[self::C24] = [
            'column' => 24, 'cell' => 'X', 'title' => 'Portfolio at Risk (PAR)', 
            'field' => 'par', 'background' => 'gold', 'html' => 'Portfolio<br>at Risk<br>(PAR)', 
            'csv' => 'Portfolio at Risk' . self::LF . ' (PAR)', 'label' => 'Portfolio at Risk (PAR)',
        ];
    }
    
    /**
     * catch
     */
    private function _catch()
    {
        // 25 Catch
        $this->_columns[self::C25] = [
            'column' => 25, 'cell' => 'Y', 'title' => 'Catch', 
            'field' => 'catch_amount', 'background' => '', 'html' => 'Catch', 'csv' => 'Catch', 'label' => 'Catch',
        ];
        
        // 26 Repaid
        $this->_columns[self::C26] = [
            'column' => 26, 'cell' => 'Z', 'title' => 'Repaid', 
            'field' => 'repaid', 'background' => '', 'html' => 'Repaid', 'csv' => 'Repaid', 'label' => 'Repaid',
        ];
        
        // 27 % Ctch
        $this->_columns[self::C27] = [
            'column' => 76, 'cell' => 'AA', 'title' => '% Ctch', 
            'field' => '', 'background' => '', 'html' => '% Ctch', 'csv' => '% Ctch', 'label' => 'Percentage Catch',
        ];
    }
   
}
