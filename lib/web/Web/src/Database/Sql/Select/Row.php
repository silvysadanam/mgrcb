<?php
/**
 * Database Sql Select Row with specified fields
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class Row extends Select
{
    public function __construct($adapter = null)
    {
        parent::__construct($adapter);
    }
    
    /**
     * get result
     * 
     * @param string $from
     * @param string|array $columns
     * @param string $where
     * @param string $order
     * @return array
     */
    public function get($from = null, $columns = null, $where = null, $order = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        if (null !== $columns) {
            $this->fields($columns);
        }
        $this->bindClauses($where, $order, 1);
        return $this->row();
    }
    
}
