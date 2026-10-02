<?php
/**
 * Password validator
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Validator
 */

namespace Web\Form\Validator;

use Web\Form\ValidatorInterface;


class Password implements ValidatorInterface
{
    const MIN = 6;
    const MAX = 20;
    const REGEXP = '/^[^[:space:]]+$/';
    
    public function __construct() { }
    
    /**
     * validate password
     * 
     * @param string $string
     * @return boolean
     */
    public function isValid($string)
    {
        $valid = false;
        $length = strlen($string);
        if ($length >= self::MIN && $length <=  self::MAX) {
            $valid = 1 == preg_match(self::REGEXP, $string);
        }
        return $valid;
    }
    
}
