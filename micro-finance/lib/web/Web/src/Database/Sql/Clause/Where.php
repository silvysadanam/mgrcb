<?php
/**
 * Database Sql Clause Where
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Clause
 */

namespace Web\Database\Sql\Clause;

use Web\Database\SqlHelper;
use Web\Database\Sql\Keyword;


final class Where
{
    private $_where = [];
    private $_helper;
    
    public function __construct($adapter = null)
    {
        $this->_helper = new SqlHelper($adapter);
    }
    
    /**
     * get clause
     * 
     * @param boolean $and
     * @return string
     */
    public function get($and = true)
    {
        if ($and) {
            $glue = Keyword::WHERE_AND;
        } else {
            $glue = Keyword::WHERE_OR;
        }
        
        return $this->_helper->combine($this->_where, $glue);
    }
    
    /**
     * add where condition
     * 
     * @param string|array $where
     * @return \Web\Database\Sql\Clause\Where
     */
    public function add($where)
    {
        if (is_array($where)) {
            $args = count($where);
            if (1 == $args) {
                $key = key($where);
                if (! is_numeric($key)) {
                    $this->_add($this->_helper->equalTo($key, current($where)));
                } else {
                    $this->_add(current($where));
                }
            } else {
                if (2 == $args) {
                    $this->_add($this->_helper->equalTo($where[0], $where[1]));
                } elseif (3 == $args) {
                    $this->_add($this->_helper->format('%s ' . $where[2] . ' %s', $where[0], $this->_helper->quote($where[1])));
                }
            }
        } elseif (! is_null($where)) {
            $this->_add($where);
        }
        
        return $this;
    }
    
    /**
     * WHERE column = value
     * 
     * @param string $column
     * @param string $value
     */
    public function equal($column, $value)
    {
        $this->_add($this->_helper->equalTo($column, $value));
    }
    
    /**
     * WHERE column = id
     * 
     * @param int $id
     * @param string $column
     */
    public function id($id, $column = 'id')
    {
        $this->_add($this->_helper->equalTo($column, $id, true));
    }
    
    /**
     * WHERE column LIKE value
     * 
     * @param string $column
     * @param string $value
     */
    public function like($column, $value)
    {
        $this->_add($this->_helper->like($column, $value));
    }
    
    /**
     * WHERE column NOT LIKE value
     * 
     * @param string $column
     * @param string $value
     */
    public function notLike($column, $value)
    {
        $this->_add($this->_helper->notLike($column, $value));
    }
    
    /**
     * WHERE column > value
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return \Web\Database\Sql\Clause\Where
     */
    public function greater($column, $value, $integer = true)
    {
        $this->_add($this->_helper->greaterThan($column, $value, $integer));
        
        return $this;
    }
    
    /**
     * WHERE column >= value
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return \Web\Database\Sql\Clause\Where
     */
    public function greaterOrEqual($column, $value, $integer = true)
    {
        $this->_add($this->_helper->greaterThanOrEqualTo($column, $value, $integer));
        
        return $this;
    }
    
    /**
     * WHERE column < value
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return \Web\Database\Sql\Clause\Where
     */
    public function less($column, $value, $integer = true)
    {
        $this->_add($this->_helper->lessThan($column, $value, $integer));
        
        return $this;
    }
    
    /**
     * WHERE column >= value
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return \Web\Database\Sql\Clause\Where
     */
    public function lessOrEqual($column, $value, $integer = true)
    {
        $this->_add($this->_helper->lessThanOrEqualTo($column, $value, $integer));
        
        return $this;
    }
    
    /**
     * WHERE column IN(values)
     * 
     * @param string $column
     * @param array $values
     * @param boolean $integer
     */
    public function in($column, $values, $integer = true)
    {
        $this->_add($this->_helper->in($column, $values, $integer));
        
        return $this;
    }
    
    /**
     * WHERE column NOT IN(values)
     * 
     * @param string $column
     * @param array $values
     * @param boolean $integer
     */
    public function notIn($column, $values, $integer = true)
    {
        $this->_add($this->_helper->in($column, $values, $integer));
    }
    
    
    /**
     * add where item
     * 
     * @param string $where
     */
    private function _add($where)
    {
        $this->_where[] = $where;
    }
    
}
