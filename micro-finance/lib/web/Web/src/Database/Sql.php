<?php
/**
 * Database Sql
 *
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use Web\Database\SqlHelper;
use Web\Database\Sql\Keyword;
use Web\Database\Sql\Operator;
use Web\Exception;
use PDO;
use PDOException;


class Sql
{
    const SQL_NONE = 0;
    const SELECT_ROW = 1;
    const SELECT_ROWS = 2;
    const SELECT_COUNT = 3;
    const SELECT_COLUMN = 4;
    const SELECT_COLUMNS = 5;
    const SELECT_PAIRS = 6;
    
    const INSERT = 7;
    const UPDATE = 8;
    const DELETE = 9;

    const WHERE_AND = 0;
    const WHERE_OR = 1;
    const GLUE_AND = ' AND ';
    const GLUE_OR = ' OR ';
    
    const ONE = 1;
    const ZERO = 0;
    
    /**
     *
     * @var string 
     */
    private $_table = '';
    
    /**
     *
     * @var array 
     */
    private $_fields = [];
    
    /**
     *
     * @var array 
     */
    private $_joins = [];
    
    /**
     *
     * @var array 
     */
    private $_where = [];
    
    /**
     *
     * @var string 
     */
    private $_whereGlue;
    
    /**
     *
     * @var array 
     */
    private $_order = [];
    
    /**
     *
     * @var array 
     */
    private $_group = [];
    
    /**
     *
     * @var string 
     */
    private $_limit = '';
    
    /**
     *
     * @var boolean 
     */
    private $_hasStatement = false;
    
    /**
     *
     * @var int 
     */
    private $_sqlType;
    
    /**
     *
     * @var array 
     */
    private $_selectTypes;
    
    /**
     *
     * @var array 
     */
    private $_values = [];
    
    /**
     *
     * @var array 
     */
    private $_whereGroup = [];
    
    /**
     *
     * @var boolean 
     */
    private $_whereGroupStarted = false;
    
    /**
     *
     * @var int 
     */
    private $_whereGroupGlue = 0;
    
    /**
     *
     * @var array 
     */
    private $_subQueries = [];
    
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
     * @param \Web\Database\Adapter|string|null $adapter
     */
    public function __construct($adapter = null)
    {
        $this->_adapter = $adapter;
        $this->_helper = new SqlHelper($adapter);
        $this->_sqlType = self::SQL_NONE;
        $this->_whereGlue = self::WHERE_AND;
        $this->_selectTypes = [
            self::SELECT_ROW,
            self::SELECT_ROWS,
            self::SELECT_COUNT,
            self::SELECT_COLUMN,
            self::SELECT_COLUMNS,
            self::SELECT_PAIRS,
        ];
    }
    
    /**
     * get SQL statement
     * 
     * @return string
     */
    public function getSql()
    {
        return $this->_buildSql();
    }
    
    /**
     * set adapter
     * 
     * @param \Web\Database\Adapter|string $adapter
     */
    public function setAdapter($adapter)
    {
        $this->_adapter = $adapter;
        $this->_helper = new SqlHelper($adapter);
    }
    
    /**
     * get adapter
     * @return \PDO
     */
    public function getAdapter()
    {
        return $this->_helper->getAdapter();
    }
    
    /**
     * get sql helper
     * 
     * @return \Web\Database\SqlHelper
     */
    public function getHelper()
    {
        return $this->_helper;
    }
    
    /**
     * add where clause
     * 
     * @param string $field or condition
     * @param string $value
     * @param string $operator
     * @return \Web\Database\Sql
     */
    public function where($field, $value = null, $operator = Operator::EQUAL_TO)
    {
        if (null === $value) {
            $this->_addWhere($field);
        } else {
            $this->_addWhere($this->_buildCondition($field, $value, $operator));
        }
        
        return $this;
    }
    
    /**
     * start where group capture
     * 
     * @param int $glue
     * @return \Web\Database\Sql
     */
    public function startWhereGroup($glue = 0)
    {
        if ($this->_whereGroupStarted) {
            $this->_captureWhereGroup();
        }
        $this->_whereGroup = [];
        $this->_whereGroupGlue = $glue;
        $this->_whereGroupStarted = true;
        
        return $this;
    }
    
    /**
     * end where group capture
     * 
     * @return \Web\Database\Sql
     */
    public function endWhereGroup()
    {
        if ($this->_whereGroupStarted) {
            $this->_captureWhereGroup();
        }
        $this->_whereGroup = [];
        $this->_whereGroupGlue = self::WHERE_AND;
        $this->_whereGroupStarted = false;
        
        return $this;
    }
    
    /**
     * add id condition
     * 
     * @param string $field
     * @param int $value
     * @param string $operator
     * @return \Web\Database\Sql
     */
    public function whereId($field, $value, $operator = Operator::EQUAL_TO)
    {
        $this->_addWhere($this->_helper->quoteIdentifier($field) . $operator . $this->_quote($value, true));
        
        return $this;
    }
    
    /**
     * add id not equal condition
     * 
     * @param string $field
     * @param int $value
     * @return \Web\Database\Sql
     */
    public function whereNotId($field, $value)
    {
        return $this->whereId($field, $value, Operator::NOT_EQUAL_TO);
    }
    
    /**
     * add equal to condition
     * 
     * @param string $field
     * @param string $value
     * @return \Web\Database\Sql
     */
    public function whereEqual($field, $value)
    {
        $this->_addWhere($this->_buildCondition($field, $value, Operator::EQUAL_TO));
        
        return $this;
    }
    
    /**
     * add not equal to condition
     * 
     * @param string $field
     * @param string $value
     * @return \Web\Database\Sql
     */
    public function whereNotEqual($field, $value)
    {
        $this->_addWhere($this->_buildCondition($field, $value, Operator::NOT_EQUAL_TO));
        
        return $this;
    }
    
    /**
     * add less than condition
     * 
     * @param string $field
     * @param string $value
     * @param boolean $orEqual add or equal to
     * @return \Web\Database\Sql
     */
    public function whereLess($field, $value, $orEqual = false)
    {
        $operator = Operator::LESS_THAN;
        if ($orEqual) {
            $operator = Operator::LESS_THAN_OR_EQUAL_TO;
        }
        $this->_addWhere($this->_buildCondition($field, $value, $operator));
        
        return $this;
    }
    
    /**
     * add greater than condition
     * 
     * @param string $field
     * @param string $value
     * @param boolean $orEqual add or equal to
     * @return \Web\Database\Sql
     */
    public function whereGreater($field, $value, $orEqual = false)
    {
        $operator = Operator::GREATER_THAN;
        if ($orEqual) {
            $operator = Operator::GREATER_THAN_OR_EQUAL_TO;
        }
        $this->_addWhere($this->_buildCondition($field, $value, $operator));
        
        return $this;
    }
    
    /**
     * add LIKE clause
     * 
     * @param string $field
     * @param string $value
     * @param boolean $notLike
     * @return \Web\Database\Sql
     */
    public function whereLike($field, $value, $notLike = false)
    {
        $operator = Keyword::LIKE;
        if ($notLike) {
            $operator = Keyword::NOT_LIKE;
        }
        $this->_addWhere($this->_buildCondition($field, '%' . $value . '%', $operator));
        
        return $this;
    }
    
    /**
     * add regexp  condition
     * 
     * @param string $field
     * @param string $pattern
     * @param boolean $notRegexp
     * @return \Web\Database\Sql
     */
    public function whereRegexp($field, $pattern, $notRegexp = false)
    {
        $operator = Keyword::REGEXP;
        if ($notRegexp) {
            $operator = Keyword::NOT_REGEXP;
        }
        $this->_addWhere($this->_buildCondition($field, $pattern, $operator));
        
        return $this;
    }
    
    /**
     * add IN clause
     * 
     * @param string $field
     * @param array $data
     * @return \Web\Database\Sql
     */
    public function whereIn($field, $data, $numeric = true)
    {
        if ($numeric) {
            $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::IN . '(' . $this->_implode($data) . ')');
        } else {
            $values = [];
            foreach ($data as $item) {
                $values[] = $this->_quote($item);
            }
            $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::IN . '(' . $this->_implode($values) . ')');
        }
        
        return $this;
    }
    
    /**
     * add NOT IN clause
     * 
     * @param string $field
     * @param array $data
     * @return \Web\Database\Sql
     */
    public function whereNotIn($field, $data, $numeric = true)
    {
        if ($numeric) {
            $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::NOT_IN . '(' . $this->_implode($data) . ')');
        } else {
            $values = [];
            foreach ($data as $item) {
                $values[] = $this->_quote($item);
            }
            $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::NOT_IN . '(' . $this->_implode($values) . ')');
        }
        
        return $this;
    }
    
    /**
     * add NULL condition
     * 
     * @param string $field
     * @return \Web\Database\Sql
     */
    public function whereNull($field)
    {
        $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::SQL_IS_NULL);
        
        return $this;
    }
    
    /**
     * add IS NOT NULL condition
     * 
     * @param string $field
     * @return \Web\Database\Sql
     */
    public function whereNotNull($field)
    {
        $this->_addWhere($this->_helper->quoteIdentifier($field) . Keyword::SQL_IS_NOT_NULL);
        
        return $this;
    }
    
    /**
     * add field = 1 condition
     * 
     * @param string $field
     * @return \Web\Database\Sql
     */
    public function whereOne($field)
    {
        $this->_addWhere($where = $this->_helper->quoteIdentifier($field) . Operator::EQUAL_TO . self::ONE);
        
        return $this;
    }
    
    /**
     * add field = 0 condition
     * 
     * @param string $field
     * @return \Web\Database\Sql
     */
    public function whereZero($field)
    {
        $this->_addWhere($where = $this->_helper->quoteIdentifier($field) . Operator::EQUAL_TO . self::ZERO);
        
        return $this;
    }
    
    /**
     * add condition group
     * 
     * @param string $where
     * @return \Web\Database\Sql
     */
    public function whereGroup($where)
    {
        $this->_addWhere(sprintf('(%s)', $where));
        
        return $this;
    }
    
    /**
     * add between dates condition
     * 
     * @param string $field
     * @param string $start
     * @param string $end
     * @return \Web\Database\Sql
     */
    public function whereBetween($field, $start, $end)
    {
        $where = $this->_helper->quoteIdentifier($field) . Keyword::BETWEEN
               . $this->_quote($start) . Keyword::WHERE_AND . $this->_quote($end);
        $this->_addWhere($where);
        
        return $this;
    }
    
    /**
     * add DATE(column) = date condition
     * 
     * @param string $field
     * @param string $value
     * @param string $operator
     * @return \Web\Database\Sql
     */
    public function whereDate($field, $value, $operator = ' =')
    {
        $where = 'DATE(' . $this->_helper->quoteIdentifier($field) . ')'
               . $operator . $this->_quote($value);
        $this->_addWhere($where);
        
        return $this;
    }
    
    /**
     * add FUNCTION(column) = value condition
     * 
     * @param string $function
     * @param string $field
     * @param string $value
     * @param string $operator
     * @return \Web\Database\Sql
     */
    public function whereFunction($function, $field, $value, $operator = ' =')
    {
        $where = strtoupper($function) . '(' . $this->_helper->quoteIdentifier($field) . ')'
               . $operator . $this->_quote($value);
        $this->_addWhere($where);
        
        return $this;
    }

    /**
     * add LIKE clauses for set of fields
     * 
     * @param string $value
     * @param array $fields
     * @param boolean $notLike
     * @return \Web\Database\Sql
     */
    public function whereLikeGroup($value, $fields, $notLike = false)
    {
        $glue = Keyword::WHERE_OR;
        $operator = Keyword::LIKE;
        if ($notLike) {
            $operator = Keyword::NOT_LIKE;
            $glue = Keyword::WHERE_AND;
        }
        $where = [];
        $like = $operator . $this->_quote($this->_surround($value, Operator::PERCENTAGE));
        foreach ($fields as $field) {
            $where[] = $this->_helper->quoteIdentifier($field) . $like;
        }
        $this->_addWhere(sprintf('(%s)', $this->_implode($where, $glue)));
        
        return $this;
    }
    
    /**
     * add LIKE clauses for set of values for a field
     * 
     * @param string $field
     * @param array $values
     * @return \Web\Database\Sql
     */
    public function whereLikeValues($field, $values)
    {
        $operator = Keyword::LIKE;
        $where = [];
        foreach ($values as $value) {
            $like = $operator . $this->_quote($this->_surround($value, Operator::PERCENTAGE));
            $where[] = $this->_helper->quoteIdentifier($field) . $like;
        }
        $this->_addWhere(sprintf('(%s)', $this->_implode($where, Keyword::WHERE_OR)));
        
        return $this;
    }
    
    /**
     * set WHERE join glue
     * 
     * @param int $glue
     * @return \Web\Database\Sql
     */
    public function whereGlue($glue)
    {
        if (in_array($glue, [self::WHERE_AND, self::WHERE_OR])) {
            $this->_whereGlue = $glue;
        } elseif ('or' == strtolower(trim($glue))) {
            $this->_whereGlue = self::WHERE_OR;
        } else {
            $this->_whereGlue = self::WHERE_AND;
        }
        
        return $this;
    }
    
    /**
     * add ORDER BY clause
     * 
     * @param string $field
     * @param bool $descending
     * @return \Web\Database\Sql
     */
    public function order($field, $descending = false)
    {
        if (1 == preg_match('/[,\s]/', $field)) {
            $order = $field;
        } else {
            $order = $this->_helper->quoteIdentifier($field);
            if ($descending) {
                $order .= Keyword::DESC;
            }
        }
        $this->_order[] = $order;
        
        return $this;
    }
    
    /**
     * set LIMIT clause
     * 
     * @param int $limit
     * @param int $offset
     * @return \Web\Database\Sql
     */
    public function limit($limit, $offset = 0)
    {
        if (false !== strpos($limit, ',')) {
            if ($offset > 0) {
                $this->_limit = intval($offset) . ',' . intval($limit);
            } else {
                $this->_limit = intval($limit);
            }
        } else {
            $this->_limit = $limit;
        }
        
        return $this;
    }
    
    /**
     * set LIMIT to single record
     * 
     * @return \Web\Database\Sql
     */
    public function record()
    {
        $this->_limit = 1;
        
        return $this;
    }

    /**
     * clear options / reset clauses
     * 
     * @return boolean
     */
    public function clear()
    {
        $this->_hasStatement = false;
        $this->_whereGlue = self::WHERE_AND;
        $this->_fields = [];
        $this->_table = '';
        $this->_joins = [];
        $this->_where = [];
        $this->_order = [];
        $this->_group = [];
        $this->_limit = '';
        $this->_sqlType = self::SQL_NONE;
        $this->_values = [];
        $this->_subQueries = [];
        
        return true;
    }
    
    /**
     * get WHERE clause
     * 
     * @return string
     */
    public function getWhere()
    {
        $where = '';
        if (! empty($this->_where)) {
            if (self::WHERE_AND == $this->_whereGlue) {
                $glue = Keyword::WHERE_AND;
            } else {
                $glue = Keyword::WHERE_OR;
            }
            $where = $this->_implode($this->_where, $glue);
        }
        
        return $where;
    }
    
    /**
     * quote user input
     * 
     * @param string $string
     * @param boolean $numeric
     * @return string
     */
    public function quote($string, $numeric = false)
    {
        return $this->_quote($string, $numeric);
    }
    
    /**
     * set table to select from
     * 
     * @param string $table
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function from($table, $alias = '')
    {
        $from = $this->_quoteTable($table);
        if ('' != $alias) {
            $from .= ' ' . $this->_quoteTable($alias);
        }
        $this->_table = $from;
        $this->_hasStatement = true;
        
        return $this;
    }
    
    /**
     * set table for insert / update / delete
     * 
     * @param string $table
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function table($table, $alias = '')
    {
        $this->_table = $this->_quoteTable($table);
        if ('' != $alias) {
            $this->_table .= ' ' . $this->_quoteTable($alias);
        }
        $this->_hasStatement = true;
        
        return $this;
    }
    
    /**
     * set table for insert
     * 
     * @param string $table
     * @return \Web\Database\Sql
     */
    public function into($table)
    {
        return $this->table($table);
    }
    
    /**
     * set fields to select ALL rows
     * 
     * @param array|string $fields
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectAll($fields = '', $alias = '')
    {
        if (! empty($fields)) {
            if ('' != $alias) {
                $alias = $this->_quoteTable($alias) . '.';
            }
            if (is_array($fields)) {
                foreach ($fields as $key => $field) {
                    if (! is_numeric($key)) {
                        if ($this->_helper->hasFunction($field)) {
                            $this->_fields[] = $field . Keyword::ALIAS . $this->_helper->quoteIdentifier($key);
                        } else {
                            $this->_fields[] = $this->_helper->quoteIdentifier($alias . $field) . ' AS ' . $this->_helper->quoteIdentifier($key);
                        }
                    } else {
                        if ($this->_helper->hasFunction($field)) {
                            $this->_fields[] = $field;
                        } else {
                            $this->_fields[] = $this->_helper->quoteIdentifier($alias . $field);
                        }
                    }
                }
            } else {
                if ($this->_helper->hasFunction($fields)) {
                    $this->_fields[] = $fields;
                } else {
                    $this->_fields[] = $this->_helper->quoteIdentifier($alias . $fields);
                }
            }
        }
        $this->_setSqlType(self::SELECT_ROWS);
        
        return $this;
    }
    
    /**
     * set fields to select FIRST row
     * 
     * @param array|string $fields
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectRow($fields = '', $alias = '')
    {
        $this->selectAll($fields, $alias);
        $this->_setSqlType(self::SELECT_ROW);
        
        return $this;
    }
    
    /**
     * set field to select COUNT
     * 
     * @param string $field field to count
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectCount($field = 'id', $alias = '')
    {
        if (! $this->_helper->hasFunction($field)) {
            if ('' != $alias) {
                $alias = $this->_helper->quoteIdentifier($alias) . '.';
            }
            $field = $this->_helper->quoteIdentifier($alias . $field);
        }
        $this->_fields = ['COUNT(' .  $field . ')'];
        $this->_setSqlType(self::SELECT_COUNT);
        
        return $this;
    }
    
    /**
     * set field to select COLUMN
     * 
     * @param string $field
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectColumn($field = 'id', $alias = '')
    {
        if (! $this->_helper->hasFunction($field)) {
            if ('' != $alias) {
                $alias = $this->_helper->quoteIdentifier($alias) . '.';
            }
            $field = $this->_helper->quoteIdentifier($alias . $field);
        }
        $this->_fields = [$field];
        $this->_setSqlType(self::SELECT_COLUMN);
        
        return $this;
    }
    
    /**
     * set field to select COLUMNS
     * 
     * @param string $field
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectColumns($field = 'id', $alias = '')
    {
        $this->selectColumn($field, $alias);
        $this->_setSqlType(self::SELECT_COLUMNS);
        
        return $this;
    }
    
    /**
     * set field to select PAIRS
     * 
     * @param array $fields
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function selectPairs($fields, $alias = '')
    {
        $single = true;
        if (is_array($fields)) {
            if (count($fields) > 1) {
                $single = false;
            }
        }
        $this->selectAll($fields, $alias);
        if ($single) {
            $this->_setSqlType(self::SELECT_COLUMNS);
        } else {
            $this->_setSqlType(self::SELECT_PAIRS);
        }
        
        return $this;
    }
    
    /**
     * set data for INSERT statement
     * 
     * @param array $data
     * @param boolean $hasFunctions
     * @return \Web\Database\Sql
     */
    public function insert($data)
    {
        $this->_setSqlType(self::INSERT);
        foreach ($data as $field => $value) {
            $this->_fields[] = $this->_quoteTable($field);
            $this->_values[] = $this->_quote($value);              
        }
        
        return $this;
    }
    
    /**
     * set data for UPDATE statement
     * 
     * @param array $data
     * @param boolean $hasFunctions
     * @return \Web\Database\Sql
     */
    public function update($data)
    {
        $this->_setSqlType(self::UPDATE);
        foreach ($data as $field => $value) {
            $this->_fields[] = $this->_quoteTable($field) . ' =' . $this->_quote($value);
        }
        
        return $this;
    }
    
    /**
     * set DELTE statement
     * 
     * @param array $data
     * @param boolean $hasFunctions
     * @return \Web\Database\Sql
     */
    public function delete()
    {
        $this->_setSqlType(self::DELETE);
        
        return $this;
    } 
    
    /**
     * set JOIN clause
     * 
     * @param string $table
     * @param string $on
     * @param boolean $innerJoin
     * @return \Web\Database\Sql
     */
    public function join($table, $on, $innerJoin = true)
    {
        if ($innerJoin) {
            $sql = Keyword::JOIN_INNER;
        } else {
            $sql = Keyword::JOIN_LEFT;
        }
        if (is_array($table)) {
            $first = current($table);
            $alias = key($table);
            $sql .= $this->_helper->quoteIdentifier($first) . ' ' . $this->_helper->quoteIdentifier($alias);
        } else {
            $sql .= $this->_helper->quoteIdentifier($table);
        }
        $sql .= Keyword::ON;
        $fields = explode('=', $on);
        if (2 == count($fields)) {
            $sql .= $this->_helper->quoteIdentifier(trim($fields[0])) . ' = ' . $this->_helper->quoteIdentifier(trim($fields[1]));
        } else {
            $sql .= $on;
        }
        $this->_joins[] = $sql;
        
        return $this;
    }
    
    /**
     * fields for GROUP BY clause
     * 
     * @param string|array $fields
     * @return \Web\Database\Sql
     */
    public function group($fields)
    {
        if (! is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $field) {
            $this->_group[] = $this->_helper->quoteIdentifier($field);
        }
        
        return $this;
    }
    
    /**
     * get statement result
     * 
     * @param boolean $resource get as resource
     * @return array|\PDO
     */
    public function execute($resource = false)
    {
        $result = false;
        if ($this->_hasStatement) {
            try {
                $adapter = $this->_helper->getAdapter();
                if (in_array($this->_sqlType, $this->_selectTypes)) {
                    $sql = $this->_buildSql();
                    $result = $adapter->query($sql);
                    if (! $resource) {
                        $data = [];
                        switch ($this->_sqlType) {
                            case self::SELECT_ROW:
                                $data = $result->fetch();
                                break;

                            case self::SELECT_COUNT:
                            case self::SELECT_COLUMN:
                                $data = $result->fetchColumn();
                                break;

                            case self::SELECT_COLUMNS:
                                $data = $result->fetchAll(PDO::FETCH_COLUMN);
                                break;

                            case self::SELECT_PAIRS:
                                $data = $result->fetchAll(PDO::FETCH_KEY_PAIR);
                                break;

                            default:
                                $data = $result->fetchAll();
                                break;
                        }
                        $result->closeCursor();
                        $result = $data;
                    }
                } else {
                    $sql = $this->_buildSql();
                    $result = $adapter->exec($sql);
                    if (self::INSERT == $this->_sqlType) {
                        $result = $adapter->lastInsertId();
                    }
                }
            } catch (PDOException $exception) {
                echo $exception->getMessage();
                $result = false;
            } catch (Exception $e) {
                echo $e->getMessage();
                $result = false;
            }
        }
        
        return $result;
    }
    
    /**
     * add subquery
     * 
     * @param string $query
     * @param string $alias
     * @return \Web\Database\Sql
     */
    public function subQuery($query, $alias = '')
    {
        $statement = $this->_inBrackets($query);
        if ('' != $alias) {
            $statement .= Keyword::ALIAS . $this->_helper->quoteIdentifier($alias);
        }
        $this->_subQueries[] = $statement;
        
        return $this;
    }
    
    /**
     * set AND where glue
     * 
     * @return $this
     */
    public function whereGlueAnd()
    {
        $this->_whereGlue = self::WHERE_AND;
        
        return $this;
    }
    
    /**
     * set OR where glue
     * 
     * @return $this
     */
    public function whereGlueOr()
    {
        $this->_whereGlue = self::WHERE_OR;
        
        return $this;
    }
    

    /**
     * build SQL statement
     * @return string
     */
    private function _buildSql()
    {
        if (! $this->_hasStatement) {
            return $this->getWhere();
        }
        
        $sql = '';
        if (in_array($this->_sqlType, $this->_selectTypes)) {
            $sql = Keyword::SELECT;
            if (empty($this->_fields)) {
                $sql .= '*';
            } else {
                $sql .= $this->_implode($this->_fields);
                if (! empty($this->_subQueries)) {
                    $sql .= Operator::COMMA . $this->_implode($this->_subQueries);
                }
            }
            $sql .= PHP_EOL . Keyword::FROM . $this->_table . PHP_EOL
                        . $this->_getJoins()
                        . $this->_getWhere()
                        . $this->_getGroupBy()
                        . $this->_getOrderBy()
                        . $this->_getLimit();
        } else {
            switch ($this->_sqlType) {
                case self::INSERT:
                    $sql = Keyword::INSERT . $this->_table 
                         . $this->_inBrackets($this->_implode($this->_fields))
                         . Keyword::VALUES
                         . $this->_inBrackets($this->_implode($this->_values));
                    break;

                case self::UPDATE:
                    $sql = Keyword::UPDATE . $this->_table . Keyword::SET
                         . $this->_implode($this->_fields)
                         . $this->_getWhere()
                         . $this->_getOrderBy()
                         . $this->_getLimit();
                    break;

                case self::DELETE:
                    $sql = Keyword::DELETE . $this->_table  
                         . $this->_getWhere()
                         . $this->_getOrderBy()
                         . $this->_getLimit();

                default:
                    break;
            }
        }
        
        return $sql;
    }    
    
    /**
     * quote value for SQL statement
     * 
     * @param string $string
     * @param boolean $numeric
     * @return string
     */
    private function _quote($string, $numeric = false)
    {
        $paramType = \PDO::PARAM_STR;
        if (null === $string || false === $string) {
            if ($numeric) {
                $quoted = '0';
            } else {
                $quoted = 'NULL';
            }
        } else {
            if ($numeric) {
                $paramType = \PDO::PARAM_INT;
            }
            $quoted = $this->_helper->pdoQuote($string, $paramType);
        }
        
        return $quoted;
    }
        
    /**
     * add where condition
     * 
     * @param string $where
     */
    private function _addWhere($where)
    {
        if ($this->_whereGroupStarted) {
            $this->_whereGroup[] = $where;
        } else {
            $this->_where[] = $where;
        }
    }
    
    /**
     * build condition
     * 
     * @param string $field
     * @param string $value
     * @param string $operator
     * @return string
     */
    private function _buildCondition($field, $value, $operator)
    {
        return $this->_helper->quoteIdentifier($field) . $operator . $this->_quote($value);
    }
    
    /**
     * capture where group
     */
    private function _captureWhereGroup()
    {
        $this->_whereGroupStarted = false;
        if (! empty($this->_whereGroup)) {
            if ($this->_whereGroupGlue == self::WHERE_OR) {
                $glue = self::GLUE_OR;
            } else {
                $glue = self::GLUE_AND;
            }
            $this->_addWhere($this->_implode($this->_whereGroup, $glue));
        }
    }
    
    /**
     * implode list with glue
     * 
     * @param array $list
     * @param string $glue default comma
     * @return string
     */
    private function _implode($list, $glue = ', ')
    {
        return implode($glue, $list);
    }
    
    /**
     * put string in brackets
     * 
     * @param string $string
     * @return string
     */
    private function _inBrackets($string)
    {
        return sprintf('(%s)', $string);
    }
    
    /**
     * set SQL type
     * 
     * @param int $type
     */
    private function _setSqlType($type)
    {
        $this->_sqlType = $type;
    }
    
    /**
     * get WHERE clause
     * 
     * @return string
     */
    private function _getWhere()
    {
        $statement = '';
        $where = $this->getWhere();
        if ('' != $where) {
            $statement = PHP_EOL . Keyword::WHERE . $where;
        }
        
        return $statement;
    }
    
    /**
     * get JOIN clauses
     * 
     * @return string
     */
    private function _getJoins()
    {
        return $this->_implode($this->_joins, PHP_EOL);
    }
    
    /**
     * get GROUP BY clause
     * 
     * @return string
     */
    private function _getGroupBy()
    {
        $statement = '';
        if (! empty($this->_group)) {
            $statement = PHP_EOL . Keyword::GROUP . $this->_implode($this->_group);
        }
        
        return $statement;
    }
    
    /**
     * get ORDER BY clause
     * 
     * @return string
     */
    private function _getOrderBy()
    {
        $statement = '';
        if (! empty($this->_order)) {
            $statement = PHP_EOL . Keyword::ORDER . $this->_implode($this->_order);
        }
        
        return $statement;
    }
    
    /**
     * get LIMIT clause
     * 
     * @return string
     */
    private function _getLimit()
    {
        $statement = '';
        if ('' != $this->_limit) {
            $statement = PHP_EOL . Keyword::LIMIT . $this->_limit;
        }
        
        return $statement;
    }
    
    /**
     * quote table identifier
     * 
     * @param string $table
     * @return string
     */
    private function _quoteTable($table)
    {
        return $this->_surround($table, Operator::BACKQUOTE);
    }
    
    /**
     * surround string be a symbol
     * 
     * @param string $string
     * @param string $symbol
     * @return string
     */
    private function _surround($string, $symbol)
    {
        return $symbol . $string . $symbol;
    }
    
    /**
     * to print the object
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_buildSql();
    }
    
}
