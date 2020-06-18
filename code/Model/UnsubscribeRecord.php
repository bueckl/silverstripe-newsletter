<?php
/**
 * @package  newsletter
 */

/**
 * Record to keep track of when a {@link Recipient} has
 * unsubscribed from a {@link MailingList}.
 */
namespace Newsletter\Model;

use SilverStripe\ORM\DataObject;

class UnsubscribeRecord extends DataObject {

    private static $table_name = 'UnsubscribeRecord';


	private static $has_one = [
        'MailingList' => MailingList::class,
        'Recipient' => Recipient::class
    ];

	/**
	 * Unsubscribe the recipient from a specific mailing list
	 *
	 * @param int|Recipient $recipient Recipient object or ID
	 * @param int|MailingList $mailinglist MailingList or ID
	 */
	function unsubscribe($recipient, $mailinglist) {
		// $this->UnsubscribeDate()->setVal( 'now' );
		$this->RecipientID = (is_numeric($recipient))
			? $recipient
			: $recipient->ID;

		$this->MailingListID = (is_numeric($mailinglist))
			? $mailinglist
			: $mailinglist->ID;
		$this->write();
	}
}
