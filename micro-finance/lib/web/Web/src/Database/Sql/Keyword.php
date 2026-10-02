<?php
/**
 * Database Sql Keyword
 *
 * @category   Web
 * @package    Web\Database
 * @subpackage Web\Database\Sql
 */

namespace Web\Database\Sql;


class Keyword
{
    const SELECT = 'SELECT ';
    const INSERT = 'INSERT INTO ';
    const UPDATE = 'UPDATE ';
    const DELETE = 'DELETE FROM ';
    const FROM = ' FROM ';
    const WHERE = ' WHERE ';
    const ORDER = ' ORDER BY ';
    const LIMIT = ' LIMIT ';
    const GROUP = ' GROUP BY ';
    const WHERE_AND = ' AND ';
    const WHERE_OR = ' OR ';
    const LIKE = ' LIKE ';
    const NOT_LIKE = ' NOT LIKE ';
    const IN = ' IN';
    const NOT_IN = ' NOT IN';
    const REGEXP = ' REGEXP ';
    const NOT_REGEXP = ' NOT REGEXP ';
    const BETWEEN = ' BETWEEN ';
    const STAR = '*';
    const SET = ' SET ';
    const SQL_NULL = 'NULL';
    const SQL_IS_NULL = ' IS NULL';
    const SQL_IS_NOT_NULL = ' IS NOT NULL';
    const DESC = ' DESC';
    const JOIN_INNER = ' INNER JOIN ';
    const JOIN_LEFT = ' LEFT JOIN ';
    const ON = ' ON ';
    const ALIAS = ' AS ';
    const VALUES = ' VALUES ';
    const DISTINCT = ' DISTINCT ';
    
    public function __construct() { }
    
}
