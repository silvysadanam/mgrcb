<?php
/**
 * Quote filter - add magic quotes
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Quote implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter add slashes
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return addslashes($string);
    }
    
}
