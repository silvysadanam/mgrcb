<?php
/**
 * LoanType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class LoanType extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::LOAN_TYPE);
    }

}
