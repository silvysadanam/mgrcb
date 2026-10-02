<?php
/**
 * DeductionBillEntry Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DeductionBillEntry extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DEDUCTION_BILL_ENTRY);
    }

}
