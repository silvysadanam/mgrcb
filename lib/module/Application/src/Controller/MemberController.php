<?php

namespace Application\Controller;

use Web\Core\Registry;
use Application\Helper\JsonResponse;
use Web\Utility\DateHelper;
use Branch\Utility\BranchStore;
use Branch\Utility\MReceiptsReport;
use Model\DeductionMemberDaily;

class MemberController
{
    protected $_branch;
    
    private $_model;
    
    public function getAction()
    {
        ob_clean();
        header('Content-Type: application/json');
       
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            JsonResponse::send([
                'status' => false,
                'message' => 'Method Not Allowed'
            ], 405);
        }

        $code = Registry::get('slug');

        if (empty($code)) {
            JsonResponse::send([
                'status' => false,
                'message' => 'Member code required'
            ], 400);
        }

        /* Example DB fetch */
        $member = [
            'code'  => $code,
            'name'  => 'John Doe',
            'email' => 'john@example.com'
        ];

        JsonResponse::send([
            'status' => true,
            'data'   => $member
        ]);
    }
    
    public function dashboardAction()
    {
        ob_clean();
        header('Content-Type: application/json');
        $this->_model = new DeductionMemberDaily();
        $minDate = $this->_model->selectMin('deduction_date');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            JsonResponse::send([
                'status' => false,
                'message' => 'Method Not Allowed'
            ], 405);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
         if (empty($data['memberID']) || empty($data['fmc_id'])) {
            JsonResponse::send([
                'status' => false,
                'message' => 'MemberID or FMC is missing'
            ], 400);
            exit;
        }
        if (!is_array($data)) {
            $this->sendJson([
                'status' => false,
                'message' => 'Invalid JSON'
            ], 400);
        }
        
          // Validate required fields
            if (
                empty($data['start_date']) ||
                empty($data['end_date']) 
            ) {
               $data['start_date'] = DateHelper::datepicker($minDate);
               $data['end_date'] =  DateHelper::datepicker();
            }
            $this->_branch = new BranchStore($data['fmc_id']);
            $generator = new MReceiptsReport($this->_branch);
             $generator->setStartDate(DateHelper::fromDatepicker($data['start_date']))
                      ->setEndDate(DateHelper::fromDatepicker($data['end_date']))
                     // ->setReporType($data['group_by'])
                      ->setHeadId($data['memberID']);
            $generator->loadMReport();
            $report = $generator->report();
            $memberData = $generator->data();
             if (empty($report)) {
                JsonResponse::send([
                    'status' => false,
                    'message' => 'No records found'
                ], 401);
                exit;
            }
            
            JsonResponse::send([
            'status' => true,
            'status' => true,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'member_id' => $data['memberID'] ?? 0,
            'data' => $report,
            'member_name' =>  $memberData['memberName'],
            'total_catch' =>  (int) $memberData['totalCatch'],  
            'total_bill' => (int) $memberData['totalBill']
        ]);
        exit;
         
    }
}
