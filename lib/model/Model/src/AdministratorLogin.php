<?php
/**
 * AdministratorLogin Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AdministratorLogin extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::ADMINISTRATOR_LOGIN);
    }

}
