<?php
/**
 * WebAddress validator
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Validator
 */

namespace Web\Form\Validator;

use Web\Form\ValidatorInterface;


class WebAddress implements ValidatorInterface
{
    public function __construct() { }
    
    /**
     * validate URL
     * 
     * @param string $string
     * @return boolean
     */
    public function isValid($string)
    {
        if (1 == preg_match('/^[a-z0-9]+[^.]*\.[a-z0-9.-]+[a-z]/i', $string)) {
            return 0 == preg_match('/[.-][.-]+/', $string);
        }
        return false;
    }
    
}
