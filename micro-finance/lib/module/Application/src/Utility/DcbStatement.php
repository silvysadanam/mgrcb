<?php
/**
 * Application DcbStatement Utility
 * 
 * @category Application
 * @package  Application\Utility
 */

namespace Application\Utility;


class DcbStatement
{
    const COLUMNS = 29;
    
    /**
     * 
     * @var \Web\View\ViewModel
     */
    protected $view;
    
    public function __construct($view)
    {
        $this->view = $view;
    }
    
    /**
     * get statement title
     * 
     * @return string
     */
    public function title()
    {
        return 'DEMAND/COLLECTION/BALANCE STATEMENT';
    }
    
    /**
     * get number of columns
     * 
     * @return int
     */
    public function columns()
    {
        return self::COLUMNS;
    }
    
    /**
     * get second row
     * 
     * @param boolean $export
     * @return array
     */
    public function secondRow($export = false)
    {
        if ($export) {
            $br = ' ';
        } else {
            $br = '<br>';
        }
        
        $row = [
            ['CURRENT DEMAND', 7, '#CCCC99'],
            ['ARREARS DEMAND', 4, '#FFCC66'],
            ['TOTAL' . $br . 'DEMAND', 1, '#FF99CC', 2],
            ['COLLECTION', 9, '#CCCCCC'],
            ['BALANCE', 5, '#9999CC'],
            ['AGEING', 2, '#CCCC99'],
        ];
        
        return $row;
    }
    
    /**
     * get third row
     * 
     * @param boolean $short
     * @return array
     */
    public function thirdRow($short = false)
    {
        if ($short) {
            $row = [
                'Instlnt', 'Date', 'Loan OS', 'Interest', 'Principal', 'EMI', 
                'Prncpl OS', 'Pnal Int', 'Int Arrs', 'Prncpl Ars', 'Total', '', 'Pnl Int', 
                'Int Arrs','Crnt Int', 'Prncpl Ars', 'Crnt Prcpl', 'Prepay', 'Repaid',
                'Catch', 'Rpy % ctch', 'Int OD', 'Pncpl OD', 'Tot OD',
                'Schdld OS', 'Actual OS',
            ];
        } else {
            $row = [
                'Instalment', 'Date', 'Loan<br>Outstanding', 'Interest', 'Principal',
                'EMI', 'Principal<br>Outstanding', 'Penal<br>Interest', 'Interest<br>Arrears',
                'Principal<br>Arrears', 'Total', '', 'Penal<br>Interest', 
                'Interest<br>Arrears','Current<br>Interest', 'Principal<br>Arrears',
                'Current<br>Principal', 'Prepayment', 'Repaid', 'Catch', 'Repayment<br>% Catch',
                'Interest<br>Overdue', 'Principal<br>Overdue', 'Total<br>Overdue',
                'Scheduled<br>Outstanding', 'Actual<br>Outstanding',
            ];
        }
        
        return $row;
    }
    
}
