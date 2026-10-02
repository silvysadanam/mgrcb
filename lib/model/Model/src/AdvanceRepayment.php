<?php
/**
 * AdvanceRepayment Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AdvanceRepayment extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::ADVANCE_REPAYMENT);
    }

}
