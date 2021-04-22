<?php
namespace Newsletter\Model;

use Newsletter\Email\NewsletterEmail;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

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
    private static $table_name = 'SendRecipientQueue';

    private static $db = [
        "Status" => "Enum('Scheduled, InProgress, Sent, Failed, Bounced, BlackListed', 'Scheduled')",
        "RetryCount" => "Int",    //number of times this email got "stuck" in the queue
        "isDuplicate" => 'Boolean', // Is a duplicate of another Mailing
        "ParentID" => "Int"
    ];

    private static $has_one = [
        "Newsletter" => Newsletter::class,
        "Member" => Member::class
    ];

    private static $summary_fields = [
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
    ];


    private static $default_sort = [
        // 'LastEdited DESC'
        'ID'
    ];

    public function niceIsDuplicate() {
        return $this->isDuplicate;
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
        if (empty($newsletter)) {
            $newsletter = $this->Newsletter();
        }
        if (empty($recipient)) {
            $recipient = $this->Member();
        }

        //check recipient not blacklisted and verified
        // if ($recipient && empty($recipient->Blacklisted) && !empty($recipient->Verified)) {

        $email = new NewsletterEmail(
            $newsletter,
            $recipient,
            false,
            true
        );



        $attachment = $newsletter->Attachment1();

        if ( $attachment->exists() ) {
            $file = ASSETS_PATH . '/' . $attachment->FileFilename;
            // We check the filesize in bytes in order to see if the file realy exists
            if ($attachment->getAbsoluteSize() > 5000) {
                $email->addAttachment($file);
            }

        }

        $attachment = $newsletter->Attachment2();

        if ( $attachment->exists() ) {
            $file = ASSETS_PATH . '/' . $attachment->FileFilename;
            // We check the filesize in bytes in order to see if the file realy exists
            if ($attachment->getAbsoluteSize() > 5000) {
                $email->addAttachment($file);
            }

        }


        $attachment = $newsletter->Attachment3();

        if ( $attachment->exists() ) {
            $file = ASSETS_PATH . '/' . $attachment->FileFilename;
            // We check the filesize in bytes in order to see if the file realy exists
            if ($attachment->getAbsoluteSize() > 5000) {
                $email->addAttachment($file);
            }

        }

        if ( $newsletter->AgendaPDF == true ) {            

            if ( $recipient->getSubEvent() ) {

                $attachment = $recipient->getSubEvent()->AgendaPDF();
            
                if ( $attachment->exists() ) {
                    $file = ASSETS_PATH . '/' . $attachment->FileFilename;
                    // We check the filesize in bytes in order to see if the file realy exists
                    if ($attachment->getAbsoluteSize() > 5000) {
                        $email->addAttachment($file);
                    }
                }    
            }
            
        }


        if ( $newsletter->AgendaPDF_en == true ) {            

            if ( $recipient->getSubEvent() ) {

                $attachment = $recipient->getSubEvent()->AgendaPDF_en();
            
                if ( $attachment->exists() ) {
                    $file = ASSETS_PATH . '/' . $attachment->FileFilename;
                    // We check the filesize in bytes in order to see if the file realy exists
                    if ($attachment->getAbsoluteSize() > 5000) {
                        $email->addAttachment($file);
                    }
                }

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

        $this->write();
    }
}
