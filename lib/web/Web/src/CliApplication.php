<?php
/**
 * CliApplication
 * 
 * @category Web
 */

namespace Web;

use Web\Core\Registry;
use Web\Core\ErrorManager;
use Web\Cli\CliManager;
use Web\Cli\CliRouter;


class CliApplication
{
    private $_cliNamespace;
    
    public function __construct($configs)
    {
        $this->_readConfig();
        $this->_loadConfigs($configs);
        $this->_loadEnvironment();
        $this->_loadErrorManager();
        $this->_cliNamespace = CliManager::CLI_NAMESPACE;
    }
    
    /**
     * set cli namespace
     * 
     * @param string $namespace
     */
    public function setCliNamespace($namespace)
    {
        $this->_cliNamespace = $namespace;
    }
    
    /**
     * Run CLI request
     * 
     * @param array $argv commandline arguments
     */
    public function run($argv)
    {
        $this->_readCliConfig();
        CliRouter::init();
        $route = CliRouter::cli($argv);
        $cliManager = new CliManager($this->_cliNamespace);
        $cliManager->run($route);
    }
    
    /**
     * read config file
     */
    private function _readConfig()
    {
        $file = LIB_DIR . DS . 'config/application.ini';
        if (file_exists($file)) {
            if (false !== ($config = parse_ini_file($file, true))) {
                Registry::set('application', $config);
                foreach ($config as $key => $value) {
                    Registry::set($key, $value);
                }
            }
        }
    }
    
    /**
     * load configs
     * 
     * @param array $configs
     */
    private function _loadConfigs($configs)
    {
        if (! empty($configs)) {
            foreach ($configs as $key => $value) {
                if ('registry' == $key) {
                    foreach ($value as $name => $option) {
                        Registry::set($name, $option);
                    }
                } else {
                    Registry::set($key, $value);
                }
            }
        }
    }
    
    /**
     * load application environment
     */
    private function _loadEnvironment()
    {
        if (null === Registry::get('environment')) {
            if (getenv('APP_ENV')) {
                $environment = getenv('APP_ENV');
            } else {
                $environment = 'production';
            }
            Registry::set('environment', $environment);
        }
    }
    
    /**
     * load error manager
     * 
     * @return ErrorManager
     */
    private function _loadErrorManager()
    {
        return new ErrorManager();
    }
    
    /**
     * read cli config file
     */
    private function _readCliConfig()
    {
        $file = LIB_DIR . DS . 'config/cli.ini';
        if (file_exists($file)) {
            if (false !== ($config = parse_ini_file($file, true))) {
                Registry::set('cli', $config);
                foreach ($config as $key => $value) {
                     if ('registry' == $key) {
                        foreach ($value as $name => $option) {
                            Registry::set($name, $option);
                        }
                    } else {
                        Registry::set($key, $value);
                    }
                }
            }
        }
    }
    
}
