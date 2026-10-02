<?php
/**
 * SQL Date filter - convert ot SQL date
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;
use Web\Utility\DateHelper;


class SqlDate implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter as amount
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        if ('' != $string) {
            return DateHelper::fromDatepicker($string);
        }
        return '';
    }
    
}
