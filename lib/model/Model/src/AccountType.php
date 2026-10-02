<?php
/**
 * AccountType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AccountType extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::ACCOUNT_TYPE);
    }

}
