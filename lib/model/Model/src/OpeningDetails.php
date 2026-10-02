<?php
/**
 * DeductionType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class OpeningDetails extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::OPENING_DETAILS);
    }

}
