<?php
/**
 * Database Sql Result
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;

use Web\Database\Adapter;
use PDO;


class Result
{
    /**
     *
     * @var \Web\Database\Adapter
     */
    protected $_adapter;
    
    public function __construct($adapter = null)
    { 
        $this->_adapter = Adapter::load($adapter);
    }
    
    /**
     * fetch all
     * 
     * @param string $sql
     * @return array
     */
    public function fetchAll($sql)
    {
        $result = $this->_adapter->query($sql);
        
        if (false !== $result) {
            return $result->fetchAll();
        }
        
        return [];
    }
    
    /**
     * fetch column
     * 
     * @param string $sql
     * @return string
     */
    public function fetchColumn($sql)
    {
        $column = null;
        $result = $this->_adapter->query($sql);
        if (false !== $result) {
            $column = $result->fetch(PDO::FETCH_COLUMN);
            $result->closeCursor();
        }
        
        return $column;
    }
    
    /**
     * fetch columns
     * 
     * @param string $sql
     * @return array
     */
    public function fetchColumns($sql)
    {
        return $this->fetchAllWithOption($sql, PDO::FETCH_COLUMN);
    }
    
    /**
     * fetch row
     * 
     * @param string $sql
     * @return array
     */
    public function fetchRow($sql)
    {
        $row = [];
        $result = $this->_adapter->query($sql);
        if (false !== $result) {
            $row = $result->fetch();
            $result->closeCursor();
        }
        
        return $row;
    }
    
    /**
     * fetch pairs
     * 
     * @param string $sql
     * @return array
     */
    public function fetchPairs($sql)
    {
        return $this->fetchAllWithOption($sql, PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * fetch all with option
     * 
     * @param string $sql
     * @param int $option \PDO_PARAM_*
     * @return array
     */
    public function fetchAllWithOption($sql, $option)
    {
        $result = $this->_adapter->query($sql);
        if (false !== $result) {
            return $result->fetchAll($option);
        }
        
        return [];
    }
    
    /**
     * execute sql
     * 
     * @param string $sql
     * @return int
     */
    public function execute($sql)
    {
        return $this->_adapter->exec($sql);
    }
    
    /**
     * get last insert id
     * 
     * @return int
     */
    public function insertId()
    {
        return $this->_adapter->lastInsertId();
    }
    
    /**
     * get query result
     * 
     * @param string $sql
     * @return \PDOStatement
     */
    public function query($sql)
    {
        return $this->_adapter->query($sql);
    }
    
    /**
     * prepare statement
     * 
     * @param string $sql
     * @return \PDOStatement
     */
    public function prepare($sql)
    {
        return $this->_adapter->prepare($sql);
    }
    
}
