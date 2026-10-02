<?php
/**
 * AuctioneerAccountMonthly Model
 *
 * @category   Model
 */

namespace Model;

use Web\Database\TableGateway;
use Web\Database\Table\Db;


class AuctioneerAccountMonthly extends TableGateway
{
    public function __construct()
    {
        parent::__construct(Db::AUCTIONEER_ACCOUNT_MONTHLY);
    }

}
