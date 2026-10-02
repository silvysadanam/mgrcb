<?php
/**
 * PaymentMonth View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;
use Web\Utility\DateHelper;


class PaymentMonth extends ViewHelper
{
    /**
     * format payment month
     * 
     * @param int $year
     * @param int $month
     * @return string
     */
    public function paymentMonth($year, $month)
    {
        $date = DateHelper::getDateTime();
        $date->setDate($year, $month, 10);
        
        return $date->format('F Y');
    }
    
}
