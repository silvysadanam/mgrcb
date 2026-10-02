<?php
/**
 * Database Sql Insert
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;

use Web\Database\Sql;
use Web\Database\Sql\Result;


class Insert
{
    private $_table;
    private $_values;
    private $_adapter;
    private $_multi = false;
    private $_fields = null;
    
    public function __construct($adapter = null)
    {
        $this->_adapter = $adapter;
    }
    
    /**
     * set table
     * 
     * @param string $table
     * @return \Web\Database\Sql\Insert
     */
    public function into($table)
    {
        $this->_table = $table;
        return $this;
    }
    
    /**
     * set values
     * 
     * @param array $values
     * @return \Web\Database\Sql\Insert
     */
    public function values($values)
    {
        $this->_values = $values;
        return $this;
    }
    
    /**
     * set values
     * 
     * @param array $values
     * @return \Web\Database\Sql\Insert
     */
    public function set($values)
    {
        return $this->values($values);
    }
    
    /**
     * insert common values
     * 
     * @param array $fields
     * @param array $values - numeric index
     * @return \Web\Database\Sql\Insert
     */
    public function common($fields, $values)
    {
        $this->_fields = $fields;
        $this->_values = $values;
        $this->_multi = true;
        return $this;
    }
    
    /**
     * insert multiple values
     * 
     * @param array $values
     * @return \Web\Database\Sql\Insert
     */
    public function multi($values)
    {
        $this->_values = $values;
        $this->_multi = true;
        return $this;
    }
    
    /**
     * execute insert and return result
     * 
     * @return int
     */
    public function getResult()
    {
        $result = new Result($this->_adapter);
        if ($this->_multi) {
            $sql = 'INSERT INTO `' . $this->_table . '` (`';
            if (! empty ($this->_fields)) {
                $placeholders = array_fill(0, count($this->_fields), '?');
                $sql .= implode('`, `', $this->_fields) . '`) VALUES ('
                      . implode(', ', $placeholders) . ')';
            } else {
                $fields = array_keys($this->_values[0]);
                $sql .= implode('`, `', $fields) . '`) VALUES (:'
                      . implode(', :', $fields) . ')';
            }
            $stmt = $result->prepare($sql);
            foreach ($this->_values as $row) {
                $stmt->execute($row);
                $stmt->closeCursor();
            }
            
            return count($this->_values);
        }
        
        $sql = 'INSERT INTO `' . $this->_table . '` (`';
        $placeholders = array_fill(0, count($this->_values), '?');
        $sql .= implode('`, `', array_keys($this->_values)) . '`) VALUES ('
                  . implode(', ', $placeholders) . ')';
        $stmt = $result->prepare($sql);
        $stmt->execute(array_values($this->_values));
        
        return $result->insertId();
    }
    
    /**
     * get insert sql statement
     * 
     * @return string
     */
    public function getSql()
    {
        if ($this->_multi) {
            return null;
        }
        $sqlBuilder = new Sql($this->_adapter);
        $sqlBuilder->insert($this->_values)
                   ->table($this->_table);
        
        return $sqlBuilder->getSql();
    }
    
}
