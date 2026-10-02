<?php
/**
 * Administrator Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Administrator extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::ADMINISTRATOR);
    }

}
