<?php
/**
 * Database Sql Select Total
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class Total extends Select
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
     * @param string $column
     * @return array
     */
    public function get($from = null, $where = null, $column = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        if (null !== $column) {
            $this->fields($column);
        }
        $this->bindClauses($where);
        return $this->rowCount();
    }
    
}
