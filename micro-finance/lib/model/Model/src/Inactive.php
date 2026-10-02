<?php
/**
 * Inactive Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Inactive extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::INACTIVE);
    }

}
