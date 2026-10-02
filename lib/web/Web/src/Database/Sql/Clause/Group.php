<?php
/**
 * Database Sql Clause Group
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Clause
 */

namespace Web\Database\Sql\Clause;

use Web\Database\Sql\Operator;


final class Group
{
    /**
     *
     * @var array 
     */
    private $_groups;
    
    public function __construct()
    { 
        $this->_groups = [];
    }
    
    /**
     * set group
     * 
     * @param string|array $group
     */
    public function set($group)
    {
        if (is_array($group)) {
            $this->_groups = $group;
        } else {
            $this->_groups = [$group];
        }
    }
    
    /**
     * add group
     * 
     * @param string $column
     */
    public function add($column)
    {
        $this->_groups[] = $this->_quoteIdentifier($column);
    }
    
    /**
     * get group
     * 
     * @return string
     */
    public function get()
    {
        return implode(Operator::COMMA, $this->_groups);
    }
    
    
    /**
     * quote identifier
     * 
     * @param string $string
     * @return string
     */
    private function _quoteIdentifier($string)
    {
        if (1 == preg_match('/\([^)]*\)/', $string)) {
            return $string;
        }
        return Operator::BACKQUOTE 
                . str_replace('.', '`.`', str_replace(Operator::BACKQUOTE, '', trim($string, Operator::BACKQUOTE))) 
                . Operator::BACKQUOTE;
    }
    
}
