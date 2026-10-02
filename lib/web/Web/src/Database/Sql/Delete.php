<?php
/**
 * Database Sql Update
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;

use Web\Database\SqlHelper;
use Web\Database\Sql\Clause\Where;
use Web\Database\Sql\Keyword;
use Web\Database\Sql\Result;


class Delete
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
    
    public function __construct($adapter = null)
    {
        $this->where = new Where();
        $this->_helper = new SqlHelper($adapter);
    }
    
    /**
     * set table
     * 
     * @param string $table
     * @return \Web\Database\Sql\Delete
     */
    public function from($table)
    {
        $this->_table = $table;
        
        return $this;
    }
    
    /**
     * set order
     * 
     * @param string $order
     * @return \Web\Database\Sql\Delete
     */
    public function order($order)
    {
        $this->_order = $order;
        
        return $this;
    }

    /**
     * set limit
     * 
     * @param string $limit
     * @return \Web\Database\Sql\Delete
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        
        return $this;
    }
    
    /**
     * execute DELETE and get result
     * 
     * @return int
     */
    public function getResult()
    {
        $sql = $this->getSql();
        $result = new Result($this->_helper->getAdapter());
        
        return $result->execute($sql);
    }
    
    /**
     * get delete sql statement
     * 
     * @return string
     */
    public function getSql()
    {
        $sql = Keyword::DELETE . $this->_helper->quoteIdentifier($this->_table);
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
        
        return $sql;
    }
    
}
