<?php
/**
 * Database Sql Clause Order
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Clause
 */

namespace Web\Database\Sql\Clause;

use Web\Database\Sql\Operator;


final class Order
{
    /**
     *
     * @var string 
     */
    private $_order;
    
    public function __construct()
    { 
        $this->_order = [];
    }
    
    /**
     * set order
     * 
     * @param string|array $order
     */
    public function set($order)
    {
        if (! empty($order)) {
            if (is_array($order)) {
                $this->_order = $order;
            } else {
                $this->_order = [$order];
            }
        }
    }
    
    /**
     * add order
     * 
     * @param string $column
     * @param string $sort
     */
    public function add($column, $sort = null)
    {
        if (null === $sort) {
            if (is_array($column)) {
                $desc = '';
                if (isset($column[1])) {
                    if ('desc' == strtolower(trim($column[1]))) {
                        $desc = ' DESC';
                    }
                }
                $this->_order[] = $this->_quoteIdentifier($column[0]) . $desc;
            } else {
                if (false !== strpos($column, ',')) {
                    $parts = explode(',', trim($column));
                    foreach ($parts as $item) {
                        if (false !== strpos($item, ' ')) {
                            $sections = explode(' ', $item);
                            $desc = '';
                            if ('desc' == strtolower(trim($sections[1]))) {
                                $desc = ' DESC';
                            }
                            $this->_order[] = $this->_quoteIdentifier($sections[0]) . $desc;
                        } else {
                            $this->_order[] = $this->_quoteIdentifier($item);
                        }
                    }
                } elseif (false !== strpos($column, ' ')) {
                    $this->_order[] = trim($column);
                } else {
                    $this->_order[] = $this->_quoteIdentifier($column);
                }
            }
        } else {
            $desc = '';
            if ('desc' == strtolower(trim($sort))) {
                $desc = ' DESC';
            }
            $this->_order[] = $this->_quoteIdentifier($column) . $desc;
        }
    }
    
    /**
     * get order
     * 
     * @return string
     */
    public function get()
    {
        return implode(Operator::COMMA, $this->_order);
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
                . str_replace('.', '`.`', str_replace(Operator::BACKQUOTE, '', trim($string))) 
                . Operator::BACKQUOTE;
    }
    
}
