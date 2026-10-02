<?php
/**
 * Mobile validator
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Validator
 */

namespace Web\Form\Validator;

use Web\Form\ValidatorInterface;


class Mobile implements ValidatorInterface
{
    public function __construct() { }
    
    /**
     * validate mobile number
     * 
     * @param string $string
     * @return boolean
     */
    public function isValid($string)
    {
        if (10 != strlen($string)) {
            return false;
        }
        
        return 1 == preg_match('/^[6-9]\d+$/', $string);
    }
    
}
