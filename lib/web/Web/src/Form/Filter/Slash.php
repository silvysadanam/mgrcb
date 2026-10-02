<?php
/**
 * slash filter - strip slashes
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Slash implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter strip slashes
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return stripslashes($string);
    }
    
}
