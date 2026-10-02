<?php
/**
 * Database Sql Syntax
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;


class Syntax
{
    const SELECT = 'SELECT %s FROM %s WHERE %s';
    const INSERT = 'INSERT INTO %s (%s) VALUES(%s)';
    const UPDATE = 'UPDATE %s SET %s WHERE %s';
    const DELETE = 'DELETE FROM %s WHERE %s';
    const ORDER = ' ORDER BY %s ';
    const LIMIT = ' LIMIT %s ';
    const GROUP = ' GROUP BY %s ';
    const IN = ' IN(%s) ';
    const NOT_IN = ' NOT IN(%s) ';
    const BETWEEN = ' BETWEEN %s AND %s ';
    const BRACKETS = '(%s)';
    
    public function __construct() { }
    
}
