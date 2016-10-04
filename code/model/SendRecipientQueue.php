<?php
/**
 * @package  newsletter
 */

/**
 * Database record for recipients that have had the newsletter sent to them, or are about to have a newsletter sent.
 */
class SendRecipientQueue extends DataObject {
	/**
	 *	Status has 4 possible values: "Sent", (mail() returned TRUE), "Failed" (mail() returned FALSE),
	 * 	"Bounced" ({@see $email_bouncehandler}), or "BlackListed" (sending to is disabled).
	 */
	private static $db = array(
		"Status" => "Enum('Scheduled, InProgress, Sent, Failed, Bounced, BlackListed', 'Scheduled')",
		"RetryCount" => "Int(0)"    //number of times this email got "stuck" in the queue
	);

	private static $has_one = array(
		"Newsletter" => "Newsletter",
		"Recipient" => "Recipient"
	);

	private static $summary_fields = array(
		"Status",
		"Recipient.FirstName",
		"Recipient.Surname",
		"Recipient.Email",
		"RetryCount",
		"ReceivedCount",
		"LastEdited",
	);

	private static $default_sort = array(
		'LastEdited DESC'
	);
	
	public function ReceivedCount() {
		return $this->Recipient()->ReceivedCount;
	}
	public function fieldLabels($includelrelations = true) {
		$labels = parent::fieldLabels($includelrelations);
		
		$labels["Status"] = _t('Newsletter.FieldStatus', "Status");
		$labels["Recipient.FirstName"] = _t('Newsletter.FieldFirstName', "Vorname");
		$labels["Recipient.Surname"] = _t('Newsletter.FieldSurname', "Nachname");
		$labels["Recipient.Email"] = _t('Newsletter.FieldEmail', "Email");
		$labels["RetryCount"] = _t('Newsletter.FieldRetryCount', "Retry Count");
		$labels["RetryCount"] = _t('Newsletter.FieldRetryCount', "Retry Count");
		$labels["ReceivedCount"] = _t('Newsletter.FieldReceivedCount', "Received Count");

		return $labels;
	}

	/** Send the email out to the Recipient */
	public function send($newsletter = null, $recipient = null) {
		
		
		if (empty($newsletter)) $newsletter = $this->Newsletter();
		if (empty($recipient)) $recipient = $this->Recipient();

		//check recipient not blacklisted and verified
		if ($recipient && empty($recipient->Blacklisted) && !empty($recipient->Verified)) {
			
			$email = new NewsletterEmail(
				$newsletter,
				$recipient
			);
			
			
			if (!empty($newsletter->ReplyTo)) $email->addCustomHeader('Reply-To', $newsletter->ReplyTo);


			//HACK JOCHEN. ADDING ATTACHMENTS
			if ( $newsletter->Attachment() ) {
				$attachment = $newsletter->Attachment();
			}

			if ( $attachment ) {
				$file =  $attachment->getFullPath();
				// We check the filesize in bytes in order to see if the file realy exists
				if (file_exists($file) && ($attachment->getAbsoluteSize() > 5000)) {
					$email->attachFile( $file, $file );
				}
			}
			//END ATTACHMENTS
			
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
