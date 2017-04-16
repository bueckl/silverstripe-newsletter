<?php
/**
 * @package  newsletter
 */

/**
 * Record to keep track of when a {@link Recipient} has
 * unsubscribed from a {@link MailingList}.
 */
class UnsubscribeRecord extends DataObject {
    private static $has_one = array(
        'MailingList' => 'MailingList',
        'Member' => 'Member'
    );

    /**
     * Unsubscribe the recipient from a specific mailing list
     *
     * @param int|Recipient $recipient Recipient object or ID
     * @param int|MailingList $mailinglist MailingList or ID
     */
    function unsubscribe($recipient, $mailinglist) {
        // $this->UnsubscribeDate()->setVal( 'now' );
        $this->MemberID = (is_numeric($recipient))
            ? $recipient
            : $recipient->ID;

        $this->MailingListID = (is_numeric($mailinglist))
            ? $mailinglist
            : $mailinglist->ID;
        $this->write();
    }
}
