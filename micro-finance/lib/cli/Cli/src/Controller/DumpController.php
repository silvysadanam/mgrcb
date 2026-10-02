<?php
/**
 * Cli Index controller
 * 
 * @category Cli
 * @package Cli\Controller
 */

namespace Cli\Controller;

use Web\Cli\CliController;
use Web\Database\Model;
use Web\Utility\DateHelper;

use PDO;


class DumpController extends CliController
{
    const DATE_FORMAT = 'YmdHis';
    
    /**
     * 
     * @var \Web\Database\Model
     */
    private $_model;
    
    /**
     * 
     * @var array
     */
    private $_tables;
    
    /**
     * 
     * @var resource
     */
    private $_writer;
    
    /**
     * 
     * @var string
     */
    private $_dumpDirectory;
    
    public function __construct()
    {
        parent::__construct();
        $directory = dirname(APP_DIR, 2);
        $this->_dumpDirectory = $directory . '/dbbackup';
    }
    
    /**
     * index action
     * 
     * @return \Web\View\ViewModel
     */
    public function indexAction()
    {
        $this->start('Dump Controller');
        $this->_model = new Model();
        $this->_loadWriter();
        $this->_preset();
        $this->_loadTables();
        foreach ($this->_tables as $table) {
            $this->_structure($table);
            $this->_data($table);
        }
        $this->_nextLine();
        $this->_closure();
        
        fclose($this->_writer);
        $this->finish();
    }
    
    
    /**
     * load tables
     */
    private function _loadTables()
    {
        $query = 'SHOW TABLES';
        $result = $this->_model->query($query);
        $this->_tables = $result->fetchAll(PDO::FETCH_COLUMN);
        $this->_logger->query();
    }
    
    /**
     * load writer
     */
    private function _loadWriter()
    {
        $file = $this->_dumpDirectory . '/' . DateHelper::format(self::DATE_FORMAT) . '.sql';
        if (false === ($this->_writer = fopen($file, 'w'))) {
            die('File open failed: "' . $file . '"');
        }
    }
    
    /**
     * preset
     */
    private function _preset()
    {
        $settings = 'SET FOREIGN_KEY_CHECKS=0;' . PHP_EOL
                  . 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL
                  . 'SET time_zone = "+00:00";'  . PHP_EOL
                  . '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' . PHP_EOL
                  . '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' . PHP_EOL
                  . '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' . PHP_EOL
                  . '/*!40101 SET NAMES utf8mb4 */;' . PHP_EOL;
        $this->_write($settings);
    }
    
    /**
     * closure
     */
    private function _closure()
    {
        $settings = '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . PHP_EOL
                  . '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . PHP_EOL
                  . '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;' . PHP_EOL
                  . 'SET FOREIGN_KEY_CHECKS=1;' . PHP_EOL;
        $this->_write($settings);
    }
    
    /**
     * export structure
     * 
     * @param string $table
     */
    private function _structure($table)
    {
        $this->_printLine('Table structure for ' . $table);
        $query = sprintf('SHOW CREATE TABLE `%s`', $table);
        $result = $this->_model->query($query);
        $structure = $result->fetchColumn(1);
        $result->closeCursor();
        $this->_nextLine();
        $this->_write($structure . ';');
        $this->_logger->query();
    }
    
    /**
     * export data
     * 
     * @param string $table
     * @return null
     */
    private function _data($table)
    {
        $this->_printLine('Table data for ' . $table);
        $this->_logger->query();
        $rows = $this->_model->selectCount($table);
        if (0 == $rows) {
            return;
        }
        
        $this->_getInsert($table);
        $this->_exprotValues($table, $rows);
    }
    
    /**
     * advance to next line
     */
    private function _nextLine()
    {
        $this->_write(PHP_EOL . PHP_EOL);
    }
    
    /**
     * export insert statement
     * 
     * @param string $table
     */
    private function _getInsert($table)
    {
        $show = sprintf('SHOW COLUMNS FROM `%s`', $table);
        $result = $this->_model->query($show);
        $fields = $result->fetchAll(PDO::FETCH_COLUMN);
        $columns = '`' . implode('`, `', $fields) . '`';
        
        $insert = sprintf('INSERT INTO `%s` (%s) VALUES ', $table, $columns);
        $this->_write($insert);
        $this->_logger->query();
    }
    
    /**
     * export values
     * 
     * @param string $table
     * @param int $rows
     */
    private function _exprotValues($table, $rows)
    {
        $query = sprintf('SELECT * FROM `%s` ORDER BY `id`', $table);
        $result = $this->_model->query($query);
        $this->_logger->query();
        $count  = 0;
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            ++$count;
            $data = [];
            foreach ($row as $index => $item) {
                if ($index > 0) {
                    if ('' == $item) {
                        $data[] = 'NULL';
                    } else {
                        $data[] = $this->_model->quote($item);
                    }
                    
                } else {
                    $data[] = $item;
                }
            }
            if ($count == $rows) {
                $pattern = '(%s);' . PHP_EOL;
            } else {
                $pattern = '(%s),' . PHP_EOL;
            }
            
            $record = sprintf($pattern, implode(', ', $data));
            $this->_write($record);
        }
    }
    
    /**
     * write line
     * 
     * @param string $data
     */
    private function _write($data)
    {
        fwrite($this->_writer, $data);
    }
    
    /**
     * print line
     * 
     * @param string $string
     */
    private function _printLine($string)
    {
        echo $string . PHP_EOL;
    }
    
}
