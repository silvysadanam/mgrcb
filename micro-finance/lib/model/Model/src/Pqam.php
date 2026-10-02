<?php
/**
 * Pqam Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Pqam extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::PQAM);
    }

}
