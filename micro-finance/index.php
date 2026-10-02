<?php
/** 
 * Framework index file
 */

use Web\Application;

define ('APP_DIR', __DIR__);
define ('WEB_LIB', __DIR__ . '/lib');

define ('USE_CACHE', 0);

require WEB_LIB . '/autoload.php';
$configs = require WEB_LIB . '/config.php';

$application = new Application($configs);
if (defined('CLI')) {
    $application->cli($argv);
    exit;
}
$application->run();


exit;
