<?php
/**
 * Newsletter Template Block
 *
 * An example of a Shortcodable DataObject.
 * See https://github.com/sheadawson/silverstripe-shortcodable
 **/
namespace Newsletter\Model;

use SilverStripe\ORM\DataObject;
use Brandday\Model\Event;

class NewsletterTemplateBlock extends DataObject
{
    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    ];

    private static $singular_name = 'Textbaustein';
    private static $plural_name = 'Textbausteine';

    public static $belongs_many_many = [
        'Events' => Event::class
    ];


	public function getEventCollection(){
		$Events = $this->Events();

		$arrTags = array();;

		if($Events->count()){

			foreach ($Events as $Event) {
				$arrTags[] = $Event->getComposedTitle();
			}
		}

		return implode(',', $arrTags);

    }


    public function getCMSFields() {

        $fields = parent::getCMSFields();
        $TabSet = $fields->findOrMakeTab('Root');

        // We dont want to show the related Events here. Gets a bit to confusing for the user
        $TabSet->removeByName('Events');

        // We don't need to show a tab title cause there's only one tab
        $Main = $TabSet->fieldByName('Main')->setTitle('');





        // $MainFields = $Main->Fields();
        // $MainFields->makeFieldReadonly('Log');
        // $MainFields->makeFieldReadonly('SessionID');
        // $MainFields->makeFieldReadonly('EventID');
        // $MainFields->makeFieldReadonly('RecipientID');


        // $fields->addFieldsToTab('Root.Bestätigungsmail', array(
        //         new LiteralField('Data View', $this->getData() ),
        // ));
            return $fields;
    }

    /**
     * Parse the shortcode and render as a string, probably with a template
     * @param array $attributes the list of attributes of the shortcode
     * @param string $content the shortcode content
     * @param ShortcodeParser $parser the ShortcodeParser instance
     * @param string $shortcode the raw shortcode being parsed
     * @return String
     **/
    public function parse_shortcode($attributes, $content, $parser, $shortcode)
    {
        // // check the gallery exists
        // if (isset($attributes['id']) && $gallery = ImageGallery::get()->byID($attributes['id'])) {
        //     // collect custom attributes from the shortcode popup form
        //     $data = array();
        //     if (isset($attributes['Style'])) {
        //         $data['Style'] = $attributes['Style'];
        //     }

        //     // render with template
        //     return $gallery->customise($data)->renderWith('ImageGallery');
        // }

        return $this->Content;
    }

    /**
     * Returns a list of fields for editing the shortcode's attributes
     * in the insert shortcode popup window
     *
     * @return Fieldlist
     **/
    // public function getShortcodeFields()
    // {
    //     return FieldList::create(
    //         DropdownField::create(
    //             'Style',
    //             'Gallery Style',
    //             array('Carousel' => 'Carousel', 'Lightbox' => 'Lightbox')
    //         )
    //     );
    // }

    /**
     * Returns a link to an image to be displayed as a placeholder in the editor
     * In this example we make easy work of this task by using the placehold.it service
     * But you could also return a link to an image in the filesystem - perharps the first
     * image in this ImageGallery
     * a placeholder
     *
     * @param array $attributes the list of attributes of the shortcode
     * @return String
     **/
    public function getShortcodePlaceHolder($attributes)
    {

        return $this->Content;
        $text = $this->Title;
        if (isset($attributes['Style'])) {
            $text .= ' (' . $attributes['Style'] . ')';
        }

        $params = array(
            'txt' => $text,
            'w' => '400',
            'h' => '200',
            'txtsize' => '27',
            'bg' => '000000',
            'txtclr' => 'cccccc'
        );

        return 'https://placeholdit.imgix.net/~text?' . http_build_query($params);

    }

    /**
     * If you would like to customise or filter the list of available shortcodable
     * DataObject records available in the dropdown, you can supply a custom
     * getShortcodableRecords method on your shortcodable DataObject. The method should
     * return an associative array suitable for the DropdownField.
     *
     * @return array
     */
    // public function getShortcodableRecords() {
	   //  return ImageGallery::get()->filter('SomeField', 'SomeValue')->map()->toArray();
    // }
}
