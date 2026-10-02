<?php
/**
 * Cli App Db controller
 * 
 * @category App
 * @package  App\Controller
 */

namespace App\Controller;

use Web\Cli\CliController;
use Web\Database\Query;
use PDO;


class DbController extends CliController
{
    /**
     *
     * @var string 
     */
    private $_template;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function indexAction()
    {
        $this->start(__CLASS__  . ' Controller');
        // Do the stuff
        $this->_checkAppDirectory();
        
        $pdo = new Query();
        $statement = $pdo->query('SHOW TABLES');
        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (empty ($tables)) {
            $this->error('No tables found');   
        }
        sort($tables);
        $this->_loadTemplate();
        $list = [];
        $appPath = STORAGE_DIR . '/app/';
        $pattern = '    const %s = \'%s\';';
        foreach ($tables as $table) {
            $list[] = sprintf($pattern, strtoupper($table), $table);
        }
        if (file_exists($appPath . 'Db.php')) {
            unlink($appPath . 'Db.php');
        }
        $content = str_replace('[TABLES]', implode(PHP_EOL, $list), $this->_template);
        file_put_contents($appPath . 'Db.php', $content);

        $this->out('Success. Db file created in storage directory - "app/Db.php"');
        
        $this->finish();
    }

    private function _loadTemplate()
    {
        $this->_template = <<<EOT
<?php
/**
 * Database Table Db
 * 
 * @category Web
 * @package  Web\Database
 * @category Web\Database\Table
 */

namespace Web\Database\Table;


final class Db
{
[TABLES]
    
    public function __construct() { }
    
}


EOT;
            
    }
    
    private function _checkAppDirectory()
    {
        if (! is_dir(STORAGE_DIR)) {
            $this->_noDirectory(STORAGE_DIR);
        }
        if (! is_writable(STORAGE_DIR)) {
            $this->_notWritable(STORAGE_DIR);
        }
        if (! is_dir(STORAGE_DIR . '/app')) {
            mkdir(STORAGE_DIR . '/app');
        }
        if (! is_dir(STORAGE_DIR . '/app')) {
            $this->_noDirectory(STORAGE_DIR . '/app');
        }
    }
    
    private function _noDirectory($directory)
    {
        $this->_error(sprintf('Directory "%s" not found', $this->_convertDirectoryName($directory)));
    }
    
    private function _notWritable($directory)
    {
        $this->_error(sprintf('Directory "%s" is not writable', $this->_convertDirectoryName($directory)));
    }
    
    private function _error($message)
    {
        $this->out($message);
        $this->finish();
    }
    
    private function _convertDirectoryName($directory)
    {
        return substr($directory, strlen(APP_DIR) + 1);
    }
    
}