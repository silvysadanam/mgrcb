<?php
/**
 * CardType Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class CardType extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::CARD_TYPE);
    }

}
