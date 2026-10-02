<?php
/**
 * AuctionChit Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class RPTransactionDetails extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::RP_TRANSACTION_DETAILS);
    }

}
