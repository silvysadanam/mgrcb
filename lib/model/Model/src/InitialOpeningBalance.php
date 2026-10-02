<?php
/**
 * InitialOpeningBalance Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class InitialOpeningBalance extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::INITIAL_OPENING_BALANCE);
    }

}
