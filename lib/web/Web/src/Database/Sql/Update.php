<?php
/**
 * Database Sql Update
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;

use Web\Database\Sql;
use Web\Database\SqlHelper;
use Web\Database\Sql\Clause\Where;
use Web\Database\Sql\Keyword;
use Web\Database\Sql\Result;


class Update
{
    /**
     *
     * @var \Web\Database\Sql\Clause\Where 
     */
    public $where;
    
    /**
     *
     * @var string 
     */
    private $_table;
    
    /**
     *
     * @var string 
     */
    private $_order;
    
    /**
     *
     * @var string 
     */
    private $_limit;
    
    /**
     *
     * @var \Web\Database\SqlHelper
     */
    private $_helper;
    
    /**
     *
     * @var array 
     */
    private $_values;
    
    public function __construct($adapter = null)
    {
        $this->where = new Where($adapter);
        $this->_helper = new SqlHelper($adapter);
    }
    
    /**
     * set table for update
     * 
     * @param string $table
     * @return \Web\Database\Sql\Update
     */
    public function table($table)
    {
        $this->_table = $table;
        
        return $this;
    }
    
    /**
     * set update order
     * 
     * @param string $order
     * @return \Web\Database\Sql\Update
     */
    public function order($order)
    {
        $this->_order = $order;
        
        return $this;
    }
    
    /**
     * set update values
     * 
     * @param array $values array(column => value)
     * @return \Web\Database\Sql\Update
     */
    public function set($values)
    {
        $this->_values = $values;
        
        return $this;
    }
    
    /**
     * set update limit
     * 
     * @param string|int $limit
     * @return \Web\Database\Sql\Update
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        
        return $this;
    }
    
    /**
     * update and get result
     * 
     * @return int
     */
    public function getResult()
    {
        $sql = Keyword::UPDATE . $this->_helper->quoteIdentifier($this->_table)
              . Keyword::SET;
        $set = [];
        $values = [];
        foreach ($this->_values as $field => $value) {
            $set[] = $this->_helper->quoteIdentifier($field) . ' = ?';
            $values[] = $value;
        }
        $sql .= $this->_helper->combine($set);
        $where = $this->where->get();
        if (! empty($where)) {
            $sql .= Keyword::WHERE . $where;
        }
        if (! empty($this->_order)) {
            $sql .= Keyword::ORDER . $this->_order;
        }
        if (! empty($this->_limit)) {
            $sql .= Keyword::LIMIT . $this->_limit;
        }
        $result = new Result($this->_helper->getAdapter());
        $update = $result->prepare($sql);
        $update->execute($values);
                
        return $update->rowCount();
    }
    
    /**
     * get update sql statement
     * 
     * @return string
     */
    public function getSql()
    {
        $sqlBuilder = new Sql($this->_helper->getAdapter());
        $sqlBuilder->update($this->_values)
                   ->table($this->_table);
        $where = $this->where->get();
        if (! empty($where)) {
            $sqlBuilder->where($where);
        }
        if (! empty($this->_order)) {
            $sqlBuilder->order($this->_order);
        }
        if (! empty($this->_limit)) {
            $sqlBuilder->limit($this->_limit);
        }
        
        return $sqlBuilder->getSql();
    }
    
}
