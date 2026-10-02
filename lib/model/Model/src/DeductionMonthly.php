<?php
/**
 * DeductionMonthly Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class DeductionMonthly extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::DEDUCTION_MONTHLY);
    }

}
