<?php
/**
 * Application Report Generator
 * 
 * @category Application
 * @package  Application\Generator
 */

namespace Application\Generator;

use Application\Generator\Generator;

use Application\Utility\LoanHelper;

use Web\View\Support\LookupStore;


class Report extends Generator
{
    /**
     * 
     * @var array
     */
    protected $_percentages = [];
    
    protected $_ageingKeys;
    
    public function __construct()
    {
        parent::__construct();
        $this->_generatorType = static::TYPE_REPORT;
        $this->_ageingKeys = array_keys(LoanHelper::ageingList());
    }
    
    /**
     * load lookups
     */
    protected function _loadLookups()
    {
        LookupStore::register('ageing_title', LoanHelper::ageingList());
    }
    
    protected function _fixPercentageLoans()
    {
        $this->_fixPercentages('loans');
    }
    
    protected function _fixPercentagePortfolio()
    {
        $this->_fixPercentages('portfolio');
    }
    
    protected function _fixPercentageMembers()
    {
        $this->_fixPercentages('members');
    }
    
    protected function _llrAmount($percentage, $par)
    {
        if ($percentage > 0) {
            if (100 == $percentage) {
                return $par;
            } elseif ($par > 0) {
                return LoanHelper::amount($par * 0.01 * $percentage);
            }
        }
        
        return 0;
    }
    
    
    private function _fixPercentages($key)
    {
        $percentage = 'percentage_' . $key;
        $sum = array_sum($this->_percentages[$key]);
        if ($sum > 100) {
            $difference = $sum - 100;
            $temp1 = $this->_percentages[$key];
            arsort($temp1);
            foreach ($temp1 as $key1 => $value1) {
                if ($value1 > 1) {
                    $this->_entries[$key1][$percentage] -= 1;
                    --$difference;
                }
                if (0 == $difference) {
                    break;
                }
            }
        } elseif ($sum < 100) {
            $diff1 = 100 - $sum;
            $max1 = 0;
            $key1 = 0;
            foreach ($this->_entries as $index1 => $list1) {
                if ($list1[$percentage] > $max1) {
                    $max1 = $list1[$percentage];
                    $key1 = $index1;
                }
            }
            if ($key1 > 0) {
                $this->_entries[$key1][$percentage] += $diff1;
            }
        }
    }
    
}
