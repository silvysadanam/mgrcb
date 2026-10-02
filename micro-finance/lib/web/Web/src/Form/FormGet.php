<?php
/**
 * Form Get - for GET method forms
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Utility\DateHelper;
use DateTime;


class FormGet
{
    const INTEGER = 'int';
    const INTEGER_MIN = 'min';
    const INTEGER_MAX = 'max';
    const COLLECTION = 'list';
    const DATEPICKER = 'date';
    const MULTIPLE = 'multiple';

    /**
     *
     * @var array 
     */
    private $_fields = [];
    
    /**
     *
     * @var array 
     */
    private $_values = [];
    
    /**
     *
     * @var array 
     */
    private $_numeric = [];
    
    /**
     *
     * @var array 
     */
    private $_filters = [];
    
    /**
     *
     * @var array 
     */
    private $_multiple = [];
    
    /**
     * Constructor
     */
	public function __construct()
    {

    }
    
    /**
     * add field to form
     * 
     * @param string $name
     */
    public function addField($name)
    {
        if (! in_array($name, $this->_fields)) {
            $this->_fields[] = $name;
        }
    }
    
    /**
     * add fields to form
     * @param array $fields
     */
    public function addFields($fields)
    {
        foreach ($fields as $name) {
            $this->addField($name);
        }
    }
    
    /**
     * set numeric fields
     * 
     * @param array|string $fields
     */
    public function numeric($fields)
    {
        if (! is_array($fields)) {
            $fields = [ $fields ];
        }
        foreach ($fields as $field) {
            if (in_array($field, $this->_fields)) {
                $this->_numeric[] = $field;
            }
        }
    }
    
    /**
     * set multiple (array) fields
     * 
     * @param array|string $fields
     */
    public function multiple($fields)
    {
        if (! is_array($fields)) {
            $fields = [ $fields ];
        }
        foreach ($fields as $field) {
            if (in_array($field, $this->_fields)) {
                $this->_multiple[] = $field;
            }
        }
    }
    
    /**
     * get multiple filter
     * 
     * @param array $values
     * @return array
     */
    public function getMultipleFilter($values)
    {
        return [self::MULTIPLE => $values];
    }
    
    
    /**
     * add field filters
     * 
     * @param string $field
     * @param string|array $options
     */
    public function addFilter($field, $options)
    {
        if (in_array($field, $this->_fields)) {
            $this->_filters[$field] = $options;
        }
    }
    
    /**
     * set filters
     * 
     * @param array $options
     */
    public function setFilters($options)
    {
        foreach ($options as $field => $item) {
            $this->addFilter($field, $item);
        }
    }
    
    /**
     * reset form
     */
    public function reset()
    {
        $this->_fields = [];
        $this->_values = [];
        $this->_numeric = [];
        $this->_filters = [];
        $this->_multiple = [];
        $this->error->clear();
    }
    
    /**
     * get form fields
     * 
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }
    
    /**
     * populate form values
     * 
     * @param array|\stdClass $raw
     * @param boolean $direct
     */
    public function populate($raw)
    {
        $data = $this->toArray($raw);
        if (! empty($data)) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->_fields)) {
                    $this->_values[$key] = $value;
                }
            }
            
            foreach ($this->_fields as $key) {
                if (! isset($this->_values[$key])) {
                    if (in_array($key, $this->_numeric)) {
                        $this->_values[$key] = 0;
                    } else {
                        $this->_values[$key] = '';
                    }
                }
            }
        }
    }

    /**
     * load filter values
     * 
     * @return array
     */
    public function load()
    {
        if (! empty($this->_fields)) {
            foreach ($this->_fields as $field) {
                if (in_array($field, $this->_numeric)) {
                    $this->_values[$field] = $this->_getNumber($field);
                } elseif (in_array($field, $this->_multiple)) {
                    $this->_values[$field] = $this->_getArray($field);
                } else {
                    $this->_values[$field] = $this->_getString($field);
                }
                if (isset($this->_filters[$field])) {
                    $this->_applyFilters($field);
                }
            }
        }
        
        return $this->_values;
    }
    
    /**
     * get field value
     * 
     * @param string $key
     * @param boolean $escape
     * @return string|array
     */
    public function getValue($key, $escape = false)
    {
        $value = '';
        if (isset($this->_values[$key])) {
            $value = $this->_values[$key];
            if ($escape && ! is_array($value)) {
                $value = $this->escape($value);
            }
        }
        
        return $value;
    }
    
    /**
     * get all field values
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }
    
    /**
     * get escaped value for view
     * 
     * @param string $key
     * @return string
     */
    public function getHtmlValue($key)
    {
        return $this->getValue($key, true);
    }
    
    /**
     * get escaped value for view
     * 
     * @param string $key
     * @return string
     */
    public function getEscapedValue($key)
    {
        return $this->getValue($key, true);
    }
    
    /**
     * get numeric value of field
     * 
     * @param string $key
     * @return int
     */
    public function getNumericValue($key)
    {
        return (int) $this->getValue($key);
    }
    
    /**
     * HTML escape string
     * 
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8', true);
    }
    
    /**
     * convert object to array
     * 
     * @param stdClass $object
     * @return array
     */
    public function toArray($object)
    {
        if (! is_array($object)) {
            return (array) $object;
        }
        
        return $object;
    }

    /**
     * get yes or no filter
     * 
     * @return array
     */
    public function getYesOrNoFilter()
    {
        return [self::COLLECTION => [0, 1]];
    }
    
    /**
     * get minimum and maximum values filter
     * 
     * @param int $max
     * @param int $min
     * @return array
     */
    public function getMinMaxFilter($max, $min = 0)
    {
        return [
            self::INTEGER_MIN => $min,
            self::INTEGER_MAX => $max,
        ];
    }
    
    /**
     * get date filter
     * 
     * @return string
     */
    public function getDateFilter()
    {
        return self::DATEPICKER;
    }
    
    /**
     * get collection filter
     * 
     * @param array $collection
     * @return array
     */
    public function getCollectionFilter($collection)
    {
        return [self::COLLECTION => $collection];
    }
    
    /**
     * check _GET key
     * 
     * @param string $name
     * @return true
     */
    public function hasGet($name)
    {
        return filter_has_var(INPUT_GET, $name);
    }
    
    /**
     * get maximum value 2 filter
     * 
     * @param int $min
     * @return array
     */
    public function getMaxTwoFilter($min = 0)
    {
        return [
            self::INTEGER_MIN => $min,
            self::INTEGER_MAX => 2,
        ];
    }
    
    /**
     * get maximum value 3 filter
     * 
     * @param int $min
     * @return array
     */
    public function getMaxThreeFilter($min = 0)
    {
        return [
            self::INTEGER_MIN => $min,
            self::INTEGER_MAX => 3,
        ];
    }
    
    /**
     * get $_GET value as string
     * 
     * @param string $name
     * @return string
     */
    private function _getString($name)
    {
        if ($this->_isGet($name)) {
            return $this->_get($name);
        } elseif (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
        
        return '';
    }
    
    /**
     * get $_GET value as number
     * 
     * @param string $name
     * @return string
     */
    private function _getNumber($name)
    {
        if ($this->_isGet($name)) {
            return (int) filter_input(INPUT_GET, $name, FILTER_SANITIZE_NUMBER_INT);
        } elseif (isset($this->_values[$name])) {
            return (int) $this->_values[$name];
        }
        
        return 0;
    }
    
    /**
     * get $_GET value as array
     * 
     * @param string $name
     * @return array
     */
    private function _getArray($name)
    {
        if ($this->_isGet($name)) {
            return (array) filter_input(INPUT_GET, $name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        } elseif (isset($this->_values[$name])) {
            return (array) $this->_values[$name];
        }
        
        return [];
    }
    
    /**
     * get $_GET param
     * 
     * @param string $name
     * @return string
     */
    private function _get($name)
    {
        return filter_input(INPUT_GET, $name, FILTER_DEFAULT);
    }
    
    /**
     * check if field is in $_GET
     * 
     * @param string $name
     * @return boolean
     */
    private function _isGet($name)
    {
        return null !== filter_input(INPUT_GET, $name, FILTER_DEFAULT);
    }
    
    /**
     * apply filters
     * 
     * @param string $field
     */
    private function _applyFilters($field)
    {
        $value = $this->_values[$field];
        $options = $this->_filters[$field];
        if (is_array($options)) {
            foreach ($options as $key => $item) {
                switch($key) {
                    case self::INTEGER;
                        $this->_values[$field] = (int) $value;
                        break;

                    case self::INTEGER_MIN;
                        $this->_values[$field] = (int) $value;
                        if ($this->_values[$field] < $item) {
                            $this->_values[$field] = $item;
                        }
                        break;

                    case self::INTEGER_MAX;
                        $this->_values[$field] = (int) $value;
                        if ($this->_values[$field] > $item) {
                            $this->_values[$field] = $item;
                        }
                        break;

                    case self::COLLECTION;
                        if (is_array($item)) {
                            if (! in_array($value, $item)) {
                                $this->_values[$field] = current($item);
                            }
                        }
                        break;
                        
                    case self::MULTIPLE;
                        if (is_array($item)) {
                            if (! empty($value) && is_array($value)) {
                                $newValues = [];
                                foreach ($value as $arrayItem) {
                                    if (in_array($arrayItem, $item)) {
                                        $newValues[] = $arrayItem;
                                    }
                                }
                                $this->_values[$field] = $newValues;
                            }
                        }
                        break;

                    default:
                        break;
                }
            }

        } elseif (self::INTEGER == $options) {
            $this->_values[$field] = (int) $value;
        } elseif (self::DATEPICKER == $options) {
            if ('' != $value) {
                if (false !== DateTime::createFromFormat(DateHelper::DATEPICKER, $value)) {
                    $this->_values[$field] = $value;
                } else {
                    $this->_values[$field] = '';
                }
            }
        }
    }
    
}
