<?php
/**
 * Database TableGateway
 *
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use Web\Database\Sql;
use Web\Database\SqlHelper;
use Web\Database\Sql\Select;
use Web\Database\Sql\Clause\Where;
use Web\Database\Sql\Insert;
use Web\Database\Sql\Update;
use Web\Database\Sql\Delete;
use Web\Database\Model;
use Web\Exception;


class TableGateway
{
    /**
     *
     * @var string 
     */
    private $_table;
    
    /**
     *
     * @var string 
     */
    private $_primary = 'id';
    /**
     *
     * @var \Web\Database\SqlHelper 
     */
    private $_helper;
    
    /**
     *
     * @var \PDO 
     */
    private $_adapter;
    
    /**
     * constructor
     * 
     * @param string $table
     * @param string $primary
     * @param mixed $adapter
     * @throws Exception
     */
    public function __construct($table, $primary = null, $adapter = null)
    { 
        if (null === $table) {
            throw new Exception('Table name must be specified');
        }
        $this->_table = $table;
        if (null !== $primary) {
            $this->_primary = $primary;
        }
        $this->_helper = new SqlHelper($adapter);
        $this->_adapter = $adapter;
    }
    
    /**
     * set adapter
     * 
     * @param mixed $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
        $this->_helper = new SqlHelper($adapter);
    }

    /**
     * get quoted table name
     * 
     * @return string
     */
    public function getTableQuoted()
    {
        return $this->getHelper()->quoteIdentifier($this->_table);
    }
    
    /**
     * get quoted table name
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }
    
    /**
     * get adapter
     * 
     * @return \PDO
     */
    public function getAdapter()
    {
        return $this->_helper->getAdapter();
    }
    
    /**
     * get Sql builder
     * 
     * @return \Web\Database\Sql
     */
    public function getSql()
    {
        return new Sql($this->_adapter);
    }
    
    /**
     * get SQL helper
     * 
     * @return \Web\Database\SqlHelper
     */
    public function getHelper()
    {
        return $this->_helper;
    }
    
    /**
     * select records
     * 
     * @param string|array|Where $where
     * @param string $order
     * @param string|int $limit
     * @return array
     */
    public function select($where = null, $order = null, $limit = null)
    {
        $select = $this->_getSelect($where, $order, $limit);
        
        return $select->all();
    }
    
    /**
     * select records with Select object
     * 
     * @param \Web\Database\Sql\Select $select
     * @return array
     */
    public function selectWith(Select $select)
    {
        $select->from($this->_table);
        
        return $select->all();
    }
    
    /**
     * insert record
     * 
     * @param array $set
     * @return int
     */
    public function insert($set)
    {
        $this->_validateSet($set);
        $insert = new Insert($this->_adapter);
        $insert->into($this->_table)
               ->values($set);
        
        return $insert->getResult();
    }
    
    /**
     * insert multiple records
     * 
     * @param array $set
     * @return int
     */
    public function insertMulti($set)
    {
        $this->_validateSet($set);
        $insert = new Insert($this->_adapter);
        $insert->into($this->_table)
               ->multi($set);
        
        return $insert->getResult();
    }
    
    /**
     * update records
     * 
     * @param array $set
     * @param string|array|Where $where
     * @param string|int $limit
     * @return int
     */
    public function update($set, $where = null, $limit = null)
    {
        $this->_validateSet($set);
        $update = new Update($this->_adapter);
        $update->table($this->_table)
               ->set($set);        
        if (null !== $where) {
            if ($where instanceof Where) {
                $update->where = $where;
            } else {
                $update->where->add($where);
            }
        }
        if (null !== $limit) {
            $update->limit($limit);
        }
        
        return $update->getResult();
    }
    
    /**
     * delete records
     * 
     * @param string|array|Where $where
     * @param string|int $limit
     * @return int
     */
    public function delete($where = null, $limit = null)
    {
        $delete = new Delete($this->_adapter);
        $delete->from($this->_table);
        if (null !== $where) {
            if ($where instanceof Where) {
                $delete->where = $where;
            } else {
                $delete->where->add($where);
            }
        }
        if (null !== $limit) {
            $delete->limit($limit);
        }
        
        return $delete->getResult();
    }
    
    /**
     * update record by id
     * 
     * @param array $set
     * @param int $id
     * @return int
     */
    public function updateById($set, $id)
    {
        return $this->update($set, $this->_helper->bindId($id, $this->_primary), 1);
    }
    
    /**
     * update records by ids
     * 
     * @param array $set
     * @param array $ids
     * @return int
     */
    public function updateByIds($set, $ids)
    {
        return $this->update($set, $this->_helper->in($this->_primary, $ids, true), count($ids));
    }
    
    /**
     * delete by id
     * 
     * @param int $id
     * @return int
     */
    public function deleteById($id)
    {
        return $this->delete($this->_helper->bindId($id, $this->_primary), 1);
    }
    
    /**
     * delete by ids
     * 
     * @param array $ids
     * @return int
     */
    public function deleteByIds($ids)
    {
        return $this->delete($this->_helper->in($this->_primary, $ids), count($ids));
    }
    
    /**
     * delete single record
     * 
     * @param string|array|Where $where
     * @return int
     */
    public function deleteOne($where = null)
    {
        return $this->delete($where, 1);
    }
    
    /**
     * select record by id
     * 
     * @param int $id
     * @return \stdClass
     */
    public function selectById($id)
    {
        $select = $this->_getSelect($this->_helper->bindId($id, $this->_primary));
        
        return $select->row();
    }
    
    /**
     * select records by ids
     * 
     * @param array $ids
     * @param string $order
     * @return array
     */
    public function selectByIds($ids, $order = null)
    {
        if (null === $order) {
            $order = $this->_primary;
        }
        
        return $this->select($this->_helper->in($this->_primary, $ids), $order, count($ids));
    }
    
    /**
     * select record by column
     * 
     * @param string $column
     * @param string $value
     * @return \stdClass
     */
    public function selectByColumn($column, $value)
    {
        $select = $this->_getSelect($this->_helper->equalTo($column, $value));
        
        return $select->row();
    }
    
     /**
     * select record by id column
     * 
     * @param string $column
     * @param int $id
     * @return \stdClass
     */
    public function selectByIdColumn($column, $id)
    {
        $select = $this->_getSelect($this->_helper->bindId($id, $column));
        
        return $select->row();
    }
    
    /**
     * select specific fields by id
     * 
     * @param array $fields
     * @param int $id
     * @return \stdClass
     */
    public function selectFieldsById($fields, $id)
    {
        $select = $this->_getSelect($this->_helper->bindId($id, $this->_primary), null, null, $fields);
        
        return $select->row();
    }
    
    /**
     * check record by column
     * 
     * @param string $column
     * @param string $value
     * @return boolean
     */
    public function checkByColumn($column, $value)
    {
        $select = $this->_getSelect($this->_helper->equalTo($column, $value));
        
        return $select->rowCount() > 0;
    }
    
    /**
     * check record by id (int) column other than primary key
     * 
     * @param string $column
     * @param int $id
     * @return boolean
     */
    public function checkByColumnId($column, $id)
    {
        $select = $this->_getSelect($this->_helper->equalTo($column, $id, true));
        
        return $select->rowCount() > 0;
    }
    
    /**
     * check record by column except for id 
     * check exists column in records than selected
     * 
     * @param string $column
     * @param string $value
     * @param int $id
     * @return boolean
     */
    public function checkByColumnExceptId($column, $value, $id = 0)
    {
        $where = $this->_helper->equalTo($column, $value)
               . ' AND ' . $this->_helper->notEqualTo($this->_primary, $id, true);
        $select = $this->_getSelect($where);
        
        return $select->rowCount() > 0;
    }
    
    /**
     * select total
     * 
     * @param string|array|Where $where
     * @return int
     */
    public function selectTotal($where = null)
    {
        $select = $this->_getSelect($where);
        
        return $select->rowCount();
    }
    
    /**
     * select column
     * 
     * @param string $column
     * @param string|array|Where $where
     * @param string $order
     * @return string
     */
    public function selectColumn($column = null, $where = null, $order = null)
    {
        if (null === $column) {
            $column = $this->_primary;
        }
        $select = $this->_getSelect($where, $order);
        $select->fields($column);
        
        return $select->column();
    }
    
    /**
     * select column by id
     * 
     * @param string $column
     * @param int $id
     * @return string
     */
    public function selectColumnById($column, $id)
    {
        return $this->selectColumn($column, $this->_helper->bindId($id, $this->_primary));
    }
    
    /**
     * select id by column value
     * 
     * @param string $column
     * @param string $value
     * @return int
     */
    public function selectIdByColumn($column, $value)
    {
        return (int) $this->selectColumn($this->_primary, $this->_helper->bindValue($value, $column));
    }
    
    /**
     * select record
     * 
     * @param string|array|Where $where
     * @param string $order
     * @return \stdClass
     */
    public function selectRow($where = null, $order = null)
    {
        $select = $this->_getSelect($where, $order);
        
        return $select->row();
    }
    
    /**
     * select column from all records
     * 
     * @param string $column
     * @param string|array|Where $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectColumns($column = null, $where = null, $order = null, $limit = null)
    {
        if (null === $column) {
            $column = $this->_primary;
        }
        $select = $this->_getSelect($where, $order, $limit);
        $select->fields($column);
        
        return $select->columns();
    }
    
    /**
     * select id column from all records
     * 
     * @param string|array|Where $where $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectIds($where = null, $order = null, $limit = null)
    {
        $select = $this->_getSelect($where, $order, $limit);
        $select->fields($this->_primary);
        
        return $select->columns();
    }
    
    /**
     * select pairs
     * 
     * @param array $columns
     * @param string|array|Where $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectPairs($columns, $where = null, $order = null, $limit = null)
    {
        $select = $this->_getSelect($where, $order, $limit);
        $select->fields($columns);
        
        return $select->pairs();
    }
    
    /**
     * has id on table
     * 
     * @param int $id
     * @return boolean
     */
    public function hasId($id)
    {
        return $this->selectTotal($this->_helper->equalTo($this->_primary, $id, true)) > 0;
    }
    
    /**
     * select MAX(`column`)
     * 
     * @param string $column
     * @param string|array|Where $where
     * @return string|int
     */
    public function selectMax($column, $where = null)
    {
        $select = $this->_getSelect($where);
        
        return $select->columnMax($column);
    }
    
    /**
     * select SUM(`column`)
     * 
     * @param string $column
     * @param string|array|Where $where
     * @return int|float
     */
    public function selectSum($column, $where = null)
    {
        $select = $this->_getSelect($where);
        
        return $select->columnSum($column);
    }
    
    /**
     * select last row
     * 
     * @param string|array|Where $where
     * @param string $order
     * @return \stdClass
     */
    public function selectLastRow($where = null, $order = null)
    {
        $select = $this->_getSelect($where, $order);
        
        return $select->rowLast();
    }
    
    /**
     * select first row or rows
     * 
     * @param int $limit
     * @return \stdClass|array
     */
    public function selectFirst($limit = 1)
    {
        if (1 == $limit) {
            return $this->selectRow(null, 'id');
        }
        
        return $this->select(null, 'id', $limit);
    }
    
    /**
     * select top record or records
     * 
     * @param int $limit
     * @return array
     */
    public function selectTop($limit = 1)
    {
        if (1 == $limit) {
            return $this->selectRow(null, 'id DESC');
        }
        
        return $this->select(null, 'id DESC', $limit);
    }
    
    /**
     * get Another table gateway
     * 
     * @param string $table
     * @return \Web\Database\TableGateway
     */
    public function getTableGateway($table)
    {
        return new TableGateway($table, null, $this->_adapter);
    }
    
    /**
     * select specified fields from all records
     * 
     * @param array $columns
     * @param string|array|Where $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectFields($columns, $where = null, $order = null, $limit = null)
    {
        $select = $this->_getSelect($where, $order, $limit);
        $select->fields($columns);
        
        return $select->all();
    }
    
    /**
     * select total by column value
     * 
     * @param string $column
     * @param string|int $value
     * @param boolean $int whether integer/id column
     * @return int
     */
    public function selectTotalByColumn($column, $value, $int = true)
    {
        if ($int) {
            $where = $this->_helper->bindId($value, $column);
        } else {
            $where = $this->_helper->bindValue($value, $column);
        }
        $select = $this->_getSelect($where);
        
        return $select->rowCount();
    }
    
    /**
     * select total by column value
     * 
     * @param string $column
     * @param string|int $id
     * @return int
     */
    public function selectTotalByIdColumn($column, $id)
    {
        return $this->selectTotalByColumn($column, $id, true);
    }
    
    /**
     * select all records by id column value
     * 
     * @param string $column
     * @param int $id
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectAllByIdColumn($column, $id, $order = null, $limit = null)
    {
        $where = $this->_helper->bindId($id, $column);
        $select = $this->_getSelect($where, $order, $limit);
        
        return $select->all();
    }
    
    /**
     * select all by column value
     * 
     * @param string $column
     * @param string $value
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectAllByColumn($column, $value, $order = null, $limit = null)
    {
        $where = $this->_helper->bindValue($value, $column);
        $select = $this->_getSelect($where, $order, $limit);
        
        return $select->all();
    }
    
    /**
     * select all records by id column value
     * 
     * @param string $column
     * @param array $ids
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function selectAllByIdColumnValues($column, $ids, $order = null, $limit = null)
    {
        $where = $this->_helper->in($column, $ids);
        $select = $this->_getSelect($where, $order, $limit);
        
        return $select->all();
    }
    
    /**
     * delete by column
     * 
     * @param string $column
     * @param string $value
     * @return int
     */
    public function deleteByColumn($column, $value)
    {
        return $this->delete($this->_helper->bindValue($value, $column));
    }
    
    /**
     * delete by id column
     * 
     * @param string $column
     * @param int $id
     * @return int
     */
    public function deleteByIdColumn($column, $id)
    {
        return $this->delete($this->_helper->bindId($id, $column));
    }
    
    /**
     * get last id
     * 
     * @return int
     */
    public function lastId()
    {
        return (int) $this->selectMax($this->_primary);
    }
    
    /**
     * get model instance
     * 
     * @return Model
     */
    public function getModel()
    {
        return new Model($this->getAdapter());
    }
    
    /**
     * query result for statement
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function query($statement)
    {
        return $this->getAdapter()->query($statement);
    }
    
    /**
     * prepare statement
     * 
     * @param string $statement
     * @return \PDOStatement
     */
    public function prepare($statement)
    {
        return $this->getAdapter()->prepare($statement);
    }
    
    /**
     * execute statement
     * 
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        return $this->getAdapter()->exec($statement);
    }
    
    /**
     * get total from statement
     * 
     * @param string $statement
     * @return int
     */
    public function queryTotal($statement)
    {
        return $this->getModel()->total($statement);
    }
    
    /**
     * get column from statement
     * 
     * @param string $statement
     * @return string
     */
    public function queryColumn($statement)
    {
        return $this->getModel()->column($statement);
    }
    
    /**
     * get columns from statement
     * 
     * @param string $statement
     * @return array
     */
    public function queryColumns($statement)
    {
        return $this->getModel()->columns($statement);
    }
    
    /**
     * get pairs from statement
     * 
     * @param string $statement
     * @return array
     */
    public function queryPairs($statement)
    {
        return $this->getModel()->pairs($statement);
    }
    
    /**
     * get row from statement
     * 
     * @param string $statement
     * @return \stdClass
     */
    public function queryRow($statement)
    {
        return $this->getModel()->row($statement);
    }
    
    /**
     * get rows from statement
     * 
     * @param string $statement
     * @return array
     */
    public function queryRows($statement)
    {
        return $this->getModel()->rows($statement);
    }
    
    /**
     * get prepared statement for inserting multiple rows
     * 
     * @param array $columns columns present in insert set, in order
     * 
     * @return \PDOStatement
     */
    public function getInsertPreparedStatement($columns)
    {
        $placeholders = array_fill(0, count($columns), '?');
        $statement = 'INSERT INTO `' . $this->_table . '` (`' 
                   . implode('`, `', $columns) . '`) VALUES ('
                   . implode(', ', $placeholders) . ')';
        
        return $this->prepare($statement);
    }
    
    /**
     * get prepared statement with column name parameters
     * 
     * @param array $columns columns present in insert set
     * 
     * @return \PDOStatement
     */
    public function getParamInsertStatement($columns)
    {
        $placeholders = [];
        foreach ($columns as $column) {
            $placeholders[] = ':' . $column;
        }
        
        $statement = 'INSERT INTO `' . $this->_table . '` (`' 
                   . implode('`, `', $columns) . '`) VALUES ('
                   . implode(', ', $placeholders) . ')';
        
        return $this->prepare($statement);
    }
    
    /**
     * get prepared statement for counting total
     * 
     * @param array $whereColumns columns in where condition
     * 
     * @return \PDOStatement
     */
    public function getCountPreparedStatement($whereColumns = ['id'])
    {
        $statement = 'SELECT COUNT(`' . $this->_primary . '`) FROM `' . $this->_table . '`';
        $conditions = [];
        if (is_array($whereColumns)) {
            foreach ($whereColumns as $field) {
                $conditions[] = '`' . $field . '` = ?';
            }
        } else {
            $conditions[] = '`' . $whereColumns . '` = ?';
        }
        
        if (! empty($conditions)) {
            $statement .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        return $this->prepare($statement);
    }
    
    /**
     * get prepared statement for delete record(s)
     * 
     * @param array $whereColumns columns in where condition
     * 
     * @return \PDOStatement
     */
    public function getDeletePreparedStatement($whereColumns = ['id'])
    {
        $statement = 'DELETE FROM `' . $this->_table . '`';
        $conditions = [];
        $single = true;
        if (is_array($whereColumns)) {
            foreach ($whereColumns as $field) {
                $conditions[] = '`' . $field . '` = ?';
            }
            if (count($whereColumns) > 1) {
                $single = false;
            } elseif (! in_array('id', $whereColumns)) {
                $single = false;
            }
        } else {
            $conditions[] = '`' . $whereColumns . '` = ?';
            if ('id' != $whereColumns) {
                $single = false;
            }
        }
        
        $statement .= ' WHERE ' . implode(' AND ', $conditions);
        if ($single) {
            $statement .= ' LIMIT 1';
        }
        
        return $this->prepare($statement);
    }
    
    /**
     * get prepared statement for updating row(s) by parameter names
     * 
     * @param array $columns columns present in update set, in order
     * @param array $whereColumns columns in where condition
     * @param boolean $limit whether update single record only
     * 
     * @return \PDOStatement
     */
    public function getParamUpdatePreparedStatement($columns, $whereColumns = ['id'], $limit = true)
    {
        $statement = 'UPDATE `' . $this->_table . '` SET ';
        $placeholders = [];
        foreach ($columns as $column) {
            $placeholders[] = '`' . $column . '` = :' . $column;
        }
        $statement .= implode(', ', $placeholders) . ' WHERE ';
        $conditions = [];
        if (is_array($whereColumns)) {
            foreach ($whereColumns as $field) {
                $conditions[] = '`' . $field . '` = :' . $field;
            }
        } else {
            $conditions[] = '`' . $whereColumns . '` = :' . $whereColumns;
        }
        $statement .= implode(' AND ', $conditions);
        if ($limit) {
            $statement .= ' LIMIT 1';
        }
        
        return $this->prepare($statement);
    }
    
    /**
     * select distinct column values
     * 
     * @param string $column
     * @param string|array|Where $where
     * @param string $order
     * @return array
     */
    public function selectDistinct($column, $where = null, $order = null)
    {
        $select = $this->_getSelect($where, $order);
        $select->fields($column);
        
        return $select->distinct();
    }
    
    /**
     * get prepared statement for updating row by id
     * 
     * @param array $columns columns present in update set, in order
     * @param array $whereColumns columns in where condition
     * @param boolean $limit whether update single record only
     * 
     * @return \PDOStatement
     */
    public function getUpdatePreparedStatement($columns, $whereColumns = ['id'], $limit = true)
    {
        $statement = 'UPDATE `' . $this->_table . '` SET ';
        $values = [];
        foreach ($columns as $column) {
            $values[] = '`' . $column . '` = ?';
        }
        $statement .= implode(', ', $values) . ' WHERE ';
        $conditions = [];
        if (is_array($whereColumns)) {
            foreach ($whereColumns as $field) {
                $conditions[] = '`' . $field . '` = ?';
            }
        } else {
            $conditions[] = '`' . $whereColumns . '` = ?';
        }
        $statement .= implode(' AND ', $conditions);
        if ($limit) {
            $statement .= ' LIMIT 1';
        }
        
        return $this->prepare($statement);
    }
    
    /**
     * find record by id alias for selectById()
     * 
     * @param int $id
     * @return \stdClass
     */
    public function find($id)
    {
        $select = $this->_getSelect($this->_helper->bindId($id, $this->_primary));
        
        return $select->row();
    }
    
    /**
     * select group count
     * 
     * @param string $column
     * @param string|array|Where $where
     * @param string $order
     * @param string|int $limit
     * @return array
     */
    public function selectGroupCount($column, $where = null, $order = null, $limit = null)
    {
        $select = $this->_getSelect($where, $order, $limit, [$column, $this->_primary]);
        $select->group->add($column);
        
        return $select->groupCount();
    }
    
    /**
     * select records
     * 
     * @param string|array|Where $where
     * @param string $order
     * @param string|int $limit
     * @param string $column
     * @return array
     */
    public function selectIndexed($where = null, $order = null, $limit = null, $column = 'id')
    {
        $select = $this->_getSelect($where, $order, $limit);
        $sql = $select->getSql();
        $list = [];
        $result = $this->query($sql);
        if (false !== $result) {
            while ($row = $result->fetch()) {
                $list[$row->{$column}] = $row;
            }
        }
        
        return $list;
    }
    
    /**
     * select pairs by ids
     * 
     * @param array $columns
     * @param array $ids
     * @param string $order
     * @return array
     */
    public function selectPairsByIds($columns, $ids, $order = null)
    {
        $where = $this->_helper->in('id', $ids);
        $select = $this->_getSelect($where, $order, count($ids));
        $select->fields($columns);
        
        return $select->pairs();
    }
    
    /**
     * extract id column from list
     * 
     * @param array $list
     * @return array
     */
    public function extractIds($list)
    {
        $ids = [];
        if (! empty($list)) {
            foreach ($list as $row) {
                $ids[] = $row->id;
            }
        }
        
        return $ids;
    }
    
    /**
     * extract column from list
     * 
     * @param array $list
     * @param string $column
     * @return array
     */
    public function extractColumns($list, $column)
    {
        $extracted = [];
        if (! empty($list)) {
            foreach ($list as $row) {
                $extracted[] = $row->{$column};
            }
        }
        
        return $extracted;
    }
    
    /**
     * get indexed list by column
     * 
     * @param array $list
     * @param string $column
     * @return array
     */
    public function getIndexedList($list, $column = 'id')
    {
        $indexed = [];
        if (! empty($list)) {
            foreach ($list as $row) {
                $indexed[$row->{$column}] = $row;
            }
        }
        
        return $indexed;
    }
    
    /**
     * set field values as NULL by id
     * 
     * @param array|string $fields
     * @param int $id
     * @return boolean
     */
    public function setNullById($fields, $id)
    {
        $query = 'UPDATE ' . $this->getTableQuoted() . ' SET ';
        $values = [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $values[] = $this->getHelper()->quoteIdentifier($field) . ' =NULL';
            }
        } else {
            $values[] = $this->getHelper()->quoteIdentifier($fields) . ' =NULL';
        }
        
        $query .= implode(', ', $values);
        $query .= ' WHERE ' . $this->getHelper()->quoteIdentifier($this->_primary) . ' =? LIMIT 1';
        $update = $this->prepare($query);
        return $update->execute([$id]);
    }
    
    /**
     * set field values as NULL by condition
     * 
     * @param array|string $fields
     * @param string|array|Where $where
     * @param string $limit
     * @return boolean
     */
    public function setNull($fields, $where, $limit = null)
    {
        $query = 'UPDATE ' . $this->getTableQuoted() . ' SET ';
        $values = [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $values[] = $this->getHelper()->quoteIdentifier($field) . ' =NULL';
            }
        } else {
            $values[] = $this->getHelper()->quoteIdentifier($fields) . ' =NULL';
        }
        
        $query .= implode(', ', $values);
        $query .= ' WHERE ';
        if ($where instanceof Where) {
            $query .= $where->get();
        } elseif (! empty($where)) {
            $clause = new Where($this->_adapter);
            $clause->add($where);
            $query .= $clause->get();
        }
        if (null !== $limit) {
            $query .= ' LIMIT ' . $limit;
        }
        
        return $this->exec($query);
    }
    
    /**
     * select id by condition
     * 
     * @param string|array|Where $where
     * @param string $order
     * @return int
     */
    public function selectId($where = null, $order = null)
    {
        return (int) $this->selectColumn($this->_primary, $where, $order);
    }
    
    /**
     * select MIN(`column`)
     * 
     * @param string $column
     * @param string|array|Where $where
     * @return string|int
     */
    public function selectMin($column, $where = null)
    {
        $select = $this->_getSelect($where);
        
        return $select->columnMin($column);
    }
    /**
     * get select params
     * 
     * @param string|array|Where $where
     * @param string $order
     * @param string|int $limit
     * @return Select
     */
    private function _getSelect($where = null, $order = null, $limit = null, $fields = null)
    {
        $select = new Select($this->_adapter, $this->_primary);
        $select->from($this->_table);
        if (null !== $fields) {
            $select->fields($fields);
        }
        if (null !== $where) {
            if ($where instanceof Where) {
                $select->where = $where;
            } elseif (! empty($where)) {
                $select->where->add($where);
            }
        }
        if (null !== $order) {
            $select->order($order);
        }
        if (null !== $limit) {
            $select->limit($limit);
        }
        
        return $select;
    }
    
    /**
     * validate data set
     * 
     * @param mixed $set
     * @throws Exception
     */
    private function _validateSet($set)
    {
        if (empty($set)) {
            throw new Exception('Empty Row set');
        } elseif (! is_array($set)) {
            throw new Exception('Invalid Row set');
        }
    }
    
    public function checkByColumnByFmc($column, $value, $fmc_id=0)
    {
        $where = $this->_helper->equalTo($column, $value)
               . ' AND ' . $this->_helper->equalTo('fmc_id', $fmc_id);
        $select = $this->_getSelect($where);        
        return $select->row();
    }
    
    }
