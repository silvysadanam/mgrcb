<?php
/**
 * Application DcbColumn Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;


class DcbColumn
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
    const C22 = 22;
    const C23 = 23;
    const C24 = 24;
    const C25 = 25;
    const C26 = 26;
    const C27 = 27;
    const C28 = 28;
    const C29 = 29;
    
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
    const AB = 28;
    const AC = 29;
    
    const INSTALMENT_NUMBER = 1;
    const INSTALMENT_DATE = 2;
    const LOAN_OUTSTANDING = 3;
    const INTEREST = 4;
    const PRINCIPAL = 5;
    const EMI = 6;
    const PRINCIPAL_OUTSTANDING = 7;
    const PENAL_INTEREST_DEMAND = 8;
    const INTEREST_ARREARS_DEMAND = 9;
    const PRINCIPAL_ARREARS_DEMAND = 10;
    const TOTAL_ARREARS_DEMAND = 11;
    const TOTAL_DEMAND = 12;
    const PENAL_INTEREST_COLLECTION = 13;
    const INTEREST_ARREARS_COLLECTION = 14;
    const CURRENT_INTEREST_COLLECTION = 15;
    const PRINCIPAL_ARREARS_COLLECTION = 16;
    const CURRENT_PRINCIPAL_COLLECTION = 17;
    const PREPAY = 18;
    const REPAID = 19;
    const CATCH_AMOUNT = 20;
    const CATCH_PERCENTAGE = 21;
    const INTEREST_OVERDUE = 22;
    const PRINCIPAL_OVERDUE = 23;
    const TOTAL_OVERDUE = 24;
    const SCHEDULED_PRINCIPAL_OUTSTANDING = 25;
    const ACTUAL_PRINCIPAL_OUTSTANDING = 26;
    const ACTUAL_PRINCIPAL_INTEREST_OUTSTANDING = 27;
    const AGEING_FACTOR = 28;
    const AGEING_DAYS = 29;
    
    const HEADING_MAIN = 'DEMAND/COLLECTION/BALANCE STATEMENT';
    const HEADING_FMC = 'FMC';
    const HEADING_MEMBER_NAME = 'Name of Member';
    const HEADING_MEMBER_CODE = 'Code';
    const HEADING_SOURCE = 'Source';
    const HEADING_DATE = 'Loan Date';
    const HEADING_AMOUNT = 'Amount';
    const HEADING_TENURE = 'Tenure';
    const HEADING_PENAL_INTEREST = 'Penal Int';
    const HEADING_INTEREST_RATE = 'RoI';
    const HEADING_LOAN_TYPE = 'Type';
    const HEADING_EMI = 'EMI';
    const HEADING_SCHEDULED_DEMAND = 'SCHEDULED DEMAND';
    const HEADING_ARREARS_DEMAND = 'ARREARS DEMAND';
    const HEADING_TOTAL_DEMAND_HTML = 'TOTAL<br>DEMAND';
    const HEADING_TOTAL_DEMAND_CSV = 'TOTAL ' . "\n" . 'DEMAND';
    const HEADING_COLLECTION = 'COLLECTION';
    const HEADING_BALANCE = 'BALANCE';
    const HEADING_AGEING = 'AGEING';
    
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
        $this->_scheduledDemand();
        $this->_arrearsDemand();
        $this->_totalDemand();
        $this->_collection();
        $this->_payment();
        $this->_balance();
        $this->_ageing();
    }
    
    /**
     * scheduled demand
     */
    private function _scheduledDemand()
    {
        // 1 Instlnt
        $this->_columns[self::C1] = [
            'column' => 1, 'cell' => 'A', 'title' => 'Instlnt', 
            'field' => 'instalment', 'background' => '',
        ];
        
        // 2 Date
        $this->_columns[self::C2] = [
            'column' => 2, 'cell' => 'B', 'title' => 'Date',
            'field' => 'instalment_date', 'background' => 'demand',
        ];
        
        // 3 Loan OS
        $this->_columns[self::C3] = [
            'column' => 3, 'cell' => 'C', 'title' => 'Loan OS', 
            'field' => 'scheduled_demand_loan_outstanding', 'background' => 'demand',
        ];
        
        // 4 Interest
        $this->_columns[self::C4] = [
            'column' => 4, 'cell' => 'D', 'title' => 'Interest', 
            'field' => 'scheduled_demand_interest', 'background' => 'demand',
        ];
        
        // 5 Principal
        $this->_columns[self::C5] = [
            'column' => 5, 'cell' => 'E', 'title' => 'Principal', 
            'field' => 'scheduled_demand_principal', 'background' => 'demand',
        ];
        
        // 6 EMI
        $this->_columns[self::C6] = [
            'column' => 6, 'cell' => 'F', 'title' => 'EMI', 
            'field' => 'emi', 'background' => 'demand',
        ];
        
        // 7 Prncpl OS
        $this->_columns[self::C7] = [
            'column' => 7, 'cell' => 'G', 'title' => 'Prncpl OS', 
            'field' => 'scheduled_demand_principal_outstanding', 'background' => 'demand',
        ];
    }
    
    /**
     * arrears demand
     */
    private function _arrearsDemand()
    {
        // 8 Penal Int
        $this->_columns[self::C8] = [
            'column' => 8, 'cell' => 'H', 'title' => 'Penal Int', 
            'field' => 'arrears_demand_penal_interest', 'background' => '',
        ];
        
        // 9 Int Arrs
        $this->_columns[self::C9] = [
            'column' => 9, 'cell' => 'I', 'title' => 'Int Arrs', 
            'field' => 'arrears_demand_interest_arrears', 'background' => '',
        ];
        
        // 10 Prncpl Arrrs
        $this->_columns[self::C10] = [
            'column' => 10, 'cell' => 'J', 'title' => 'Prncpl Arrrs', 
            'field' => 'arrears_demand_principal_arrears', 'background' => '',
        ];
        
        // 11 Total
        $this->_columns[self::C11] = [
            'column' => 11, 'cell' => 'K', 'title' => 'Total', 
            'field' => 'arrears_demand_total', 'background' => '',
        ];
    }
    
    /**
     * total demand
     */
    private function _totalDemand()
    {
        // 12 TOTAL DEMAND
        $this->_columns[self::C12] = [
            'column' => 12, 'cell' => 'L', 'title' => 'TOTAL DEMAND', 
            'field' => 'total_demand', 'background' => '',
        ];
    }
    
    /**
     * collection
     */
    private function _collection()
    {
        // 13 Pnl Int
        $this->_columns[self::C13] = [
            'column' => 13, 'cell' => 'M', 'title' => 'Pnl Int', 
            'field' => 'collection_penal_interest', 'background' => 'collection',
        ];
        
        // 14 Int Arrs
        $this->_columns[self::C14] = [
            'column' => 14, 'cell' => 'N', 'title' => 'Int Arrs', 
            'field' => 'collection_interest_arrears', 'background' => 'collection',
        ];
        
        // 15 Crnt Int
        $this->_columns[self::C15] = [
            'column' => 15, 'cell' => 'O', 'title' => 'Crnt Int', 
            'field' => 'collection_current_interest', 'background' => 'collection',
        ];
        
        // 16 Prncpl Ars
        $this->_columns[self::C16] = [
            'column' => 16, 'cell' => 'P', 'title' => 'Prncpl Ars', 
            'field' => 'collection_principal_arrears', 'background' => 'collection',
        ];
        
        // 17 Crnt Prcpl
        $this->_columns[self::C17] = [
            'column' => 17, 'cell' => 'Q', 'title' => 'Crnt Prcpl', 
            'field' => 'collection_current_principal', 'background' => 'collection',
        ];
    }
    
    /**
     * payment
     */
    private function _payment()
    {
        // 18 Prepay
        $this->_columns[self::C18] = [
            'column' => 18, 'cell' => 'R', 'title' => 'Prepay', 
            'field' => 'collection_prepayment', 'background' => 'collection',
        ];
        
        // 19 Repaid
        $this->_columns[self::C19] = [
            'column' => 19, 'cell' => 'S', 'title' => 'Repaid', 
            'field' => 'collection_repaid', 'background' => 'collection',
        ];
        
        // 20 Catch
        $this->_columns[self::C20] = [
            'column' => 20, 'cell' => 'T', 'title' => 'Catch', 
            'field' => 'catch_amount', 'background' => 'collection',
        ];
        
        // 21 % ctch
        $this->_columns[self::C21] = [
            'column' => 21, 'cell' => 'U', 'title' => '% ctch', 
            'field' => 'percentage_catch', 'background' => 'collection',
        ];
    }
    
    /**
     * balance
     */
    private function _balance()
    {
        $this->_overdue();
        $this->_outstanding();
    }
    
    /**
     * balance overdue
     */
    private function _overdue()
    {
         // 22 Int OD
        $this->_columns[self::C22] = [
            'column' => 22, 'cell' => 'V', 'title' => 'Int OD', 
            'field' => 'balance_interest_overdue', 'background' => '',
        ];
        
        // 23 Pncpl OD
        $this->_columns[self::C23] = [
            'column' => 23, 'cell' => 'W', 'title' => 'Pncpl OD', 
            'field' => 'balance_principal_overdue', 'background' => '',
        ];
        
        // 24 Tot OD
        $this->_columns[self::C24] = [
            'column' => 24, 'cell' => 'X', 'title' => 'Tot OD', 
            'field' => 'balance_total_overdue', 'background' => '',
        ];
    }
    
    /**
     * balance outstanding
     */
    private function _outstanding()
    {
        // 25 Schdld POS
        $this->_columns[self::C25] = [
            'column' => 25, 'cell' => 'Y', 'title' => 'Schdld POS', 
            'field' => 'balance_scheduled_principal_outstanding', 'background' => '',
        ];
        
        // 26 Actual POS
        $this->_columns[self::C26] = [
            'column' => 26, 'cell' => 'Z', 'title' => 'Actual POS', 
            'field' => 'balance_actual_principal_outstanding', 'background' => '',
        ];
        
        // 27 Actl PIOS
        $this->_columns[self::C27] = [
            'column' => 27, 'cell' => 'AA', 'title' => 'Actl PIOS', 
            'field' => 'balance_actual_principal_interest_outstanding', 'background' => '',
        ];
    }
    
    /**
     * ageing
     */
    private function _ageing()
    {
        // 28 Ageing 1
        $this->_columns[self::C28] = [
            'column' => 28, 'cell' => 'AA', 'title' => '', 
            'field' => 'ageing_factor', 'background' => '',
        ];
        
        // 29 Ageing 2
        $this->_columns[self::C29] = [
            'column' => 29, 'cell' => 'AB', 'title' => '', 
            'field' => 'ageing_days', 'background' => '',
        ];
    }
    
}
