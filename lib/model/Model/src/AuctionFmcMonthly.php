<?php
/**
 * AuctionFmcMonthly Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AuctionFmcMonthly extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::AUCTION_FMC_MONTHLY);
    }

}
