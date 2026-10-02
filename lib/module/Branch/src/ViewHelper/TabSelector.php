<?php
/**
 * TabSelector View helper
 * 
 * @category   Branch
 * @package    Branch\ViewHelper
 */

namespace Branch\ViewHelper;

use Web\View\ViewHelper;
use Branch\Constant\Tab;


class TabSelector extends ViewHelper
{
    const ACTIVE = ' active';
    
    /**
     * get tab selector
     * 
     * @param int $current
     * @return array
     */
    public function tabSelector($current = Tab::DASHBOARD)
    {
        $tabs = [
            Tab::DASHBOARD, Tab::MEMBERSHIP, Tab::AUCTION, Tab::AUCTION_PAYMENT,
            Tab::PROFILE, Tab::DEDUCTION_BILL, Tab::DEDUCTION_BILL_NEW, Tab::ADVANCE,
            Tab::ADVANCE_NEW, Tab::TRANSACTION,
        ];
        
        $list = array_fill_keys($tabs, '');
        $list[$current] = self::ACTIVE;
        
        return $list;
    }
    
}
