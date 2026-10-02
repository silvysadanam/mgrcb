<?php
/**
 * Form validator
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Form\ValidatorFactory;


class FormValidator
{
    /**
     *
     * @var array 
     */
    private $_arrays = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        
    }
    
    /**
     * validate not empty string
     * 
     * @param string $string
     * @return boolean
     */
    public function notNull($string)
    {
        return strlen(trim($string)) > 0;
    }
    
    /**
     * validate empty string
     * 
     * @param string $string
     * @return boolean
     */
    public function isNull($string)
    {
        return 0 == strlen(trim($string));
    }
    
    /**
     * validate selection
     * 
     * @param string|int $number
     * @return boolean
     */
    public function selected($number)
    {
        return intval($number) > 0;
    }
    
    /**
     * validate not selection
     * 
     * @param string|int $number
     * @return boolean
     */
    public function notSelected($number)
    {
        return 0 == intval($number);
    }
    
    /**
     * validate integer number
     * 
     * @param int|string $number
     * @return boolean
     */
    public function isNumber($number)
    {
        return false !== filter_var($number, FILTER_VALIDATE_INT);
    }
    
    /**
     * validate positive integer number
     * 
     * @param int|string $number
     * @return boolean
     */
    public function isPositiveNumber($number)
    {
        if (false !== filter_var($number, FILTER_VALIDATE_INT)) {
            return $number >= 0;
        }
        
        return false;
    }
    
    /**
     * validate checkbox checked
     * 
     * @param string $field
     * @return boolean
     */
    public function checked($field)
    {
        return null !== filter_input(INPUT_POST, $field);
    }
    
    /**
     * validate checkbox not checked
     * 
     * @param string $field
     * @return boolean
     */
    public function notChecked($field)
    {
        return ! filter_has_var(INPUT_POST, $field);
    }
    
    /**
     * validate equality of strings
     * 
     * @param string $string1
     * @param string $string2
     * @return boolean
     */
    public function equal($string1, $string2)
    {
        return 0 == strcmp($string1, $string2);
    }
    
    /**
     * get length of string
     * 
     * @param string $string
     * @return int
     */
    public function length($string)
    {
        return strlen($string);
    }
    
    /**
     * validate decimal number
     * 
     * @param string $number
     * @return boolean
     */
    public function decimal($number)
    {
        if ($this->isPositiveNumber($number)) {
            return true;
        }
        if (false !== filter_var($number, FILTER_VALIDATE_FLOAT)) {
            return 1 == preg_match('/^[0-9]+[0-9.]*$/', $number);
        }
        
        return false;
    }
    
    /**
     * validate integer
     * 
     * @param int|string $number
     * @return boolean
     */
    public function integer($number)
    {
        if (false !== filter_var($number, FILTER_VALIDATE_INT)) {
            return 1 == preg_match('/^[0-9]\d*$/', $number);
        }
        
        return false;
    }
    
    /**
     * validate decimal points
     * 
     * @param string $number
     * @param int $max max number of decimals
     * @return boolean
     */
    public function decimalPoints($number, $max = 2)
    {
        if ($this->isPositiveNumber($number)) {
            return true;
        }
        if (false !== filter_var($number, FILTER_VALIDATE_FLOAT)) {
            if (1 == preg_match('/^[0-9]+[0-9.]*$/', $number)) {
                $parts = explode('.', $number);
                if (2 == count($parts)) {
                    return strlen($parts[1]) <= $max;
                }
            }
        }
        
        return false;
    }
    
    /**
     * validate if value is zero
     * 
     * @param string $value
     * @return boolean
     */
    public function zero($value)
    {
        return 1 == preg_match('/^0[0.]*$/', $value);
    }
    
    /**
     * validate if value is integer greater than zero
     * 
     * @param string $value
     * @return boolean
     */
    public function nonZeroInteger($value)
    {
        if (false !== filter_var($value, FILTER_VALIDATE_INT)) {
            return $value > 0;
        }
        
        return false;
    }
    
     /**
     * register array list for validation
     * 
     * @param string $name
     * @param array $list
     */
    public function registerArray($name, $list)
    {
        $this->_arrays[$name] = $list;
    }
    
    /**
     * validate if value is in array
     * 
     * @param string $name
     * @param string|array $value
     * @return boolean
     */
    public function inArray($name, $value)
    {
        $valid = isset($this->_arrays[$name]);
        if ($valid) {
            if (is_array($value)) {
                if (count($value) > 0) {
                    foreach ($value as $item) {
                        if (! in_array($item, $this->_arrays[$name])) {
                            $valid = false;
                            break;
                        }
                    }
                }
            } else {
                $valid = in_array($value, $this->_arrays[$name]);
            }
        }
        
        return $valid;
    }
    
    /**
     * magic method to call dynamic validator
     * 
     * @param string $name
     * @param array $arguments
     * @return boolean
     */
    public function __call($name, $arguments)
    {
        $validator = ValidatorFactory::load($name);
        
        return call_user_func_array([$validator, 'isValid'], $arguments);
    }

}
