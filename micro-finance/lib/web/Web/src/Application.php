<?php
/* 
 * Application
 * 
 * @category Web
 * 
 */

namespace Web;

use Web\Core\Registry;
use Web\Mvc\Module;
use Web\Mvc\Router;
use Web\Core\Server;
use Web\Core\Session;
use Web\Mvc\ServiceManager;
use Web\Core\ErrorManager;
use Web\Cli\CliManager;


class Application
{
    const RELOAD_CACHE = 'cache';
    
    private $_useCache = false;

    public function __construct($configs)
    {
        $readFresh = $this->_checkCache();
        $this->_readConfig();
        $this->_loadConfigs($configs);
        $this->_loadEnvironment();
        $this->_loadErrorManager();
    }
    
    private function _checkCache()
    {
        $read = true;
        if (null !== filter_input(INPUT_GET, self::RELOAD_CACHE)) {
            
        }
        
        return $read;
    }


    /**
     * Run CLI request
     * 
     * @param array $argv commandline arguments
     */
    public function cli($argv)
    {
        $this->_readCliConfig();
        Router::init(false);
        $route = Router::cli($argv);
        $cliManager = new CliManager();
        $cliManager->run($route);
    }
    
    /**
     * Run web request
     */
    public function run()
    {
        $this->_loadPaths();
        Session::init();
        Module::init();
        Router::init(true);
        $route = Router::web();
        $serviceManager = new ServiceManager();
        $serviceManager->run($route);
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
     * load URL/file paths
     */
    private function _loadPaths()
    {
        if (! defined('CLI')) {
            $baseUrl = fwdp(dirname(Server::scriptName()));
            if ('/' != $baseUrl) {
                $baseUrl .= '/';
            }
            Registry::set('baseUrl', $baseUrl);
            Registry::set('actionUrl', $baseUrl);
            Registry::set('mediaUrl', $baseUrl . MEDIA_URL);
            Registry::set('uri', Server::requestUri());
            Registry::set('siteUrl', Server::protocol() . '://' . Server::host() . $baseUrl);
            Registry::set('ajax', Server::xmlHttpRequest());
            Registry::set('uploadUrl', $baseUrl . UPLOAD_URL);
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
                    Registry::set($key, $value);
                }
            }
        }
    }
    
}
