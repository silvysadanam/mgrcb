<?php
use Application\Controller\AuthController;
use Application\Controller\MemberController;
return [
    'POST /api/login' => function () {
        $controller = new AuthController();
        $controller->login();
    },
    // Mobile receipts (POST /api/member/dashboard)
    'POST /api/member/dashboard' => function () {
        $controller = new MemberController();
        $controller->dashboard();
    },
];