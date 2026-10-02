<?php
/**
 * Database Sql Helper
 *
 * @category   Web
 * @package    Web\Database
 */

namespace Web\Database;

use Web\Database\Adapter;
use Web\Database\Sql\Operator;
use Web\Database\Sql\Keyword;
use PDO;


class SqlHelper
{
    /**
     *
     * @var \PDO 
     */
    protected $_adapter;
    
    public function __construct($adapter = null)
    {
        $this->_adapter = Adapter::load($adapter);
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
     * quote identifier
     * 
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        if ($this->hasFunction($identifier)) {
            return $identifier;
        }
        $clean = str_replace('`', '', $identifier);
        
        return sprintf('`%s`', str_replace('.', '`.`' , $clean));
    }
    
    /**
     * quote input for sql value
     * 
     * @param string $string
     * @param boolean $integer
     * @return string
     */
    public function quote($string, $integer = false)
    {
        if ($integer) {
            return intval($string);
        }
        
        return $this->_adapter->quote($string);
    }
    
    /**
     * quote string using PDO
     * 
     * @param string $string
     * @param int $paramType
     * @return string
     */
    public function pdoQuote($string,  $paramType = PDO::PARAM_STR)
    {
        return $this->_adapter->quote($string, $paramType);
    }
    
    /**
     * get = expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function equalTo($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::EQUAL_TO . '%d';
        } else {
            $format = '%s' . Operator::EQUAL_TO . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get id = value expression
     * 
     * @param int $id
     * @param string $column
     * @return string
     */
    public function bindId($id, $column = 'id')
    {
        return $this->equalTo($column, $id, true);
    }
    
    /**
     * get id <> value expression
     * 
     * @param int $id
     * @param string $column
     * @return string
     */
    public function bindNotId($id, $column = 'id')
    {
        return $this->notEqualTo($column, $id, true);
    }
    
    /**
     * get column = 1 expression
     * 
     * @param string $column
     * @return string
     */
    public function bindOne($column = 'id')
    {
        return $this->equalTo($column, 1, true);
    }
    
    /**
     * get column = 0 expression
     * 
     * @param string $column
     * @return string
     */
    public function bindZero($column = 'id')
    {
        return $this->equalTo($column, 0, true);
    }
    
    /**
     * get column = NULL expression
     * 
     * @param string $column
     * @return string
     */
    public function bindNull($column = 'id')
    {
        return $this->quoteIdentifier($column) . ' =NULL';
    }
    
    /**
     * get column = value expression
     * 
     * @param string $value
     * @param string $column
     * @return string
     */
    public function bindValue($value, $column)
    {
        return $this->equalTo($column, $value);
    }
    
    /**
     * get column > 0 expression
     * 
     * @param string $column
     * @return string
     */
    public function bindNotZero($column = 'id')
    {
        return $this->greaterThan($column, 0, true);
    }
    
    /**
     * get <> expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function notEqualTo($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::NOT_EQUAL_TO . '%d';
        } else {
            $format = '%s' . Operator::NOT_EQUAL_TO . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get > expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function greaterThan($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::GREATER_THAN . '%d';
        } else {
            $format = '%s' . Operator::GREATER_THAN . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get >= expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function greaterThanOrEqualTo($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::GREATER_THAN_OR_EQUAL_TO . '%d';
        } else {
            $format = '%s' . Operator::GREATER_THAN_OR_EQUAL_TO . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get < expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function lessThan($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::LESS_THAN . '%d';
        } else {
            $format = '%s' . Operator::LESS_THAN . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get >= expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $integer
     * @return string
     */
    public function lessThanOrEqualTo($column, $value, $integer = false)
    {
        if ($integer) {
            $format = '%s' . Operator::LESS_THAN_OR_EQUAL_TO . '%d';
        } else {
            $format = '%s' . Operator::LESS_THAN_OR_EQUAL_TO . '%s';
            $value = $this->quote($value);
        }
        
        return $this->format($format, $column, $value);
    }
    
    /**
     * get IN(..) expression
     * 
     * @param string $column
     * @param array $values
     * @param boolean $integer
     * @param boolean $not whether NOT IN(..)
     * @return string
     */
    public function in($column, $values, $integer = true, $not = false)
    {
        if (! $integer) {
            $list = [];
            foreach ($values as $item) {
                $list[] = $this->quote($item);
            }
            $values = $list;
        }
        if ($not) {
            $operator = Keyword::NOT_IN;
        } else {
            $operator = Keyword::IN;
        }
        
        return $this->format('%s' . $operator . '(%s)', $column, $this->combine($values));
    }

    /**
     * get NOT IN(..) expression
     * 
     * @param string $column
     * @param array $values
     * @param boolean $integer
     * @return string
     */
    public function notIn($column, $values, $integer = true)
    {
        return $this->in($column, $values, $integer, true);
    }

    /**
     * get LIKE expression
     * 
     * @param string $column
     * @param string $value
     * @param boolean $not
     * @return string
     */
    public function like($column, $value, $not = false)
    {
        if ($not) {
            $operator = Keyword::NOT_LIKE;
        } else {
            $operator = Keyword::LIKE;
        }
        
        return $this->quoteIdentifier($column) . $operator . $this->quote($value);
    }
    
    /**
     * get NOT LIKE expression
     * 
     * @param string $column
     * @param string $value
     * @return string
     */
    public function notLike($column, $value)
    {
        return $this->like($column, $value, true);
    }
    
    /**
     * get REGEXP expression
     * 
     * @param string $column
     * @param string $pattern
     * @param boolean $not
     * @return string
     */
    public function regexp($column, $pattern, $not = false)
    {
        if ($not) {
            $operator = Keyword::NOT_REGEXP;
        } else {
            $operator = Keyword::REGEXP;
        }
        
        return $this->quoteIdentifier($column) . $operator . $this->quote($pattern);
    }
    
    /**
     * get NOT REGEXP expression
     * 
     * @param string $column
     * @param string $pattern
     * @return string
     */
    public function notRegexp($column, $pattern)
    {
        return $this->regexp($column, $pattern, true);
    }
    
    /**
     * get BETWEEN .. AND expression
     * 
     * @param string $column
     * @param string $start
     * @param string $end
     * @return string
     */
    public function between($column, $start, $end)
    {
        return $this->quoteIdentifier($column) . Keyword::BETWEEN 
                . $this->quote($start) . Keyword::WHERE_AND . $this->quote($end);
    }
    
    /**
     * combine array with glue
     * 
     * @param array $list
     * @param string $glue
     * @return string
     */
    public function combine($list, $glue = ', ')
    {
        return implode($glue, $list);
    }

    /**
     * format expression
     * 
     * @param string $format
     * @param string $column
     * @param string $quotedValue
     * @return string
     */
    public function format($format, $column, $quotedValue)
    {
        return sprintf($format, $this->quoteIdentifier($column), $quotedValue);
    }
    
    /**
     * quote multiple columns
     * 
     * @param array $columns
     * @return string
     */
    public function quoteColumns($columns)
    {
        $list = [];
        foreach ($columns as $column) {
            $list[] = $this->quoteIdentifier($column);
        }
        
        return $this->combine($list);
    }
    
    /**
     * check value for SQL functions
     * 
     * @param string $string
     * @return boolean
     */
    public function hasFunction($string)
    {
        return 1 == preg_match('/\([^)]*\)/', $string);
    }
    
}
