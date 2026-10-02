<?php
/**
 * Database Sql Select Columns
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class Columns extends Select
{
    public function __construct($adapter = null)
    {
        parent::__construct($adapter);
    }
    
    /**
     * get result
     * 
     * @param string $from
     * @param string $column
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function get($from = null, $column = null, $where = null, $order = null, $limit = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        if (null !== $column) {
            $this->fields($column);
        }
        $this->bindClauses($where, $order, $limit);
        return $this->columns();
    }
    
}
