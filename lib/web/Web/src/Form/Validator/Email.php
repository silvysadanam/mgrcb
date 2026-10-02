<?php
/**
 * Email validator
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Validator
 */

namespace Web\Form\Validator;

use Web\Form\ValidatorInterface;


class Email implements ValidatorInterface
{
    public function __construct() { }
    
    /**
     * validate email
     * 
     * @param string $string
     * @return boolean
     */
    public function isValid($string)
    {
        return $string === filter_var($string, FILTER_VALIDATE_EMAIL);
    }
    
}
