<?php
/**
 * check PHP version support
 */
define ('VERSION_MIN', '7.0');
if (true === version_compare(PHP_VERSION, VERSION_MIN, '<')) {
    die('This application require PHP version "' . VERSION_MIN . '" or higher.');
}
define('PROJECT_ROOT', dirname(dirname(__FILE__))); 
/**
 * Autoload configurations
 */
define ('DS', '/');
define ('WDS', '\\');
define ('BS', '\\');
$windows = preg_match('/win/i', PHP_OS);
define ('OS_WINDOWS', $windows);

function base_url(){ 
   return sprintf( 
    "%s://%s%s", 
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', 
    $_SERVER['SERVER_NAME'], 
    $_SERVER['REQUEST_URI'] 
   ); 
} 


$base_url= !empty ($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
$base_url= str_replace('\\', '/', $base_url);
$truncate= strpos($base_url, !empty ($_SERVER['SCRIPT_FILENAME']) ? basename($_SERVER['SCRIPT_FILENAME']) : '.php');
if ($truncate !== false){
   $base_url= substr($base_url, 0, $truncate);
}

$base_url = str_replace('micro-finance/', '', $base_url);
define('HOME', $base_url);

/**
 * fix Windows directory path
 * 
 * @param string $path
 * @return string
 */
function fwdp($path)
{
    if (1 == OS_WINDOWS) {
        return str_replace(WDS, DS, $path);
    }
    return $path;
}

/**
 * application configurations and settings
 */
ini_set ('default_charset', 'UTF-8');
ini_set ('display_errors', 'on');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');
define ('LIB_DIR', fwdp(__DIR__));
define ('WEB_DIR', LIB_DIR . DS . 'web');
define ('CLI_DIR', LIB_DIR . DS . 'cli');
define ('MODULE_DIR', LIB_DIR . DS . 'module');
define ('VENDOR_DIR', LIB_DIR . DS . 'vendor');
define ('MEDIA_URL', 'media/');
define ('STORAGE_DIR', LIB_DIR . DS . 'storage');
define ('UPLOAD_DIR', APP_DIR . DS . 'assets');
define ('UPLOAD_URL', 'assets/');

if ('cli' === PHP_SAPI) {
    define('CLI', 1);
    $paths = [
        'Web' => WEB_DIR . DS . 'Web/src',
        'Cli' => CLI_DIR . DS . 'Cli/src',
        'App' => CLI_DIR . DS . 'App/src',
        'Model' => LIB_DIR . DS . 'model/Model/src',
        'Shared' => LIB_DIR . DS . 'shared/Shared/src',
    ];
} else {
    $paths = [
        'Web' => WEB_DIR . DS . 'Web/src',
        'Model' => LIB_DIR . DS . 'model/Model/src',
        'Shared' => LIB_DIR . DS . 'shared/Shared/src',
        'Application' => MODULE_DIR . DS . 'Application/src',
        'ControlCenter' => MODULE_DIR . DS . 'ControlCenter/src',
    ];
}

// set vendor packages
$vendors = [
    'PHPMailer' => 'src',
    'Ellumilel' => 'src',
];

//vendors with special namespace
$customVendors = [];

require LIB_DIR . DS . 'loader.php';

$loader = new PSR4ClassAutoloader();
foreach ($paths as $namespace => $path) {
    $loader->registerNamespace($namespace, $path);
}

if (! empty($vendors)) {
    foreach ($vendors as $namespace => $source) {
        $loader->registerVendor($namespace, $source);
    }
}

if (! empty($customVendors)) {
    foreach ($customVendors as $vendor) {
        $loader->registerVendorNamespace($vendor[0], $vendor[1]);
    }
}

$loader->registerAutoload();
