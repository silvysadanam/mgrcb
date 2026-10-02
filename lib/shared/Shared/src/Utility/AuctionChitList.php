<?php
/**
 * Shared AuctionChitList Utility
 * 
 * @category Shared
 * @package  Shared\Utility
 */

namespace Shared\Utility;

use Model\{AuctionChit, AuctionChitEntry};


class AuctionChitList
{  
    /**
     * 
     * @var \stdClass
     */
    private $_auctionChit;
    
    /**
     * 
     * @var array
     */
    private $_entries;
    
    
    /**
     * constructor
     */
    public function __construct($id)
    {
        $auctionChitModel = new AuctionChit();
        $auctionChitEntryModel = new AuctionChitEntry();
        $this->_auctionChit = $auctionChitModel->find($id);
        $this->_entries = $auctionChitEntryModel->select(['auction_chit_id' => $id]);
    }
    
    public function get()
    {
        return $this->_auctionChit;
    }
    
    public function entries()
    {
        return $this->_entries;
    }
    
}
