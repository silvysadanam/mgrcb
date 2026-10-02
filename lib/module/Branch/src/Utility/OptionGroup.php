<?php
/**
 * Branch OptionGroup Utility
 * 
 * @category Branch
 * @package  Branch\Utility
 */

namespace Branch\Utility;


class OptionGroup
{
    /**
     *
     * @var array 
     */
    private $_groups = [];
    
    /**
     *
     * @var array 
     */
    private $_options = [];
    
    public function __construct()
    {
        
    }
    
    /**
     * set group options
     * 
     * @param array $groups
     * @return $this
     */
    public function setGroups($groups)
    {
        $this->_groups = $groups;
        
        return $this;
    }
    
    /**
     * set options
     * 
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->_options = $options;
        
        return $this;
    }
    
    /**
     * build select group
     * 
     * @param string $id
     * @param string $field
     * @return array
     */
    public function build($id, $field)
    {
        $options = [];
        if (empty($this->_groups) || empty($this->_options)) {
            return $options;
        }
        
        $list = [];
        foreach ($this->_options as $item) {
            $column = $item->{$id};
            if (! isset($list[$column])) {
                $list[$column] = [];
            }
            $list[$column][$item->id] = $item->{$field};
        }
        
        if (! empty($list)) {
            foreach ($this->_groups as $key => $title) {
                if (isset($list[$key])) {
                    $options[$title] = $list[$key];
                }
            }
        }
        
        return $options;
    }
    
}
