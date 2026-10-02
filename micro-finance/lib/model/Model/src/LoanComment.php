<?php
/**
 * LoanComment Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class LoanComment extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::LOAN_COMMENT);
    }

}
