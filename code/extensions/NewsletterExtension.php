<?php
/**
 * TODO add to Newsletter
 * @package  newsletter
 */

class NewsletterExtension extends DataExtension {

    private static $db = array(
        "Headline" => "Text",
        "Subline" => "Text"
    );

	private static $has_one = array(
        "Attachment" => "File"
    );

	public static $many_many = array(
	);


	public function updateCMSFields(FieldList $fields) {
        return $fields;
        /* Reorder all fields */
        // $SubjectField = $fields->dataFieldByName('Subject');
//         $SubjectField->setDescription('Mandatory!');
//
//         $RenderTemplateField = $fields->dataFieldByName('RenderTemplate');
//         $RenderTemplateField->setTitle('Template');
//
//         // $HeadlineField = $fields->dataFieldByName('Headline');
//         // $HeadlineField->setDescription('Not shown if left empty');
//
//         // $HeroImageField = $fields->dataFieldByName('HeroImage');
//         // $HeroImageField->setDescription('Not shown if left empty');
//
//         // $HeroImageField = $fields->dataFieldByName('HeroImage');
//         // $HeroImageField->setDescription('Not shown if left empty');
//
//         $AttachmentField = $fields->dataFieldByName('Attachment');
//         $AttachmentField->setDescription('Leave empty for no Attachments');
//
//         // $SublineField = $fields->dataFieldByName('Subline');
//         // $SublineField->setDescription('Not shown if left empty');
//
//         $fields->removeByName('Headline');
//         $fields->removeByName('Subline');
//
//
//
//         // $field_order = array('Hint1','Hint','Status','Subject','RenderTemplate','MailingLists','NewAddedOnly');
//
//         $mainTab = $fields->fieldByName('Root.Main');
//         $mainFields = $mainTab->Fields();

        // $mainFields->changeFieldOrder($field_order); //Call to API

	}
}
