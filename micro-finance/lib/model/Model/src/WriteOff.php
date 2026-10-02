<?php
/**
 * WriteOff Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class WriteOff extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::WRITE_OFF);
    }

}
