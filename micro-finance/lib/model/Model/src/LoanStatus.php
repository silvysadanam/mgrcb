<?php
/**
 * LoanStatus Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class LoanStatus extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::LOAN_STATUS);
    }

}
