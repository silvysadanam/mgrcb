<?php
/**
 * Database Table Db
 * 
 * @category Web
 * @package  Web\Database
 * @category Web\Database\Table
 */

namespace Web\Database\Table;


final class Db
{
    const ADMINISTRATOR = 'administrator';
    const FMC = 'fmc';
    const INACTIVE = 'inactive';
    const LOAN = 'loan';
    const LOAN_COMMENT = 'loan_comment';
    const LOAN_RESCHEDULE = 'loan_reschedule';
    const LOAN_SOURCE = 'loan_source';
    const LOAN_STATUS = 'loan_status';
    const LOAN_SUMMARY = 'loan_summary';
    const LOAN_TIMELINE = 'loan_timeline';
    const LOAN_TYPE = 'loan_type';
    const LOGIN = 'login';
    const MEMBER = 'member';
    const MEMBER_CATCH = 'member_catch';
    const MEMBER_TYPE = 'member_type';
    const NOTIFICATION = 'notification';
    const NOTIFICATION_COMMENT = 'notification_comment';
    const PQAM = 'pqam';
    const SCHEDULE = 'schedule';
    const SUMMARY = 'summary';
    const WRITE_OFF = 'write_off';
    
    const ACCOUNT = 'account';
    const ACCOUNT_TYPE = 'account_type';
    const BANK = 'bank';
    const BOAT = 'boat';
    const CARD_TYPE = 'card_type';
    const DESIGNATION_TYPE = 'designation_type';
    const STAFF = 'staff';
    const DOCUMENT_TYPE = 'document_type';
    const RATION_CARD = 'ration_card';
    const PANCHAYATH_BRAND = 'panchayath_brand';
    const DISEASE = 'disease';
    const MEMBER_CARD = 'member_card';
    const MEMBER_DOCUMENT = 'member_document';
    const MEMBER_PHOTO = 'member_photo';
    const STAFF_PHOTO = 'staff_photo';
    const LOAN_NUMBER = 'loan_number';
    const RP_TRANS_MEMBER = 'rp_trans_member';
    const RP_TRANS_MEMBER_DETAILS = 'rp_trans_member_details';
    const RP_TRANSACTION_DETAILS = 'rp_transactiondetails';
    const RP_ACCOUNT_SETTINGS = 'rp_account_settings';
    const RP_HEADS = 'rp_head';
    const RP_BSHEADS = 'rp_balance_sheet';

    const CLOSEOUT_DETAILS = 'closeout_details';
    const CLOSEOUT_DATA = 'closeout_data';
    const OPENING_DETAILS = 'opening_details';
    
    public function __construct() { }
    
}

