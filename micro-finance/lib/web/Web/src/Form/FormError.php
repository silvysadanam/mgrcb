<?php
/**
 * Form error handler
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;


class FormError
{
    const REQUIRED = '%s is required';
    const NOT_SELECTED = '%s is not selected';
    const GIVE = 'Please give %s';
    const NO_MATCH = '%s and %s do not match';
    const INVALID = '%s is invalid';
    const ENTER = 'Please enter %s';
    const EXISTS = '%s already exists';
    const USED = '%s already used';
    const VALID_REQUIRED = 'Valid %s is required';
    const REPEATED = '%s is repeated';
    const INCORRECT = '%s is incorrect';
    const SPECIFY = 'Please specify %s';
    const NO_FORMAT = 'Format of %s is incorrect';
    const EXPECTED = '%s is expected';
    const FAILED = '%s failed';
    
    /**
     *
     * @var array 
     */
    private $_errors;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_errors = [];
    }
    
    /**
     * format error
     * 
     * @param string $title
     * @param string $type
     * @return $this
     */
    public function format($title, $type)
    {
        $this->_add(sprintf($type, $title));
        
        return $this;
    }
    
    /**
     * add error
     * 
     * @param string|array $error
     * @return $this
     */
    public function add($error)
    {
        if (is_array($error)) {
            foreach ($error as $item) {
                $this->_add($item);
            }
        } else {
            $this->_add($error);
        }
        
        return $this;
    }
    
    /**
     * set / reset errors
     * 
     * @param string|array $error
     * @return $this
     */
    public function set($error = null)
    {
        $this->_errors = [];
        if (! empty($error)) {
            $this->add($error);
        }
        
        return $this;
    }
    
    /**
     * get errors
     * 
     * @return array
     */
    public function get()
    {
        return $this->_errors;
    }
    
    /**
     * clear errors
     * 
     * @return $this
     */
    public function clear()
    {
        return $this->set();
    }
    
    /**
     * check if has errors
     * 
     * @return boolean
     */
    public function hasError()
    {
        return ! empty($this->_errors);
    }
    
    /**
     * check for no errors
     * 
     * @return boolean
     */
    public function noError()
    {
        return empty($this->_errors);
    }
    
    /**
     * add required field error
     * 
     * @param string $title
     * @return $this
     */
    public function required($title)
    {
        return $this->format($title, self::REQUIRED);
    }
    
    /**
     * add fields not matching error
     * 
     * @param string $title1
     * @param string $title2
     * @return $this
     */
    public function noMatch($title1, $title2)
    {
        $this->_add(sprintf(self::NO_MATCH, $title1, $title2));
        
        return $this;
    }
    
    /**
     * add enter field error
     * 
     * @param string $title
     * @return $this
     */
    public function enter($title)
    {
        return $this->format($title, self::ENTER);
    }
    
     /**
     * add value exists error
     * 
     * @param string $title
     * @return $this
     */
    public function exists($title)
    {
        return $this->format($title, self::EXISTS);
    }
    
    /**
     * add value used error
     * 
     * @param string $title
     * @return $this
     */
    public function used($title)
    {
        return $this->format($title, self::USED);
    }
    
    /**
     * add valid value required error
     * 
     * @param string $title
     * @return $this
     */
    public function validRequired($title)
    {
        return $this->format($title, self::VALID_REQUIRED);
    }
    
    /**
     * add value repeated error
     * 
     * @param string $title
     * @return $this
     */
    public function repeated($title)
    {
        return $this->format($title, self::REPEATED);
    }
    
    /**
     * add incorrect value error
     * 
     * @param string $title
     * @return $this
     */
    public function incorrect($title)
    {
        return $this->format($title, self::INCORRECT);
    }
    
    /**
     * add specify field error
     * 
     * @param string $title
     * @return $this
     */
    public function specify($title)
    {
        return $this->format($title, self::SPECIFY);
    }
    
    /**
     * add incorrect format error
     * 
     * @param string $title
     * @return $this
     */
    public function noFormat($title)
    {
        return $this->format($title, self::NO_FORMAT);
    }
    
    /**
     * add expected value error
     * 
     * @param string $title
     * @return $this
     */
    public function expected($title)
    {
        return $this->format($title, self::EXPECTED);
    }
    
    /**
     * add give field error
     * 
     * @param string $title
     * @return $this
     */
    public function give($title)
    {
        return $this->format($title, self::GIVE);
    }
    
    /**
     * add field not selected error
     * 
     * @param string $title
     * @return $this
     */
    public function notSelected($title)
    {
        return $this->format($title, self::NOT_SELECTED);
    }
    
    /**
     * add invalid error
     * 
     * @param string $title
     * @return $this
     */
    public function invalid($title)
    {
        return $this->format($title, self::INVALID);
    }
    
    /**
     * add action failed error
     * 
     * @param string $title
     * @return $this
     */
    public function failed($title)
    {
        return $this->format($title, self::FAILED);
    }
    
    
    /**
     * add single error message
     * 
     * @param string $error
     */
    private function _add($error)
    {
        $this->_errors[] = $error;
    }
    
}
