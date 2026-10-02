<?php
/**
 * Database Model
 *
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use Web\Database\Sql;
use Web\Database\TableGateway;
use PDO;


class Model
{
    /**
     *
     * @var \PDO 
     */
    private $_adapter;
    
    /**
     *
     * @var \Web\Database\Sql 
     */
    private $_sql;
    
    public function __construct($adapter = null)
    {
        $this->_sql = new Sql($adapter);
        $this->_adapter = $this->_sql->getAdapter();
    }
    
    /**
     * set adapter
     * 
     * @param mixed $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_sql = new Sql($adapter);
        $this->_adapter = $this->_sql->getAdapter();
    }
    
    /**
     * get Sql instance
     * 
     * @return \Web\Database\Sql
     */
    public function getSql()
    {
        return new Sql($this->_adapter);
    }
    
    /**
     * get helper
     * 
     * @return \Web\Database\SqlHelper
     */
    public function getHelper()
    {
        return $this->_sql->getHelper();
    }
    
    /**
     * get adapter
     * 
     * @return \PDO
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }
    
    /**
     * execute statement
     * 
     * @param string $statement
     * @return int
     */
    public function execute($statement)
    {
        return $this->_adapter->exec($statement);
    }
    
    /**
     * execute PDO::query
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function query($statement)
    {
        return $this->_adapter->query($statement);
    }
    
    /**
     * prepare statement
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function prepare($statement)
    {
        return $this->_adapter->prepare($statement);
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
     * end transaction
     * 
     * @param boolean $commit
     * @return boolean
     */
    public function endTransaction($commit = true)
    {
        if ($commit) {
            return $this->commit();
        }
        
        return $this->rollBack();        
    }
    
    /**
     * rollback transaction
     * 
     * @return boolean
     */
    public function rollBack()
    {
        return $this->_adapter->rollBack();
    }
    
    /**
     * commit transaction
     * 
     * @return boolean
     */
    public function commit()
    {
        return $this->_adapter->commit();
    }
    
    /**
     * select all
     * 
     * @param string $table
     * @param string $where
     * @param string $orderBy
     * @param string $limit
     * @return array
     */
    public function selectAll($table, $where = null, $orderBy = null, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->selectAll()
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select records with specified fields
     * 
     * @param string $table
     * @param array $fields
     * @param string $where
     * @param string $orderBy
     * @param string $limit
     * @return array
     */
    public function selectFields($table, $fields, $where = null, $orderBy = null, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->selectAll($fields)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select row
     * 
     * @param string $table
     * @param array $fields
     * @param string $where
     * @param string $orderBy
     * @return \stdClass
     */
    public function selectRow($table, $fields = [], $where = null, $orderBy = null)
    {
        $this->_sql->clear();
        $this->_sql->selectRow($fields)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select count
     * 
     * @param string $table
     * @param string $primary
     * @param string $where
     * @return int
     */
    public function selectCount($table, $primary = 'id', $where = null)
    {
        $this->_sql->clear();
        $this->_sql->selectCount($primary)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select pairs
     * 
     * @param string $table
     * @param array $fields
     * @param string $where
     * @param string $orderBy
     * @param string $limit
     * @return array
     */
    public function selectPairs($table, $fields, $where = null, $orderBy = null, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->selectPairs($fields)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select first column
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @param string $orderBy
     * @return string
     */
    public function selectColumn($table, $field, $where = null, $orderBy = null)
    {
        $this->_sql->clear();
        $this->_sql->selectColumn($field)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select column by id 
     * 
     * @param string $table
     * @param string $field
     * @param int $id
     * @return string
     */
    public function selectColumnById($table, $field, $id)
    {
        $this->_sql->clear();
        $this->_sql->selectColumn($field)
                   ->from($table)
                   ->whereId('id', $id);
        
        return $this->_sql->execute();
    }
    
    /**
     * select id by column value
     * 
     * @param string $table
     * @param string $column
     * @param string $value
     * @param string $idColumn
     * @param string $where
     * @param string $orderBy
     * @return int
     */
    public function selectIdByColumn($table, $column, $value, $idColumn = 'id', $where = null, $orderBy = null)
    {
        $this->_sql->clear();
        $this->_sql->selectColumn($idColumn)
                   ->from($table)
                   ->whereEqual($column, $value);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        
        return (int) $this->_sql->execute();
    }
    
    /**
     * select columns
     * 
     * @param string $table
     * @param string $field
     * @param string $where
     * @param string $orderBy
     * @param string $limit
     * @return array
     */
    public function selectColumns($table, $field, $where = null, $orderBy = null, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->selectColumns($field)
                   ->from($table);
        if (! empty($where)) {
            $this->_sql->where($where);
        }
        if (! empty($orderBy)) {
            $this->_sql->order($orderBy);
        }
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * select by id
     * 
     * @param string $table
     * @param int $id
     * @param string $primary
     * @return \stdClass
     */
    public function selectById($table, $id, $primary = 'id')
    {
        return $this->selectRow($table, [], $this->bindId($id, $primary));
    }
    
    /**
     * check id
     * 
     * @param string $table
     * @param int $id
     * @param string $primary
     * @return boolean
     */
    public function checkId($table, $id, $primary = 'id')
    {
        return $this->selectCount($table, $primary, $this->bindId($id, $primary)) > 0;
    }
    
    /**
     * check column
     * 
     * @param string $table
     * @param string $value
     * @param string $column
     * @param string $primary
     * @return boolean
     */
    public function checkColumn($table, $value, $column, $primary = 'id')
    {
        return $this->selectCount($table, $primary, $this->bindValue($value, $column)) > 0;
    }
    
    /**
     * check row
     * 
     * @param string $table
     * @param string $where
     * @param string $primary
     * @return boolean
     */
    public function checkRow($table, $where, $primary = 'id')
    {
        return $this->selectCount($table, $primary, $where) > 0;
    }
    
    /**
     * insert record
     * 
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert($table, $data)
    {
        $this->_sql->clear();
        $this->_sql->insert($data)->into($table);
        
        return $this->_sql->execute();
    }
    
    /**
     * update records
     * 
     * @param string $table
     * @param array $data
     * @param string $where
     * @param string $limit
     * @return boolean
     */
    public function update($table, $data, $where, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->update($data)
                   ->table($table)
                   ->where($where);
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * delete records
     * 
     * @param string $table
     * @param string $where
     * @param string $limit
     * @return int
     */
    public function delete($table, $where, $limit = null)
    {
        $this->_sql->clear();
        $this->_sql->delete()
                   ->from($table)
                   ->where($where);
        if (! empty($limit)) {
            $this->_sql->limit($limit);
        }
        
        return $this->_sql->execute();
    }
    
    /**
     * update by id
     * 
     * @param string $table
     * @param array $data
     * @param int $id
     * @param string $primary
     * @return boolean
     */
    public function updateById($table, $data, $id, $primary = 'id')
    {
        return $this->update($table, $data, $this->bindId($id, $primary), 1);
    }
    
    /**
     * delete by id
     * 
     * @param string $table
     * @param int $id
     * @param string $primary
     * @return int
     */
    public function deleteById($table, $id, $primary = 'id')
    {
        return $this->delete($table, $this->bindId($id, $primary), 1);
    }
    
    /**
     * bind id
     * 
     * @param int $id
     * @param string $column
     * @return string
     */
    public function bindId($id, $column = 'id')
    {
        return $this->getHelper()->bindId($id, $column);
    }
    
    /**
     * bind value
     * 
     * @param string $value
     * @param string $column
     * @return string
     */
    public function bindValue($value, $column)
    {
        return $this->getHelper()->bindValue($value, $column);
    }
    
    /**
     * quote user input
     * 
     * @param string $string
     * @return string
     */
    public function quote($string)
    {
        return $this->getHelper()->quote($string);
    }
    
    /**
     * quote identifier
     * 
     * @param string $string
     * @return string
     */
    public function quoteIdentifier($string)
    {
        return $this->getHelper()->quoteIdentifier($string);
    }
    
    /**
     * get table gateway
     * 
     * @param string $table
     * @param string $primary
     * @return \Web\Database\TableGateway
     */
    public function getTableGateway($table, $primary = null)
    {
        return new TableGateway($table, $primary, $this->getAdapter());
    }
    
    /**
     * select column from statement
     * 
     * @param string $statement
     * @return string
     */
    public function column($statement)
    {
        $column = null;
        $result = $this->query($statement);
        if (false !== $result) {
            $column = $result->fetchColumn();
            $result->closeCursor();
        }
        
        return $column;
    }
    
    /**
     * select columns from statement
     * 
     * @param string $statement
     * @return array
     */
    public function columns($statement)
    {
        $result = $this->query($statement);
        if (false !== $result) {
            return $result->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return [];
    }
    
    /**
     * select total from statement
     * 
     * @param string $statement
     * @return int
     */
    public function total($statement)
    {
        return (int) $this->column($statement);
    }
    
    /**
     * check total from statement
     * 
     * @param string $statement
     * @return boolean
     */
    public function checkTotal($statement)
    {
        return $this->total($statement) > 0;
    }
    
    /**
     * select pairs from statement
     * 
     * @param string $statement
     * @return array
     */
    public function pairs($statement)
    {
        $result = $this->query($statement);
        if (false !== $result) {
            return $result->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        
        return [];
    }
    
    /**
     * select row from statement
     * 
     * @param string $statement
     * @return \stdClass
     */
    public function row($statement)
    {
        $row = [];
        $result = $this->query($statement);
        if (false !== $result) {
            $row = $result->fetch();
            $result->closeCursor();
        }
        
        return $row;
    }
    
    /**
     * select rows from statement
     * 
     * @param string $statement
     * @return array
     */
    public function rows($statement)
    {
        $result = $this->query($statement);
        if (false !== $result) {
            return $result->fetchAll();
        }
        
        return [];
    }
    
}
