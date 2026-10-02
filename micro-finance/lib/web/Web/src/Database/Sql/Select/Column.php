<?php
/**
 * Database Sql Select Column
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class Column extends Select
{
    public function __construct($adapter = null)
    {
        parent::__construct($adapter);
    }
    
    /**
     * get result
     * 
     * @param string $from
     * @param string $where
     * @param string $order
     * @return array
     */
    public function get($from = null, $column = null, $where = null, $order = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        if (null !== $column) {
            $this->fields($column);
        }
        $this->bindClauses($where, $order, 1);
        return $this->column();
    }
    
}
