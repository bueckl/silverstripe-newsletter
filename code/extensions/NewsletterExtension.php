<?php
/**
 * TODO add to Newsletter
 * @package  newsletter
 */

class NewsletterExtension extends DataExtension {

    private static $db = array(
        "Headline" => "Text",
        "Subline" => "Text",
        "ExcludeParams" => "Varchar(255)"
    );

	private static $has_one = array(
        "Attachment" => "File"
    );

	public static $many_many = array(
	);


	public function updateCMSFields(FieldList $fields) {
        return $fields;
        /* Reorder all fields */
        $SubjectField = $fields->dataFieldByName('Subject');
        $SubjectField->setDescription('Mandatory!');
        //$fields->removeByName('Subject');

        //Cant get Literalfield to show up. WTF?
        // $HintField =  LiteralField::create('Hint', 'Mit Erneut versenden (an nachtr&auml;glich hinzugef&uuml;gte Teilnehmer) schicken wir Emails an solche Teilnehmer, die zum Versandzeitpunkt eines Newsletters noch nicht auf der Mailingliste waren. Dies ist z.B. beim erneuten Versand einer Einladungsmail oder Infomail hilfreich. *** Eine Ausnahme bilden Reminder Mails. Hier kann diese Funktion nicht eingesetzt werden. F&uuml;r diesen Fall bitte einfach die bereits versandte Einladungs-/Infomail duplizieren und die Sonderkriterien zum Filtern von Teilnehmern nutzen.');
//         $fields->insertAfter('Headline', $HintField);

        // $MailingListsField = $fields->dataFieldByName('MailingLists');
        // $MailingListsField->setTitle( _t('Newsletter.SendTo', "Send To") );
        // $MailingListsField->setDescription('Who will receive the Newsletter? Choose at least one list');

        $RenderTemplateField = $fields->dataFieldByName('RenderTemplate');
        $RenderTemplateField->setTitle('Template');

        $HeadlineField = $fields->dataFieldByName('Headline');
        $HeadlineField->setDescription('Not shown if left empty');

        $HeroImageField = $fields->dataFieldByName('HeroImage');
        $HeroImageField->setDescription('Not shown if left empty');

        $HeroImageField = $fields->dataFieldByName('HeroImage');
        $HeroImageField->setDescription('Not shown if left empty');

        $AttachmentField = $fields->dataFieldByName('Attachment');
        $AttachmentField->setDescription('Leave empty for no Attachments');

        $SublineField = $fields->dataFieldByName('Subline');
        $SublineField->setDescription('Not shown if left empty');

        // See enqueue() method on NewsletterSendController!

        $ExcludeParamsFields = new CheckboxSetField(
            $name = "ExcludeParams",
            "Rules",
            $source = array(
                "WillAssist"  => "User ausschließen, die NICHT teilnehmen (WillAssist == NO)",
                "NDAAccepted" => "User ausschließen, die die Anmeldung bereits abgechlossen haben (NDAAccepted == true)",
                "NotReceivedYet" => "Nur an solche User senden, welche diese Mail noch nicht erhalten haben",
            )
        );

        $fields->insertAfter('SelectAll', $ExcludeParamsFields );

        $field_order = array('Status','Subject','RenderTemplate','MailingLists','SelectAll','ExcludeParams','Headline', 'Subline');

        $mainTab = $fields->fieldByName('Root.Main');
        $mainFields = $mainTab->Fields();

        $mainFields->changeFieldOrder($field_order); //Call to API

	}
}
