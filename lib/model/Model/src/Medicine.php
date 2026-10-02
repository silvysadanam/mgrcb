<?php
/**
 * Medicine Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Medicine extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::MEDICINE);
    }

}
