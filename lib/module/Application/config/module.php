<?php
/**
 * Application Module config
 */

use Application\Controller\AuthController;
use Application\Controller\MemberController;

return [
    'session' => 'default',
    'view'    => 'view',
    'plugins' => [],
    
    /* ================= ROUTER ================= */
    'router' => [
        'routes' => [

            'api-login' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/api/login',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action'     => 'login',
                        'view'       => false,
                    ],
                ],
            ],

            'api-member-get' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/api/member/:code',
                    'defaults' => [
                        'controller' => MemberController::class,
                        'action'     => 'get',
                        'view'       => false,
                    ],
                ],
            ],

            'api-dashboard' => [
                'type' => 'Literal',
                'options' => [
                    'route' => 'api/member/dashboard',
                    'defaults' => [
                        'controller' => MemberController::class,
                        'action'     => 'dashboard',
                        'view'       => false,
                    ],
                ],
            ],

        ],
    ],

    /* ============== CONTROLLERS =============== */
   'controllers' => [
        'factories' => [
            AuthController::class => function ($container) {
                return new AuthController($container);
            },
            MemberController::class => function ($container) {
                return new MemberController();
            },
        ],
    ],
];
