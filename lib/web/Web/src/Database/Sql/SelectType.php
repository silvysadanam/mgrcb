<?php
/**
 * 
 * Database Sql SelectType
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;


class SelectType
{
    const ALL = 1;
    const ROW = 2;
    const COLUMN = 3;
    const COLUMNS = 4;
    const PAIRS = 5;
    const ROW_COUNT = 6;
    const HAS_ROW_COUNT = 7;
    const COLUMN_MAX = 8;
    const COLUMN_SUM = 9;
    const ROW_LAST = 10;
    const DISTINCT = 11;
    const GROUP_COUNT = 12;
    const COLUMN_MIN = 13;

    public function __construct() { }
    
}
