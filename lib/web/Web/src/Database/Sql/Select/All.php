<?php
/**
 * Database Sql Select All
 *
 * @category   Web\Database
 * @package    Web\Database\Sql
 * @subpackage Web\Database\Sql\Select
 */

namespace Web\Database\Sql\Select;

use Web\Database\Sql\Select;


class All extends Select
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
     * @param string $limit
     * @return array
     */
    public function get($from = null, $where = null, $order = null, $limit = null)
    {
        if (null !== $from) {
            $this->from($from);
        }
        $this->bindClauses($where, $order, $limit);
        return $this->all();
    }
    
}
