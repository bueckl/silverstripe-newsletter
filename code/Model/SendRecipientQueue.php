<?php
/**
 * @package  newsletter
 */

/**
 * Database record for recipients that have had the newsletter sent to them, or are about to have a newsletter sent.
 */
namespace Newsletter\Model;

use SilverStripe\ORM\DataObject;

class SendRecipientQueue extends DataObject {

    private static $table_name = 'SendRecipientQueue';

	/**
	 *	Status has 4 possible values: "Sent", (mail() returned TRUE), "Failed" (mail() returned FALSE),
	 * 	"Bounced" ({@see $email_bouncehandler}), or "BlackListed" (sending to is disabled).
	 */
	private static $db = [
        "Status" => "Enum('Scheduled, InProgress, Sent, Failed, Bounced, BlackListed', 'Scheduled')",
        "RetryCount" => "Int(0)"    //number of times this email got "stuck" in the queue
    ];

	private static $has_one = [
        "Newsletter" => Newsletter::class,
        "Recipient" => Recipient::class
    ];

	private static $summary_fields = [
        "Status",
        "Recipient.Email",
        "RetryCount",
        "LastEdited",
    ];

	private static $default_sort = [
        'LastEdited DESC'
    ];

	public function fieldLabels($includelrelations = true) {
		$labels = parent::fieldLabels($includelrelations);

		$labels["Status"] = _t('Newsletter.FieldStatus', "Status");
		$labels["Recipient.Email"] = _t('Newsletter.FieldEmail', "Email");
		$labels["RetryCount"] = _t('Newsletter.FieldRetryCount', "Retry Count");
		$labels["LastEdited"] = _t('Newsletter.FieldLastEdited', "Last Edited");

		return $labels;
	}

	/** Send the email out to the Recipient */
	public function send($newsletter = null, $recipient = null) {
		if (empty($newsletter)) $newsletter = $this->Newsletter();
		if (empty($recipient)) $recipient = $this->Recipient();

		//check recipient not blacklisted and verified
		if ($recipient && empty($recipient->Blacklisted) && !empty($recipient->Verified)) {
			$email = new NewsLetterEmail(
				$newsletter,
				$recipient
			);
			if (!empty($newsletter->ReplyTo)) $email->addCustomHeader('Reply-To', $newsletter->ReplyTo);


			// HACK JOCHEN. ADDING ATTACHMENTS
			$attachment = $newsletter->Attachment();

			if ( $attachment ) {
				$file =  $attachment->getFullPath();
				// We check the filesize in bytes in order to see if the file realy exists
				if (file_exists($file) && ($attachment->getAbsoluteSize() > 5000)) {
					$email->attachFile( $file, $file );
				}
			}
			// END ATTACHMENTS

			$success = $email->send();

			if ($success) {
				$this->Status = 'Sent';
				$recipient->ReceivedCount = $recipient->ReceivedCount + 1;
			} else {
				$this->Status = 'Failed';
				$recipient->BouncedCount = $recipient->BouncedCount + 1;
			}
			$recipient->write();
		} else {
			$this->Status = 'BlackListed';
		}

		$this->write();
	}

}
