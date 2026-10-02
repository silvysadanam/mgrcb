<?php
/** 
 * Framework index file
 */
require_once __DIR__ . '/lib/module/Application/src/Helper/Cors.php';
use Web\Application;
use Application\Helper\Cors;
use Web\Core\Registry;
Cors::handle();
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

Registry::set('routes', [
    [
        'url'  => 'api/login',   // IMPORTANT: NO leading slash
        'type' => 'literal',
        'params' => [
            'module'     => 'application',
            'controller' => 'auth',
            'action'     => 'login',
            'template'   => false
        ]
    ],

    // Member dashboard API
    [
        'url'  => 'api/member/dashboard',
        'type' => 'literal',
        'params' => [
            'module'     => 'application',
            'controller' => 'member',
            'action'     => 'dashboard',
            'template'   => false
        ]
    ],

    // Member receipts API
    [
        'url'  => 'api/member/mobile-receipts',
        'type' => 'literal',
        'params' => [
            'module'     => 'application',
            'controller' => 'member',
            'action'     => 'mobileReceipts',
            'template'   => false
        ]
    ],
]);

$application->run();


exit;
