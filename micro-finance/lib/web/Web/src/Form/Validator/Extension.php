<?php
/**
 * Extension validator
 *
 * @category   Web
 * @package    Web\Form
 * @subpackage Web\Form\Validator
 */

namespace Web\Form\Validator;


class Extension
{   
    public function __construct() { }
    
    /**
     * validate extension
     * 
     * @param string $string
     * @param string|array $extension
     * @return boolean
     */
    public function isValid($string, $extension = 'jpg')
    {
        if (is_array($extension)) {
            if (false !== strpos($string, '.')) {
                $parts = explode('.', $string);
                return in_array(end($parts), $extension);
            }
            return false;
        }
        $pattern = '/\.' . quotemeta(trim($extension, '.')) . '$/';
        return 1 == preg_match($pattern, trim($string));
    }
    
}
