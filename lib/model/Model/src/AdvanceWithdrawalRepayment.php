<?php
/**
 * AdvanceWithdrawalRepayment Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AdvanceWithdrawalRepayment extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::ADVANCE_WITHDRAWAL_REPAYMENT);
    }

}
