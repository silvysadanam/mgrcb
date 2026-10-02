<?php
/**
 * Form formatter
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;


class FormFormatter
{
    /**
     * Constructor
     */
    public function __construct()
    {
        
    }
    
    public function integer($number)
    {
        return (int) $number;
    }

    public function decimal($number, $decimals = 2)
    {
        return number_format($number, $decimals, '.', '');
    }
    
}
