<?php
/**
 * Form filter
 *
 * @category   Web
 * @package    Web\Form
 */

namespace Web\Form;

use Web\Form\FilterFactory;


class FormFilter
{
    const SPACE = 'space';
    const NUMBER = 'number';
    const ESCAPE = 'escape';
    const TAG = 'tag';
    const SLASH = 'slash';
    const QUOTE = 'quote';
    const AMOUNT = 'amount';
    const SQL_DATE = 'sqlDate';
    
    /**
     *
     * @var array 
     */
    private $_filters = [];
    
    /**
     *
     * @var boolean 
     */
    private $_filtered = false;
    
    /**
     *
     * @var array 
     */
    private $_values = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        
    }
    
    /**
     * add filter
     * 
     * @param string $field
     * @param array|string $options
     */
    public function add($field, $options)
    {
        $this->_filters[$field] = $options;
    }
    
    /**
     * apply filters
     * 
     * @return array
     */
    public function run($values = null)
    {
        if (! $this->_filtered) {
            if (null !== $values) {
                $this->setValues($values);
            }
            $this->_filtered = true;
            if (! empty($this->_filters) && ! empty($this->_values)) {
                foreach ($this->_filters as $field => $filters) {
                    if (isset($this->_values[$field])) {
                        if (! is_array($filters)) {
                            $list = explode('|', $filters);
                        } else {
                            $list = $filters;
                        }
                        foreach ($list as $item) {
                            if (FilterFactory::is($item)) {
                                $this->_applyFilter($item, $field);
                            }
                        }
                        
                    }
                }
            }
        }
        
        return $this->_values;
    }
    
    /**
     * set values
     * 
     * @param array $values
     */
    public function setValues($values)
    {
        $this->_values = $values;
    }
    
    /**
     * get filtered values
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }
    
    /**
     * check if already filtered
     * 
     * @return boolean
     */
    public function isFiltered()
    {
        return $this->_filtered;
    }
    
    
    /**
     * apply filter to field
     * 
     * @param string $name
     * @param string $field
     */
    private function _applyFilter($name, $field)
    {
        $filter = FilterFactory::load($name);
        $this->_values[$field] = $filter->filter($this->_values[$field]);
    }

}
