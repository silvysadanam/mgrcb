<?php
/**
 * Amount filter - 2 decimal format
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Amount implements FilterInterface
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
        return sprintf('%0.2f', $string);
    }
    
}
