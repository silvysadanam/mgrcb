<?php
/**
 * Number filter - trim string
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Number implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter number
     * 
     * @param string $string
     * @return int
     */
    public function filter($string)
    {
        return (int) $string;
    }
    
}
