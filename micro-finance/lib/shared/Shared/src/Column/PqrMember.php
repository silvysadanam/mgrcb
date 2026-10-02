<?php
/**
 * Shared Column PqrMember
 * 
 * @category Shared
 * @package  Shared\Column
 */

namespace Shared\Column;


class PqrMember
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
    
    const SERIAL = 1;
    const AGEING = 2;
    const MEMBERS = 3;
    const LOANS = 4;
    const LOAN_AMOUNT = 5;
    const SCHEDULED_OUTSTANDING = 6;
    const PRINCIPAL_OUTSTANDING = 7;
    const ACTUAL_OUTSTANDING = 8;
    const OVERDUE = 9;
    const PAR = 10;
    const PERCENTAGE_MEMBERS = 11;
    const PERCENTAGE_PORTFOLIO = 12;
    const PERCENTAGE_LOANS = 13;
    const LLR_PERCENTAGE = 14;
    const LLR_AMOUNT = 15;
    
    const HEADING_MAIN = 'OVERVIEW - PORTFOLIO QUALITY - MEMBERS LEVEL';
    
    const COLUMNS = 15;
    
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
        // 1 
        $this->_columns[self::C1] = [
            'column' => 1, 'cell' => 'A', 'title' => '', 
            'field' => '', 'background' => '', 
            'html' => '', 'csv' => '', 'label' => '',
        ];
        
        // 2 Ageing
        $this->_columns[self::C2] = [
            'column' => 2, 'cell' => 'B', 'title' => 'Ageing',
            'field' => '', 'background' => '', 
            'html' => 'Ageing', 'csv' => 'Ageing', 'label' => 'Ageing',
        ];
        
        // 3 No. of Members
        $this->_columns[self::C3] = [
            'column' => 3, 'cell' => 'C', 'title' => 'No. of Members', 
            'field' => '', 'background' => 'grey', 'html' => 'No. of<br>Members', 
            'csv' => 'No. of Members',  'label' => 'No. of Members',
        ];
        
        $this->_loan();
        $this->_outstanding();
        $this->_overdue();
        $this->_percentage();
        
        // 15 LLR Amt
        $this->_columns[self::C15] = [
            'column' => 15, 'cell' => 'O', 'title' => 'LLR Amt', 
            'field' => '', 'background' => 'gold', 'html' => 'LLR Amt', 
            'csv' => 'LLR Amt', 'label' => 'LLR Amount',
        ];
    }
    
    /**
     * loan
     */
    private function _loan()
    {
        // 4 Total Loan Amt
        $this->_columns[self::C4] = [
            'column' => 4, 'cell' => 'D', 'title' => 'Total Loan Amt', 
            'field' => '', 'background' => 'grey', 'html' => 'Total<br>Loan Amt', 
            'csv' => 'Total Loan Amt', 'label' => 'Total Loan Amt',
        ]; 
        
        // 5 No Loans
        $this->_columns[self::C5] = [
            'column' => 5, 'cell' => 'E', 'title' => 'No Loans', 
            'field' => '', 'background' => 'grey', 'html' => 'No Loans', 
            'csv' => 'No Loans',  'label' => 'No Loans',
        ];
    }
    
    /**
     * outstanding
     */
    private function _outstanding()
    {
        // 6 Total Scheduled OS
        $this->_columns[self::C6] = [
            'column' => 6, 'cell' => 'F', 'title' => 'Total Scheduled OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Total<br>Scheduled OS', 
            'csv' => 'Total Scheduled OS',  'label' => 'Total Scheduled OS',
        ];
        
        // 7 Actual Principal OS 
        $this->_columns[self::C7] = [
            'column' => 7, 'cell' => 'G', 'title' => 'Actual Principal OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Actual<br>Principal OS ', 
            'csv' => 'Actual Principal OS ',  'label' => 'Actual Principal OS ',
        ];
        
        // 8 Actaul Principal and Interest OS 
        $this->_columns[self::C8] = [
            'column' => 8, 'cell' => 'H', 'title' => 'Actual Principal and Interest OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Actual Principal<br>and Interest OS ', 
            'csv' => 'Actual Principal and Interest OS ',  'label' => 'Actual Principal and Interest OS ',
        ];
    }
    
    /**
     * Overdue
     */
    private function _overdue()
    {
         // 9 OD Amt
        $this->_columns[self::C9] = [
            'column' => 9, 'cell' => 'I', 'title' => 'OD Amt', 
            'field' => '', 'background' => 'gold', 'html' => 'OD Amt', 
            'csv' => 'OD Amt',  'label' => 'OD Amt',
        ];
        
        // 10 Portfolio At Risk (PAR)
        $this->_columns[self::C10] = [
            'column' => 10, 'cell' => 'J', 'title' => 'Portfolio At Risk (PAR)', 
            'field' => '', 'background' => 'gold', 'html' => 'Portfolio<br>At Risk (PAR)',
            'csv' => 'Portfolio At Risk (PAR)',  'label' => 'Portfolio At Risk (PAR)',
        ];
    }
    
    private function _percentage()
    {
        // 11 % of Members
        $this->_columns[self::C11] = [
            'column' => 11, 'cell' => 'K', 'title' => '% of Members', 
            'field' => '', 'background' => '', 'html' => '% of<br>Members', 
            'csv' => '% of Members', 'label' => 'Percentage of Members ',
        ];
        
        // 12 % of Loan Portfolio Amount
        $this->_columns[self::C12] = [
            'column' => 12, 'cell' => 'L', 'title' => '% of Loan Portfolio Amount', 
            'field' => '', 'background' => '', 'html' => '% of Loan<br>Portfolio Amount', 
            'csv' => '% of Loan Portfolio Amount',  'label' => 'Percentage Loans',
        ];
        
        // 13 % of No. of Loans
        $this->_columns[self::C13] = [
            'column' => 13, 'cell' => 'M', 'title' => '% of No. of Loans', 
            'field' => '', 'background' => '', 'html' => '% of<br>No. of Loans', 
            'csv' => '% of No. of Loans', 'label' => 'Percentage of Loans',
        ];
        
        // 14 Loan Loss Reserve (LLR) Percentage
        $this->_columns[self::C14] = [
            'column' => 14, 'cell' => 'N', 'title' => 'Loan Loss Reserve (LLR) Percentage', 
            'field' => '', 'background' => 'gold', 'html' => 'Loan Loss Reserve<br>(LLR) Percentage', 
            'csv' => 'Loan Loss Reserve (LLR) Percentage', 'label' => 'Loan Loss Reserve (LLR) Percentage',
        ];
    }
   
}
