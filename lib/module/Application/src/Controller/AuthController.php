<?php

namespace Application\Controller;
use Application\Helper\JsonResponse;
use Application\ApplicationController;
use Application\Form\LoginForm;
use Web\View\ViewModel;
use Web\Mvc\View;
use Model\Member;
use Model\Fmc;

class AuthController extends ApplicationController
{
    /**
     *
     * @var \Model\Member
     */
    private $_model;
    
    public function __construct()
    {
        parent::__construct();
        $this->_model = new Member();
    }
    public function loginAction()
    {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            JsonResponse::send([
                'status' => false,
                'message' => 'Method Not Allowed'
            ], 405);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['credential']) || empty($data['password'])) {
            JsonResponse::send([
                'status' => false,
                'message' => 'Email and password required'
            ], 400);
            exit;
        }
        if (!is_array($data)) {
            $this->sendJson([
                'status' => false,
                'message' => 'Invalid JSON'
            ], 400);
        }

        $form = new LoginForm();
        $form->setModel($this->_model);
        $user = $form->validateUser($data['credential'], $data['password']);
        if($user['fmc_id']){
            $fmcModel = new Fmc();
            $fmc = $fmcModel->find($user['fmc_id']);
        }
        if ($user['status'] === 'invalid_access') {
            JsonResponse::send([
                'status' => false,
                'message' => 'Invalid Username / Password'
            ], 401);
            exit;
        }

       JsonResponse::send([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'memberId' => (int) $user['memberId'],
                'memberName'  => $user['memberName'],
                'memberFmc' => $fmc->name,
                'memberMobile' => $user['memberMobile'],
                'memberCode' => $user['memberCode'],
                'memberPhoto' => $user['photo'],
                'fmc_id' => $user['fmc_id']
            ]
        ]);
        exit;
    }

    /**
     * SAFE JSON OUTPUT — bypasses layout completely
     */
    private function sendJson(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
