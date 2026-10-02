<?php
/**
 * Shared Column PqrLoan
 * 
 * @category Shared
 * @package  Shared\Column
 */

namespace Shared\Column;


class PqrLoan
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
    
    const SERIAL = 1;
    const AGEING = 2;
    const LOANS = 3;
    const LOAN_AMOUNT = 4;
    const SCHEDULED_OUTSTANDING = 5;
    const PRINCIPAL_OUTSTANDING = 6;
    const ACTUAL_OUTSTANDING = 7;
    const OVERDUE = 8;
    const PAR = 9;
    const PERCENTAGE_LOANS = 10;
    const PERCENTAGE_PORTFOLIO = 11;
    const LLR_PERCENTAGE = 12;
    const LLR_AMOUNT = 13;
    
    const HEADING_MAIN = 'OVERVIEW - PORTFOLIO QUALITY - LOANS LEVEL';
    
    const COLUMNS = 13;
    
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
        
        
        $this->_loan();
        $this->_outstanding();
        $this->_overdue();
        $this->_percentage();
        
        // 13 LLR Amt
        $this->_columns[self::C13] = [
            'column' => 13, 'cell' => 'M', 'title' => 'LLR Amt', 
            'field' => '', 'background' => 'gold', 'html' => 'LLR Amt', 
            'csv' => 'LLR Amt', 'label' => 'LLR Amount',
        ];
    }
    
    /**
     * loan
     */
    private function _loan()
    {
        // 3 No Loans
        $this->_columns[self::C3] = [
            'column' => 3, 'cell' => 'C', 'title' => 'No Loans', 
            'field' => '', 'background' => 'grey', 'html' => 'No<br>Loans', 
            'csv' => 'No Loans',  'label' => 'No Loans',
        ];
        
        // 4 Loan Amt
        $this->_columns[self::C4] = [
            'column' => 4, 'cell' => 'D', 'title' => 'Loan Amt', 
            'field' => '', 'background' => 'grey', 'html' => 'Loan Amt', 
            'csv' => 'Loan Amt', 'label' => 'Loan Amt',
        ]; 
    }
    
    /**
     * outstanding
     */
    private function _outstanding()
    {
        // 5 Scheduled OS
        $this->_columns[self::C5] = [
            'column' => 5, 'cell' => 'E', 'title' => 'Scheduled OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Scheduled<br>OS', 
            'csv' => 'Scheduled OS',  'label' => 'Scheduled OS',
        ];
        
        // 6 Actual Principal OS 
        $this->_columns[self::C6] = [
            'column' => 6, 'cell' => 'F', 'title' => 'Actual Principal OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Actual<br>Principal OS ', 
            'csv' => 'Actual Principal OS ',  'label' => 'Actual Principal OS ',
        ];
        
        // 7 Actual Principal/ Int. OS 
        $this->_columns[self::C7] = [
            'column' => 7, 'cell' => 'G', 'title' => 'Actual Principal/ Int. OS', 
            'field' => '', 'background' => 'grey', 'html' => 'Actual<br>Principal/Int. OS ', 
            'csv' => 'Actual Principal/ Int. OS ',  'label' => 'Actual Principal/ Int. OS ',
        ];
    }
    
    /**
     * Overdue
     */
    private function _overdue()
    {
         // 8 OD Amt
        $this->_columns[self::C8] = [
            'column' => 8, 'cell' => 'H', 'title' => 'OD Amt', 
            'field' => '', 'background' => 'gold', 'html' => 'OD Amt', 
            'csv' => 'OD Amt',  'label' => 'OD Amt',
        ];
        
        // 9 Portfolio At Risk (PAR)
        $this->_columns[self::C9] = [
            'column' => 9, 'cell' => 'I', 'title' => 'Portfolio At Risk (PAR)', 
            'field' => '', 'background' => 'gold', 'html' => 'Portfolio<br>At Risk (PAR)',
            'csv' => 'Portfolio At Risk (PAR)',  'label' => 'Portfolio At Risk (PAR)',
        ];
    }
    
    private function _percentage()
    {
        // 10 %  Lns
        $this->_columns[self::C10] = [
            'column' => 10, 'cell' => 'J', 'title' => '%  Lns', 
            'field' => '', 'background' => '', 'html' => '%  Lns', 
            'csv' => '%  Lns',  'label' => 'Percentage Loans',
        ];
        
        // 11 % of Portfolio 
        $this->_columns[self::C11] = [
            'column' => 11, 'cell' => 'K', 'title' => '% of Portfolio OS', 
            'field' => '', 'background' => '', 'html' => '% of<br>Portfolio OS', 
            'csv' => '% of Portfolio OS', 'label' => 'Percentage of Portfolio OS',
        ];
        
        // 12 Loan Loss Reserve % 
        $this->_columns[self::C12] = [
            'column' => 12, 'cell' => 'L', 'title' => 'Loan Loss Reserve %', 
            'field' => '', 'background' => 'gold', 'html' => 'Loan Loss<br>Reserve %', 
            'csv' => 'Loan Loss Reserve %', 'label' => 'Loan Loss Reserve Percentage',
        ];
    }
   
}
