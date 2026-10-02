<?php
/**
 * Tag filter - strip tags
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Tag implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter strip tags
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return strip_tags($string);
    }
    
}
