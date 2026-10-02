<?php
/**
 * Shared Column PqaMember
 * 
 * @category Shared
 * @package  Shared\Column
 */

namespace Shared\Column;


class PqaMember
{
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
    
    const SERIAL = 1;
    const MEMBER = 2;
    const CODE = 3;
    const FMC = 4;
    const LOANS = 5;
    const LOAN_AMOUNT = 6;
    const EMI = 7;
    const PRINCIPAL_PREPAID = 8;
    const PRINCIPAL_ARREARS = 9;
    const PRINCIPAL_PAID = 10;
    const INTEREST_PAID = 11;
    const TOTAL_PAID = 12;
    const SCHEDULED_OUTSTANDING = 13;
    const PRINCIPAL_OUTSTANDING = 14;
    const ACTUAL_OUTSTANDING = 15;
    const OVERDUE = 16;
    const AGEING = 17;
    const PAR = 18;
    const CATCH_AMOUNT = 19;
    const REPAID = 20;
    const CATCH_PERCENTAGE = 21;
    
    const HEADING_MAIN = 'Credit Portfolio Analysis - Members Level';
    
    const COLUMNS = 21;
    
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
            'field' => '', 'background' => '', 'html' => '', 'csv' => '', 'label' => '',
        ];
        
        // 2 Member
        $this->_columns[self::C2] = [
            'column' => 2, 'cell' => 'B', 'title' => 'Member',
            'field' => '', 'background' => 'grey', 'html' => 'Member', 'csv' => '', 'label' => '',
        ];
        
        // 3 Code
        $this->_columns[self::C3] = [
            'column' => 3, 'cell' => 'C', 'title' => 'Code', 
            'field' => '', 'background' => 'grey', 'html' => 'Code', 'csv' => '', 'label' => '',
        ];
        
        // 4 FMC
        $this->_columns[self::C4] = [
            'column' => 4, 'cell' => 'D', 'title' => 'FMC', 
            'field' => '', 'background' => 'grey', 'html' => 'FMC', 'csv' => '', 'label' => '',
        ];
    }
    
    /**
     * loan
     */
    private function _loan()
    {
        // 5 No of Lns
        $this->_columns[self::C5] = [
            'column' => 5, 'cell' => 'E', 'title' => 'No of Lns', 
            'field' => 'loans', 'background' => 'grey', 'html' => 'No of<br>Lns', 'csv' => '', 'label' => '',
        ];
        
        // 6 Total Loan Amount
        $this->_columns[self::C6] = [
            'column' => 6, 'cell' => 'F', 'title' => 'Total Loan Amount', 
            'field' => 'loan_amount', 'background' => 'grey', 'html' => 'Total<br>Loan<br>Amount', 'csv' => '', 'label' => '',
        ];
        
        // 6 Total EMI
        $this->_columns[self::C7] = [
            'column' => 7, 'cell' => 'G', 'title' => 'Total EMI', 
            'field' => 'emi', 'background' => 'grey', 'html' => 'Total<br>EMI', 'csv' => '', 'label' => '',
        ];
    }
    
    /**
     * paid
     */
    private function _paid()
    {
        // 8 Prncpl Pre-Paid/Paid on Time 
        $this->_columns[self::C8] = [
            'column' => 8, 'cell' => 'H', 'title' => 'Prncpl Pre-Paid/Paid on Time ', 
            'field' => 'principal_prepaid_current', 'background' => '', 'html' => 'Prncpl<br> Pre-Paid/<br>Paid on Time', 'csv' => '', 'label' => '',
        ];
        // 9 Principal Paid as Arrears
        $this->_columns[self::C9] = [
            'column' => 9, 'cell' => 'I', 'title' => 'Principal Paid as Arrears', 
            'field' => 'principal_arrears', 'background' => '', 'html' => 'Principal<br>Paid as<br>Arrears', 'csv' => '', 'label' => '',
        ];
        
        // 10 Total Principla Paid
        $this->_columns[self::C10] = [
            'column' => 10, 'cell' => 'J', 'title' => 'Total Principal Paid', 
            'field' => 'principal', 'background' => '', 'html' => 'Total<br>Principal<br>Paid', 'csv' => '', 'label' => '',
        ];
        
        // 11 Int Pd
        $this->_columns[self::C11] = [
            'column' => 11, 'cell' => 'K', 'title' => 'Int Pd', 
            'field' => 'interest', 'background' => '', 'html' => 'Int Pd', 'csv' => '', 'label' => '',
        ];
        
        // 12 Tot. Pd
        $this->_columns[self::C12] = [
            'column' => 12, 'cell' => 'L', 'title' => 'Tot. Pd', 
            'field' => 'total', 'background' => '', 'html' => 'Tot. Pd', 'csv' => '', 'label' => '',
        ];
    }
    
    /**
     * outstanding
     */
    private function _outstanding()
    {
        // 13 Scheduled OS
        $this->_columns[self::C13] = [
            'column' => 13, 'cell' => 'M', 'title' => 'Scheduled OS', 
            'field' => 'scheduled_outstanding', 'background' => '', 'html' => 'Scheduled OS', 'csv' => '', 'label' => '',
        ];
        
        // 14 Actual Principal OS
        $this->_columns[self::C14] = [
            'column' => 14, 'cell' => 'N', 'title' => 'Actual Principal OS', 
            'field' => 'principal_outstanding', 'background' => '', 'html' => 'Actual <br>Principal<br>OS',
            'csv' => 'Actual Principal OS', 'label' => 'Actual Principal Outstanding',
        ];
        
        // 15 Actual Principal/Interest OS
        $this->_columns[self::C15] = [
            'column' => 15, 'cell' => 'O', 'title' => 'Actual Principal/Interest OS', 
            'field' => 'actual_outstanding', 'background' => '', 'html' => 'Actual Principal/<br>Interest OS', 'csv' => '', 'label' => '',
        ];
    }
    
    /**
     * overdue
     */
    private function _overdue()
    {
        // 16 OD
        $this->_columns[self::C16] = [
            'column' => 16, 'cell' => 'P', 'title' => 'OD', 
            'field' => 'overdue', 'background' => 'gold', 'html' => 'OD', 'csv' => '', 'label' => '',
        ];
        
        // 17 Ageing
        $this->_columns[self::C17] = [
            'column' => 17, 'cell' => 'Q', 'title' => 'Ageing', 
            'field' => 'ageing', 'background' => 'gold', 'html' => 'Ageing', 'csv' => '', 'label' => '',
        ];
        
        // 18 PAR
        $this->_columns[self::C18] = [
            'column' => 18, 'cell' => 'R', 'title' => 'Portfolio At Risk (PAR)', 
            'field' => 'par', 'background' => 'gold', 'html' => 'Portfolio<br>At Risk<br>(PAR)', 'csv' => '', 'label' => '',
        ];
    }
    
    /**
     * catch
     */
    private function _catch()
    {
        // 19 Catch
        $this->_columns[self::C19] = [
            'column' => 19, 'cell' => 'S', 'title' => 'Catch', 
            'field' => 'catch_amount', 'background' => 'grey', 'html' => 'Catch', 'csv' => '', 'label' => '',
        ];
        
        // 20 Repaid
        $this->_columns[self::C20] = [
            'column' => 20, 'cell' => 'T', 'title' => 'Repaid', 
            'field' => 'repaid', 'background' => 'grey', 'html' => 'Repaid', 'csv' => '', 'label' => '',
        ];
        
        // 21 % Ctch
        $this->_columns[self::C21] = [
            'column' => 21, 'cell' => 'U', 'title' => '% Ctch', 
            'field' => 'percentage_catch', 'background' => 'grey', 'html' => '% Ctch', 'csv' => '', 'label' => '',
        ];
    }
   
}
