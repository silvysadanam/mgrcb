<?php
/**
 * Cli App Model controller
 * 
 * @category App
 * @package  App\Controller
 */

namespace App\Controller;

use Web\Cli\CliController;
use Web\Database\Query;
use PDO;


class ModelController extends CliController
{
    /**
     *
     * @var string 
     */
    private $_template;
    
    /**
     *
     * @var boolean 
     */
    private $_clearAll = false;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function indexAction()
    {
        $this->start(__CLASS__  . ' Controller');
        // Do the stuff
        $this->_checkAppDirectory(true);
        if ($this->_clearAll) {
            $this->_clearModels();
        }
        
        $pdo = new Query();
        $statement = $pdo->query('SHOW TABLES');
        $tables = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (empty ($tables)) {
            $this->error('No tables found');   
        }
        
        $this->_loadModelTemplate();
        $search = [ '[MODEL]', '[TABLE]' ];
        $modelPath = STORAGE_DIR . '/app/model/';
        foreach ($tables as $table) {
            $name = $this->_getModelName($table);
            $this->out('Table: ' . $table);
            $modelFile = $modelPath . $name . '.php';
            if (! file_exists($modelFile)) {
                $replace = [
                    $name,
                    strtoupper($table),
                ];
                $content = str_replace($search, $replace, $this->_template);
                file_put_contents($modelFile, $content);
                $this->out('Model: ' . $name . ' created');
            } else {
                $this->out('Model: ' . $name . ' exists');
            }
        }

        $this->out('Success. Models created in "app/model" storage directory');
        
        $this->finish();
    }
    
    public function tableAction()
    {
        
    }
    
    public function modelAction()
    {
        
    }
    
    
    private function _getModelName($table)
    {
        if (false !== strpos($table, '_')) {
            preg_match_all('/_([a-z])/', $table, $matches);
            $find = array_unique($matches[1]);
            $search = [];
            $replace = [];
            foreach ($find as $letter) {
                $search[] = '_' . $letter;
                $replace[] = ucfirst($letter);
            }
            $model = str_replace($search, $replace, $table);
        } else {
            $model = $table;
        }
        
        return ucfirst($model);
    }
    
    private function _loadModelTemplate()
    {
        $this->_template = <<<EOT
<?php
/**
 * [MODEL] Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class [MODEL] extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::[TABLE]);
    }

}

EOT;
            
    }
    
    private function _clearModels()
    {
        $modelDirectory = STORAGE_DIR . '/app/model';
        $list = scandir($modelDirectory);
        if (count($list) < 2) {
            return;
        }
        $remove = ['.', '..'];
        foreach ($remove as $item) {
            $index = array_search($item, $list);
            unset($list[$index]);
        }
        foreach ($list as $file) {
            unlink($modelDirectory . '/' . $file);
        }
        
    }
    
    
    private function _checkAppDirectory($models = true)
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
        if ($models) {
            if (! is_dir(STORAGE_DIR . '/app/model')) {
                mkdir(STORAGE_DIR . '/app/model');
            }
            if (! is_dir(STORAGE_DIR . '/app/model')) {
                $this->_noDirectory(STORAGE_DIR . '/app/model');
            }
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