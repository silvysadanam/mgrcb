<?php
/** 
 * Framework cli only index file
 */

use Web\CliApplication;

define ('APP_DIR', __DIR__);
define ('WEB_LIB', __DIR__ . '/lib');

require WEB_LIB . '/autoload.php';
if (! defined('CLI')) {
    echo '_REQUEST not supported';
    exit;
}
$configs = require WEB_LIB . '/cliconfig.php';
$application = new CliApplication($configs);
$application->run($argv);

exit;
