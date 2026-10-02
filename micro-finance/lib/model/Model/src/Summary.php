<?php
/**
 * Summary Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class Summary extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::SUMMARY);
    }

}
