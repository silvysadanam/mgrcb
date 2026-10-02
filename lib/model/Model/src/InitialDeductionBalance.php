<?php
/**
 * InitialDeductionBalance Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class InitialDeductionBalance extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::INITIAL_DEDUCTION_BALANCE);
    }

}
