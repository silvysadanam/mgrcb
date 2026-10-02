<?php
/**
 * AuctioneerDaily Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AuctioneerDaily extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::AUCTIONEER_DAILY);
    }

}
