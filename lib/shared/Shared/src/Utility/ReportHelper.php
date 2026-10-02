<?php
/**
 * Shared ReportHelper Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{DeductionType, DeductionGroup};
use Shared\Constant\DeductionConfig;

use Web\Utility\DateHelper;


class ReportHelper
{
    const CONVERSION_NO = 0;
    const CONVERSION_YES = 1;
    const CONVERSION_PERCENTAGE = 2;
    const CONVERSION_PER_BILL = 3;
    const CONVERSION_FIXED = 4;
    
    const GROUP_CONTRIBUTION = 1;
    const GROUP_LOAN = 2;
    const GROUP_SAVINGS = 3;
    
    /**
     * get conversion options
     * 
     * @return array
     */
    public static function conversionOptions()
    {
        return [
            self::CONVERSION_NO => 'No',
            self::CONVERSION_YES => 'Yes',
            self::CONVERSION_PERCENTAGE => 'Percentage',
            self::CONVERSION_PER_BILL => 'Amount Per Bill',
            self::CONVERSION_FIXED => 'Fixed Amount',
        ];
    }
    
    /**
     * get deduction display text
     * 
     * @param int $type
     * @param mixed $value
     * @return string
     */
    public static function displayText($type, $value)
    {
        $text = '';
        switch ($type) {
            case self::CONVERSION_YES:
                $text = 'Yes';
                break;
            
            case self::CONVERSION_PERCENTAGE:
                $text = $value . '%';
                break;
            
            case self::CONVERSION_PER_BILL:
                $text = 'Rs. ' . $value . ' per bill';
                break;
            
            case self::CONVERSION_FIXED:
                $text = 'Rs. ' . $value;
                break;
            
            default:
                break;
        }
        
        return $text;
    }
    
    /**
     * get deduction types
     * 
     * @return array
     */
    public static function deductionTypes()
    {
        $model = new DeductionType();
        return $model->selectPairs(['id', 'deduction_type'], null, 'priority');
    }
    
    /**
     * get deduction type groups
     * 
     * @return array
     */
    public static function deductionTypeGroups()
    {
        $model = new DeductionType();
        return $model->selectPairs(['id', 'deduction_group_id'], null, 'priority');
    }
    
    /**
     * get deduction types in malayalam
     * 
     * @return array
     */
    public static function malayalamDeductionTypes()
    {
        $model = new DeductionType();
        return $model->selectPairs(['id', 'malayalam']);
    }
    
     /**
     * get combined deduction types
     * 
     * @return array
     */
    public static function combinedDeductionTypes()
    {
        $model = new DeductionType();
        $list = $model->selectFields(['id', 'deduction_type', 'malayalam']);
        $combined = [];
        foreach ($list as $item) {
            $combined[$item->id] = $item->deduction_type . ' / ' . $item->malayalam;
        }
        
        return $combined;
    }
    
    /**
     * get deduction type ids
     * 
     * @return array
     */
    public static function deductionTypeIds()
    {
        $model = new DeductionType();
        return $model->selectIds(null, 'priority');
    }
    
    /**
     * get deduction groups
     * 
     * @return array
     */
    public static function deductionGroups()
    {
        $model = new DeductionGroup();
        return $model->selectPairs(['id', 'deduction_group'], null, 'display_order');
    }
    
    /**
     * get grouped deduction types
     * 
     * @return array
     */
    public static function groupedDeductionTypes()
    {
        $model = new DeductionType();
        $list = $model->select(null, 'priority');
        $grouped = [];
        foreach ($list as $item) {
            if (! isset($grouped[$item->deduction_group_id])) {
                $grouped[$item->deduction_group_id] = [];
            }
            
            $grouped[$item->deduction_group_id][] = $item;
        }
        
        return $grouped;
    }
    
    /**
     * get grouped deduction type ids
     * 
     * @return array
     */
    public static function groupedDeductionTypeIds()
    {
        $model = new DeductionType();
        $list = $model->select(null, 'priority');
        $grouped = [];
        foreach ($list as $item) {
            if (! isset($grouped[$item->deduction_group_id])) {
                $grouped[$item->deduction_group_id] = [];
            }
            
            $grouped[$item->deduction_group_id][] = $item->id;
        }
        
        return $grouped;
    }
    
    /**
     * get grouped deduction type ids by selection
     * 
     * @return array
     */
    public static function groupedDeductionTypeIdsSelection($selected)
    {
        $model = new DeductionType();
        $sql = $model->getSql();
        $sql->whereIn('id', $selected);
        $list = $model->select($sql->getWhere(), 'priority');
        $grouped = [];
        foreach ($list as $item) {
            if (! isset($grouped[$item->deduction_group_id])) {
                $grouped[$item->deduction_group_id] = [];
            }
            
            $grouped[$item->deduction_group_id][] = $item->id;
        }
        
        return $grouped;
    }
    
    /**
     * get deduction amount
     * 
     * @param \stdClass $deduction
     * @param int $amount
     * @param int $balance
     * @param int $bills
     * @return int
     */
    public static function getDeduction($deduction, $amount, $balance, $bills = 1)
    {
        if (0 == $amount) {
            return 0;
        }
        
        if (1 != $bills) {
            $bills = 1;
        }
        
        $value = 0;
        switch ($deduction->conversion_type) {
            case self::CONVERSION_PERCENTAGE:
                $percentage = intval(0.01 * $deduction->deduction_factor * $amount);
                //echo $percentage . ' -- ';
                if ($percentage > 0) {
                    $value = min($percentage, $balance);
                }
                break;
            
            case self::CONVERSION_PER_BILL:
                $value = min($deduction->deduction_factor * $bills, $balance);
                break;
            
            case self::CONVERSION_FIXED:
                $value = min($deduction->deduction_factor, $balance);
                break;
            
            default:
                break;
        }
        
        return $value;
    }
    
    public static function getDeductionTypeName($deductionTypeId)
    {
        $model = new DeductionType();
        
        return $model->selectColumnById('deduction_type', $deductionTypeId);
    }
    
    /**
     * get withdrawal deductions
     * 
     * @return array
     */
    public static function withdrawal()
    {
        $model = new DeductionType();
        return $model->selectIds(['withdrawal_type' => DeductionConfig::WITHDRAWAL_MEMBER]);
    }
    
    /**
     * get withdrawal only deductions
     * 
     * @return array
     */
    public static function withdrawalOnly()
    {
        $model = new DeductionType();
        $sql = $model->getSql();
        $sql->whereId('withdrawal_type', DeductionConfig::WITHDRAWAL_MEMBER)
            ->whereZero('advance');
        return $model->selectIds($sql->getWhere());
    }
    
    
    /**
     * get advance deductions
     * 
     * @return array
     */
    public static function advance()
    {
        $model = new DeductionType();
        $sql = $model->getSql();
        $sql->whereId('withdrawal_type', DeductionConfig::WITHDRAWAL_MEMBER)
            ->whereOne('advance');
        
        return $model->selectIds($sql->getWhere());
    }
    
    /**
     * get grouped deduction selector
     * 
     * @param array $selected
     * @return array
     */
    public static function groupedDeductionSelector($selected)
    {
        $groups = self::deductionGroups();
        $grouped = self::groupedDeductionTypeIdsSelection($selected);
        $list = [];
        foreach ($groups as $groupId => $group) {
            if (isset($grouped[$groupId])) {
                $list[$groupId] = $grouped[$groupId];
            }
        }
        
        $combined = self::combinedDeductionTypes();
        $options = [];
        foreach ($list as $id => $items) {
            $block = [];
            foreach ($items as $type) {
                $block[$type] = $combined[$type];
            }
            
            $options[$groups[$id]] = $block;
        }
        
        return $options;
    }
    
    public static function financialYear()
    {
        $today = DateHelper::getDateTime();
        $year = $today->format('Y');
        $month = $today->format('n');
        if (4 == $month) {
            $end = DateHelper::getDateTime();
            $end->setDate($year, 3, 31);
            $endDate = $end->format(DateHelper::DATEPICKER);
            $end->setDate($year - 1, 4, 1);
            $startDate = $end->format(DateHelper::DATEPICKER);
        } else {
            $today->setDate($year, $month, 1);
            $today->modify('-10 days');
            $today->setDate($today->format('Y'), $today->format('n'), $today->format('t'));
            $endDate = $today->format(DateHelper::DATEPICKER);
            if ($month > 4) {
                $today->setDate($year, 4, 1);
            } else {
                $today->setDate($year - 1, 4, 1);
            }
            $startDate = $today->format(DateHelper::DATEPICKER);
        }
        
        return ['start' => $startDate, 'end' => $endDate];
    }
    
     public static function currentFinancialYear()
    {
        $today = DateHelper::getDateTime();
        $year = $today->format('Y');
        $month = $today->format('n');
        if ($month >= 4) {
            $end = DateHelper::getDateTime();
            $end->setDate($year + 1, 3, 31);
            $endDate = $end->format(DateHelper::DATEPICKER);
            $end->setDate($year, 4, 1);
            $startDate = $end->format(DateHelper::DATEPICKER);
        } else {
            $today->setDate($year, $month, 1);
            $today->modify('-10 days');
            $today->setDate($today->format('Y'), $today->format('n'), $today->format('t'));
            $endDate = $today->format(DateHelper::DATEPICKER);
            if ($month > 4) {
                $today->setDate($year, 4, 1);
            } else {
                $today->setDate($year - 1, 4, 1);
            }
            $startDate = $today->format(DateHelper::DATEPICKER);
        }
        
        return ['start' => $startDate, 'end' => $endDate];
    }
    
}
