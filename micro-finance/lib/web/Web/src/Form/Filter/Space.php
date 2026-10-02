<?php
/**
 * Space filter - trim string
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Space implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter spaces
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return trim($string);
    }
    
}
