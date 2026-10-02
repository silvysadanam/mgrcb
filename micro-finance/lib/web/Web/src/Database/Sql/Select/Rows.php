<?php
/**
 * Database Sql Select Rows with fields specified
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class Rows extends Select
{
    public function __construct($adapter = null)
    {
        parent::__construct($adapter);
    }
    
    /**
     * get result
     * 
     * @param string $from
     * @param array|string $columns
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public function get($from = null, $columns = null, $where = null, $order = null, $limit = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        if (null !== $columns) {
            $this->fields($columns);
        }
        $this->bindClauses($where, $order, $limit);
        return $this->all();
    }
    
}
