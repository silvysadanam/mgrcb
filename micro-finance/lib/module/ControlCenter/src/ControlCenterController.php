<?php
/**
 * ControlCenter Main Controller
 * 
 * @category ControlCenter
 */

namespace ControlCenter;

use Web\Mvc\ActionController;
use Web\Core\Registry;
use Web\Core\Session;
use Web\Core\Server;


class ControlCenterController extends ActionController
{
    const AUTHENTICATION_SESSION = 'ControlCenterSession';
    
    /**
     *
     * @var boolean
     */
    protected $_authenticated = false;
    
    /**
     *
     * @var boolean 
     */
    private $_ouputStarted = false;
    
    /**
     *
     * @var float 
     */
    private $_startTime;
    
    /**
     *
     * @var array 
     */
    private $_allowed;
        
    public function __construct()
    {
        $module = Registry::get('module');
        parent::__construct($module['session']);
        $this->layout->setLayout('');
        if (Session::is(self::AUTHENTICATION_SESSION)) {
            $this->_authenticated = true;
        }
        $this->_startTime = microtime(true);
        $this->_allowed = [];
        if (! empty($this->_allowed)) {
            $valid = false;
            $remoteAddress = Server::remoteAddress();
            if ('' != $remoteAddress) {
                $valid = in_array($remoteAddress, $this->_allowed);
            }
            
            $valid or exit();
        }
        
        set_time_limit(300);
    }

    /**
     * access restricted message
     * 
     * @param string $url
     */
    protected function accessRestricted($url = '/')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    /**
     * invalid access message
     * 
     * @param string $url
     */
    protected function invalidAccess($url = '')
    {
        $this->setFlashMessage('Access restricted', $url, 'error');
    }
    
    /**
     * check is authenticated
     * 
     * @return boolean
     */
    protected function isAuthenticated()
    {
        return $this->_authenticated;
    }
    
    /**
     * authentication required
     */
    protected function authenticationRequired()
    {
        if (! $this->_authenticated) {
            exit;
        }
    }
    
    /**
     * end authentication session
     */
    protected function endSession()
    {
        if ($this->_authenticated) {
            Session::clear(self::AUTHENTICATION_SESSION);
        }
        exit;
    }
    
    /**
     * authenticated
     */
    protected function authenticated()
    {
        Session::set(self::AUTHENTICATION_SESSION, 1);
    }
    
    protected function start($message = '')
    {
        $this->out('Starting...');
        if (! empty($message)) {
            $this->out($message);
        }
    }
    
    /**
     * print message or variable
     * 
     * @param mixed $message
     */
    protected function out($message, $title = '')
    {
        if (! $this->_ouputStarted) {
            $this->_drawHeader();
        }
        echo '<p>';
        if (is_scalar($message)) {
            if ('' != $title) {
                echo '<strong>' . $title . '</strong> : ';
            }
            echo htmlspecialchars($message, ENT_COMPAT, 'UTF-8', true);
        } else {
            if ('' != $title) {
                echo '<strong>' . $title . '</strong> : <br>';
            }
            echo '<pre>';
            print_r($message);
            echo '</pre>';
        }
        echo '</p>';
    }
    
    /**
     * finish output
     */
    protected function finish()
    {
        echo '<p>Finished at: ' . \Web\Utility\DateHelper::format('Y-m-d H:i:s') . '</p>';
        $time = sprintf('%0.4f', microtime(true) - $this->_startTime);
        $memory = memory_get_usage() / (1024 * 1024);
        echo '<p>Time taken: ' . $time . ' seconds<br>Memory usage: ' . sprintf('%0.3f', $memory) . ' MiB</p>';
        echo '</body></html>';
        exit;
    }
    
    /**
     * stop
     */
    protected function stop()
    {
        exit;
    }
    /**
     * print item to line
     * 
     * @param string $string
     */
    protected function _printItem($string)
    {
        echo ', ' .  $string;
    }
    
    /**
     * print line
     * 
     * @param string $string
     */
    protected function _printLine($string)
    {
        echo '<p>' .  $string .'</p>';
    }
    
    /**
     * get sql limit for list selection
     * 
     * @return string
     */
    protected function _getSqlLimit()
    {
        $sqlLimit = null;
        $limit = $this->getParam('limit', true);
        if ($limit > 0) {
            $sqlLimit = $limit;
            $offset = $this->getParam('offset', true);
            if ($offset > 0) {
                $sqlLimit = $offset . ',' . $limit;
            }
        }
        
        return $sqlLimit;
    }


    /**
     * output header
     */
    private function _drawHeader()
    {
        $this->_ouputStarted = true;
        $logout = $this->view->actionUrl('index/logout');
        
        echo <<<TEMPLATE
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Control Center - Console</title>
<style>
body { font-family: monospace, monospace; color: green; background-color: black; }
a { color : red; text-decoration : none; }
</style>
</head>
<body>
    <p><strong>Control Center :: Console</strong> [<a href="{$logout}" title="Exit">EXIT</a>]</p>
    <hr>

TEMPLATE;
        
    }

}
