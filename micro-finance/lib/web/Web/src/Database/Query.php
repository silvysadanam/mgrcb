<?php
/**
 * Database Query
 *
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;


class Query
{
    /**
     *
     * @var \PDO 
     */
    private $_adapter;
    
    /**
     * constructor
     */
    public function __construct($adapter = null)
    {
        $this->_adapter = Adapter::load($adapter);
    }
    
    /**
     * get adapter instance
     * 
     * @return \Web\Database\Adapter
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }
    
    /**
     * PDO query
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function query($statement)
    {
        return $this->_adapter->query($statement);
    }
    
    /**
     * PDO exec
     * 
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        return $this->_adapter->exec($statement);
    }
    
    /**
     * begin transaction
     * 
     * @return boolean
     */
    public function beginTransaction()
    {
        return $this->_adapter->beginTransaction();
    }
    
    /**
     * commit
     * 
     * @return boolean
     */
    public function commit()
    {
        return $this->_adapter->commit();
    }
    
    /**
     * rollBack
     * 
     * @return boolean
     */
    public function rollBack()
    {
        return $this->_adapter->rollBack();
    }
    
    /**
     * checks if inside a transaction
     * 
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->_adapter->inTransaction();
    }
    
    /**
     * lastInsertId
     * 
     * @return int
     */
    public function lastInsertId()
    {
        return $this->_adapter->lastInsertId();
    }
    
    /**
     * quote a string
     * 
     * @param string $input
     * @return string
     */
    public function quote($input)
    {
        return $this->_adapter->quote($input);
    }
    
    /**
     * prepare statement for execution
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function prepare($statement)
    {
        return $this->_adapter->prepare($statement);
    }
    
}
