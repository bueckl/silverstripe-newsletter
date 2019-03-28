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
        "RetryCount" => "Int",    //number of times this email got "stuck" in the queue
        "isDuplicate" => 'Boolean', // Is a duplicate of another Mailing
        "ParentID" => "Int"
    );

    private static $has_one = array(
        "Newsletter" => "Newsletter",
        "Member" => "Member"
    );

    private static $summary_fields = array(
        "ID",
        "NewsletterID",
        "ParentID",
        "Status",
        "Member.FirstName",
        "Member.Surname",
        "Member.Email",
        "RetryCount",
        "ReceivedCount",
        "niceIsDuplicate" => "niceIsDuplicate",
        "Newsletter.Subject",
        "LastEdited" => "LastEdited.Nice"
    );


    private static $default_sort = array(
        // 'LastEdited DESC'
        'ID'
    );

    public function niceIsDuplicate() {
        return ( $this->isDuplicate ) ? 'JA' : '-';
    }
    public function ReceivedCount() {
        return $this->Member()->ReceivedCount;
    }


    public function fieldLabels($includelrelations = true) {

        $labels = parent::fieldLabels($includelrelations);
        $labels["Status"] = _t('Newsletter.FieldStatus', "Status");
        $labels["Member.FirstName"] = _t('Newsletter.FieldFirstName', "Vorname");
        $labels["Member.Surname"] = _t('Newsletter.FieldSurname', "Nachname");
        $labels["Member.Email"] = _t('Newsletter.FieldEmail', "Email");
        $labels["RetryCount"] = _t('Newsletter.FieldRetryCount', "Retry Count");
        $labels["RetryCount"] = _t('Newsletter.FieldRetryCount', "Retry Count");
        $labels["ReceivedCount"] = _t('Newsletter.FieldReceivedCount', "Received Count");
        $labels["LastEdited"] = _t('Newsletter.Timestamp', "Zeitstempel");
        $labels["niceIsDuplicate"] = _t('Newsletter.Duplicate', "Duplikat");
        return $labels;
    }

    /** Send the email out to the Recipient */
    public function send($newsletter = null, $recipient = null) {

        if (empty($newsletter)) $newsletter = $this->Newsletter();
        if (empty($recipient)) $recipient = $this->Member();

        //check recipient not blacklisted and verified
        // if ($recipient && empty($recipient->Blacklisted) && !empty($recipient->Verified)) {

            $email = new NewsletterEmail(
                $newsletter,
                $recipient
            );

            if (!empty($newsletter->ReplyTo)) $email->addCustomHeader('Reply-To', $newsletter->ReplyTo);


            $attachment = $newsletter->Attachment1();

            if ( $attachment ) {

                  $file =  $attachment->getFullPath();
                  // We check the filesize in bytes in order to see if the file realy exists
                  if (file_exists($file) && ($attachment->getAbsoluteSize() > 5000)) {
                      $email->attachFile( $file, $file );
                  }

            }


            $attachment = $newsletter->Attachment2();

            if ( $attachment ) {

                  $file =  $attachment->getFullPath();
                  // We check the filesize in bytes in order to see if the file realy exists
                  if (file_exists($file) && ($attachment->getAbsoluteSize() > 5000)) {
                      $email->attachFile( $file, $file );
                  }

            }


            $attachment = $newsletter->Attachment3();

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

        // } else {
        //
        //     $this->Status = 'BlackListed';
        //
        // }

        $this->write();
    }
}
