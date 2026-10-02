<?php
/**
 * Database Sql Operator
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;


class Operator
{
    const EQUAL_TO = ' =';
    const NOT_EQUAL_TO = ' <> ';
    const GREATER_THAN = ' > ';
    const GREATER_THAN_OR_EQUAL_TO = ' >= ';
    const LESS_THAN = ' < ';
    const LESS_THAN_OR_EQUAL_TO = ' <= ';
    const COMMA = ', ';
    const PERCENTAGE = '%';
    const BACKQUOTE = '`';
    
    public function __construct() { }
    
}
