<?php
/**
 * DeductionBillNote Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DeductionBillNote extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DEDUCTION_BILL_NOTE);
    }

}
