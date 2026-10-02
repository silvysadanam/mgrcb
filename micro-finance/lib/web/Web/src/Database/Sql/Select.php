<?php
/**
 * Database Sql Select
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;

use Web\Database\SqlHelper;
use Web\Database\Sql\Keyword;
use Web\Database\Sql\SelectType;
use Web\Database\Sql\Result;
use Web\Database\Sql\Clause\Where;
use Web\Database\Sql\Clause\Order;
use Web\Database\Sql\Clause\Limit;
use Web\Database\Sql\Clause\Group;


class Select
{
    /**
     *
     * @var \Web\Database\Sql\Clause\Where 
     */
    public $where;
    
    /**
     *
     * @var \Web\Database\Sql\Clause\Order 
     */
    public $order;
    
    /**
     *
     * @var \Web\Database\Sql\Clause\Limit 
     */
    public $limit;
    
    /**
     *
     * @var \Web\Database\Sql\Clause\Group 
     */
    public $group;
    
    /**
     *
     * @var string 
     */
    private $_from;
    
    /**
     *
     * @var int 
     */
    private $_resultType;
    
    /**
     *
     * @var array 
     */
    private $_fields;
    
    /**
     *
     * @var \Web\Database\SqlHelper 
     */
    private $_helper;
    
    /**
     *
     * @var string 
     */
    private $_primary = 'id';
    
    /**
     * construct
     * 
     * @param string $adapter
     * @param string $primary
     */
    public function __construct($adapter = null, $primary = null)
    {
        $this->where = new Where();
        $this->order = new Order();
        $this->limit = new Limit();
        $this->group = new Group();
        $this->_helper = new SqlHelper($adapter);
        if (null !== $primary) {
            $this->_primary = $primary;
        }
    }
    
    /**
     * set select FROM table
     * 
     * @param string $table
     * @return \Web\Database\Sql\Select
     */
    public function from($table)
    {
        $this->_from = $table;
        
        return $this;
    }
    
    /**
     * set ORDER BY
     * 
     * @param string|Order $order
     * @return \Web\Database\Sql\Select
     */
    public function order($order)
    {
        if ($order instanceof Order) {
            $this->order = $order;
        } else {
            $this->order->set($order);
        }
        
        return $this;
    }
    
    /**
     * set fields in result set
     * 
     * @param array $fields
     * @return \Web\Database\Sql\Select
     */
    public function fields($fields)
    {
        $this->_fields = $fields;
        
        return $this;
    }
    
    /**
     * set limit
     * 
     * @param string|int\Limit $limit
     * @return \Web\Database\Sql\Select
     */
    public function limit($limit)
    {
        if ($limit instanceof Limit) {
            $this->limit = $limit;
        } else {
            $this->limit->set($limit);
        }
        
        return $this;
    }
    
    /**
     * get SQL statement
     * 
     * @return string
     */
    public function getSql()
    {
        $sql = Keyword::SELECT;
        switch ($this->_resultType) {
            case SelectType::ROW_COUNT:
            case SelectType::HAS_ROW_COUNT:
                $field = $this->_primary;
                if (! empty($this->_fields)) {
                    if (is_array($this->_fields)) {
                        $field = $this->_fields[0];
                    } else {
                        $field = $this->_fields;
                    }
                }
                $sql .= 'COUNT(' . $this->_helper->quoteIdentifier($field) . ')';
                break;
            
            case SelectType::COLUMN_MAX:
            case SelectType::COLUMN_SUM:
            case SelectType::COLUMN_MIN:
                $field = $this->_primary;
                if (! empty($this->_fields)) {
                    if (is_array($this->_fields)) {
                        $field = $this->_fields[0];
                    } else {
                        $field = $this->_fields;
                    }
                }
                if (SelectType::COLUMN_MAX == $this->_resultType) {
                    $sql .= 'MAX';
                } elseif (SelectType::COLUMN_MIN == $this->_resultType) {
                    $sql .= 'MIN';
                } else {
                    $sql .= 'SUM';
                }
                $sql .= '(' . $this->_helper->quoteIdentifier($field) . ')';
                break;
                
            case SelectType::DISTINCT:
                $fields = Keyword::STAR;
                if (! empty($this->_fields)) {
                    if (is_array($this->_fields)) {
                        $fields = $this->_helper->quoteColumns($this->_fields);
                    } elseif (Keyword::STAR != $this->_fields) {
                        $fields = $this->_helper->quoteIdentifier($this->_fields);
                    }
                }
                $sql .= Keyword::DISTINCT . $fields;
                break;
                
            case SelectType::GROUP_COUNT:
                $sql .= $this->_helper->quoteIdentifier($this->_fields[0])
                      . ', COUNT(' . $this->_helper->quoteIdentifier($this->_fields[1]) . ')';
                break;
            
            default:
                if (SelectType::ROW_LAST == $this->_resultType) {
                    $order = $this->order->get();
                    if (! empty($order)) {
                        $this->order->add($this->_primary, Keyword::DESC);
                    }
                    $this->limit->set(1);
                }
                $fields = Keyword::STAR;
                if (! empty($this->_fields)) {
                    if (is_array($this->_fields)) {
                        $fields = $this->_helper->quoteColumns($this->_fields);
                    } elseif (Keyword::STAR != $this->_fields) {
                        $fields = $this->_helper->quoteIdentifier($this->_fields);
                    }
                }
                $sql .= $fields;
                break;
        }
        $sql .= Keyword::FROM . $this->_helper->quoteIdentifier($this->_from);
        $where = $this->where->get();
        if (! empty($where)) {
            $sql .= Keyword::WHERE . $where;
        }
        $group = $this->group->get();
        if (! empty($group)) {
            $sql .= Keyword::GROUP . $group;
        }
        $order = $this->order->get();
        if (! empty($order)) {
            $sql .= Keyword::ORDER . $order;
        }
        $limit = $this->limit->get();
        if (! empty($limit)) {
            $sql .= Keyword::LIMIT . $limit;
        }
        
        return $sql;
    }
    
    /**
     * set result type
     * 
     * @param int $type
     * @return \Web\Database\Sql\Select
     */
    public function setResultType($type)
    {
        $this->_resultType = $type;
        
        return $this;
    }
    
    /**
     * get result
     * 
     * @return array|string|int
     */
    public function getResult()
    {
        $sql = $this->getSql();
        $executor = new Result($this->_helper->getAdapter());
        switch ($this->_resultType) {
            case SelectType::ROW:
            case SelectType::ROW_LAST:
                $result = $executor->fetchRow($sql);
                break;
            
            case SelectType::COLUMN:
            case SelectType::COLUMN_MAX:
            case SelectType::COLUMN_SUM:
            case SelectType::COLUMN_MIN:
                $result = $executor->fetchColumn($sql);
                break;
            
            case SelectType::COLUMNS:
                $result = $executor->fetchColumns($sql);
                break;
            
            case SelectType::PAIRS:
            case SelectType::GROUP_COUNT:
                $result = $executor->fetchPairs($sql);
                break;
            
            case SelectType::ROW_COUNT:
                $result = (int) $executor->fetchColumn($sql);
                break;
            
            case SelectType::HAS_ROW_COUNT:
                $result = $executor->fetchColumn($sql) > 0;
                break;
            
            case SelectType::DISTINCT:
                $result = $executor->fetchColumns($sql);
                break;
            
            default:
                $result = $executor->fetchAll($sql);
                break;
        }
        
        return $result;
    }
    
    /**
     * select all
     * 
     * @return aray
     */
    public function all()
    {
        $this->_resultType = SelectType::ALL;
        
        return $this->getResult();
    }
    
    /**
     * select column
     * 
     * @return string
     */
    public function column()
    {
        $this->_resultType = SelectType::COLUMN;
        
        return $this->getResult();
    }
    
    /**
     * select columns
     * 
     * @return array
     */
    public function columns()
    {
        $this->_resultType = SelectType::COLUMNS;
        
        return $this->getResult();
    }
    
    /**
     * select single row
     * 
     * @return array
     */
    public function row()
    {
        $this->_resultType = SelectType::ROW;
        
        return $this->getResult();
    }
    
    /**
     * select pairs
     * 
     * @return array
     */
    public function pairs()
    {
        $this->_resultType = SelectType::PAIRS;
        
        return $this->getResult();
    }
    
    /**
     * select column MAX
     * 
     * @param string $column
     * @return string
     */
    public function columnMax($column = null)
    {
        $this->_resultType = SelectType::COLUMN_MAX;
        $this->_fields = $column;
        
        return $this->getResult();
    }
    
    /**
     * select column MIN
     * 
     * @param string $column
     * @return string
     */
    public function columnMin($column = null)
    {
        $this->_resultType = SelectType::COLUMN_MIN;
        $this->_fields = $column;
        
        return $this->getResult();
    }
    
    /**
     * select column SUM
     * 
     * @param string $column
     * @return int|float
     */
    public function columnSum($column)
    {
        $this->_resultType = SelectType::COLUMN_SUM;
        $this->_fields = $column;
        
        return $this->getResult();
    }
    
    /**
     * select last row
     * 
     * @return array
     */
    public function rowLast()
    {
        $this->_resultType = SelectType::ROW_LAST;
        
        return $this->getResult();
    }
    
    /**
     * get row count
     * 
     * @return int
     */
    public function rowCount()
    {
        $this->_resultType = SelectType::ROW_COUNT;
        
        return $this->getResult();
    }
    
    /**
     * check rows
     * 
     * @return boolean
     */
    public function hasRowCount()
    {
        $this->_resultType = SelectType::HAS_ROW_COUNT;
        
        return $this->getResult();
    }
    
    /**
     * select distinct
     * 
     * @return array
     */
    public function distinct()
    {
        $this->_resultType = SelectType::DISTINCT;
        
        return $this->getResult();
    }
    
    /**
     * select group count
     * 
     * @return array
     */
    public function groupCount()
    {
        $this->_resultType = SelectType::GROUP_COUNT;
        
        return $this->getResult();
    }
    
    /**
     * bind clauses
     * 
     * @param string|Where $where
     * @param string $order
     * @param string|int $limit
     * @return $this
     */
    public function bindClauses($where = null, $order = null, $limit = null)
    {
        if (null !== $where) {
            $this->where->add($where);
        }
        if (null !== $order) {
            $this->order($order);
        }
        if (null !== $limit) {
            $this->limit($limit);
        }
        
        return $this;
    }
    
}
