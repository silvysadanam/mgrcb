<?php
/**
 * Escape filter - htmlspecialchars string
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Filter
 */

namespace Web\Form\Filter;

use Web\Form\FilterInterface;


class Escape implements FilterInterface
{
    public function __construct() { }
    
    /**
     * filter escaped
     * 
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
}
