<?php

namespace Newsletter\Model;

use GridFieldAjaxRefresh\GridFieldAjaxRefresh;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\Security\Member;

/**
 * MailingList.
 * Represents a specific container of newsletter recipients
 */
class MailingList extends DataObject {

    private static $table_name = 'MailingList';

    private static $db = [
        'Title' => "Varchar(255)",
        'FiltersApplied' => 'Text'
    ];

    /* a mailing list could contains many newsletter recipients */
    private static $many_many = [
        'Members' => Member::class
    ];

    private static $belongs_many_many = [
        'MailingLists' => Newsletter::class,
    ];

    private static $singular_name = 'Mailing-Liste';

    private static $plural_name = 'Mailing-Listen';

    private static $default_sort = 'ID DESC';

    private static $summary_fields = [
        'Title',
        'ActiveRecipients.Count',
    ];

    private static $searchable_fields = [
        'Title'
    ];

    /**
     * @var array
     */
    private static $filter_classes = [];

    public function fieldLabels($includelrelations = true) {
        $labels = parent::fieldLabels($includelrelations);
        $labels["Title"] = _t('Newsletter.FieldTitle', "Title");
        $labels["FullTitle"] = _t('Newsletter.FieldTitle', "Title");
        $labels["ActiveRecipients.Count"] = _t('Newsletter.Recipients', "Recipients");
        $labels["ActiveRecipients.Count"] = _t('Newsletter.Recipients', "Recipients");
        return $labels;
    }


    /**
     * @return FieldList
     */
    function getCMSFields() {
        $fields = new FieldList();

        $fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
        $mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));

        $fields->addFieldToTab('Root.Main',
            $TitleField = new TextField('Title',
                _t('NewsletterAdmin.MailingListTitle','Mailing List Title'))
        );

        // Filterable fields
        $FilterableFields = self::get_filterable_fields_or_callbacks();


        // $FilterableFields->dataFieldByName('Filter_Member_GuestType')->setTitle('Ich bin … / Art des Teilnehmers');
        // $FilterableFields->dataFieldByName('Filter_Member_GuestType')->setDescription('Keine Auswahl für Alle');

        // $FilterableFields->dataFieldByName('Filter_Member_BadgeType')->setTitle('Ausweisart');


        if($FilterableFields->dataFieldByName('Filter_Member_WillAssist')) {
            $FilterableFields->dataFieldByName('Filter_Member_WillAssist')
                ->setTitle('Teilnahme bestätigt')
                ->setDescription('Keine Auswahl für Alle');
        }


        // $FilterableFields->dataFieldByName('Filter_Member_NDAAccepted')->setTitle('Geheimhaltungserklärung akzeptiert');
        // // $FilterableFields->dataFieldByName('Filter_Member_CountryImport')->setTitle('CountryImport');
        // $FilterableFields->dataFieldByName('Filter_Member_PhotoTermsAccepted')->setTitle('Foto Einverständniserklärung akzeptiert');

        // $FilterableFields->dataFieldByName('Filter_Member_Locale')->setEmptyString('Bitte wählen')->setDescription('Keine Auswahl für Alle');

        // $FilterableFields->dataFieldByName('Filter_Member_AttendeeCheck')->setDescription('Ist Teilnehmer');
        // $FilterableFields->dataFieldByName('Filter_Member_CrewCheck')->setDescription('Crew Only');

        // $FilterableFields->dataFieldByName('Filter_Member_PhotoCheck')->setDescription('Hat KEIN Profilbild');

        // $FilterableFields->dataFieldByName('Filter_Member_BadgeType')->setTitle('Ausweisart');


        // $FilterableFields->dataFieldByName('Filter_Member_isPkwWave1')->setTitle('Anreisen PKW, W1');
        // $FilterableFields->dataFieldByName('Filter_Member_isPkwWave2und3')->setTitle('Anreisen PKW, W2 + W3');

        //$FilterableFields->dataFieldByName('Filter_Member_isSchoenefeld')->setTitle('Anreisen Schönefeld');
        //$FilterableFields->dataFieldByName('Filter_Member_isTegel')->setTitle('Anreisen Tegel');
        //$FilterableFields->dataFieldByName('Filter_Member_isTrain')->setTitle('Anreisen Bahnhof');



        $fields->addFieldsToTab('Root.Main', [
            HeaderField::create('FiltersHeader', 'Criteria / Filter'),
            LiteralField::create(
                'FiltersDesc',
                '
                <div class="message notice">
                    We create a Mailing/Recipient List based on the following choices.<br>
                </div>
                <br>
                <br>
                '
            )
        ]);

        $fields->addFieldsToTab('Root.Main', $FilterableFields);
        
        $FiltersApplied = unserialize($this->FiltersApplied);
        
        if ($FiltersApplied) foreach ( $FiltersApplied as $key => $filter ) {
        
            $field = $FilterableFields->dataFieldByName($key);
            if ($field) $field->setValue($filter);
        }

        

        // Filtered recipients
        $filteredRecipients = $this->FilteredRecipients();

        $grid = new GridField(
            'FilteredRecipients',
            'Filtered Recipients',
            $filteredRecipients
        );

        // Important to set ModelClass otherwise Grid moans
        $grid->setModelClass(Member::class);
        $grid->getConfig()->removeComponentsByType(GridFieldAjaxRefresh::class);

        // Heads up !!!! $filteredRecipients ist not calculated from the actual database, here !!!
        // This is potentially dangerous. Reinvestigate …

        $fields->addFieldsToTab(
            'Root.Recipients matching the seletected criteria (' . $filteredRecipients->count() . ')', [
            // LiteralField::create('FilteredRecipientsDesc','
//                 <em>
//                     These recipients are filtered based on the filtering options. <br>
//                     Make sure to save for these to refresh.
//                 </em>
//             '),
            new CompositeField($grid)
        ]);


        // Additional manual added recipients -> removed to keep things lean. In order to add "special" users to a list
        // we should create a filter … Like e.g a texfield "FreeMailingListFilter". We could than put a "secret" word into
        // that field and add a "FreeMailingListFilter" to MailingList

        $fields->dataFieldByName('Title')->setTitle('Mailing list name');
        $this->extend("updateCMSFields", $fields);

        if(!$this->ID)
            $fields->removeByName('Recipients');

        return $fields;
    }


    /**
     * @return string
     */
    public function getFullTitle() {
        return sprintf(
            '%s (%s)',
            $this->Title,
            _t(
                'Newsletter.NumberRecipients',
                '{count} recipients',
                array('count' => $this->ActiveRecipients()->Count())
            )
        );
    }

    /**
     * All filterable fields
     * Fields are configured as the method "mailinglistFilters" in any class that interacts with Member
     * Additionally these have to be added as a configuration, e.g. like this:
     *
     * MailingList:
     * filter_classes:
     *   - Member
     *   - Hotel
     *
     * Example of the mailinglistFilters method
     *
     * public function mailinglistFilters()
     * {
     *     return [
     *          'Country' => [
     *
     *          ],
     *          'Name' => [
     *              'Field' => DropdownField::create('Name')
     *                  ->setSource(Hotel::get()->map())
     *                  ->setEmptyString('Please select hotel'),
     *              'Callback' => function($members, $restraint) {
     *                  $hotelID = $restraint;
     *                  return $members->filter('HotelID', $hotelID);
     *              }
     *          ]
     *     ];
     * }
     *
     * @param bool $callBacks whether to return
     * @return array|FieldList
     */
    public static function get_filterable_fields_or_callbacks($callBacks = false) {

        $FilterableFields = new FieldList();
        $callBacksArr = [];

        $filterClasses = Config::inst()->get(MailingList::class, 'filter_classes');


        asort($filterClasses);

        foreach ($filterClasses as $fc) {

            $filters = singleton($fc)->mailinglistFilters();


            if ( $filters && count($filters)  > 0 ) {

                $FilterableFields->add(HeaderField::create('Filters' . $fc .'Header', $fc, 3));

                foreach ( $filters as $key => $filterable ) {
                    $field = null;
                    if (isset($filterable['Field'])) {
                        $field = $filterable['Field'];
                    } else {
                        $field = singleton($fc)->getCMSFields()->dataFieldByName($key);
                    }
                    $fieldName = 'Filter_' . $fc . '_' . $field->getName();

                    if ($callBacks) {
                        if (isset($filterable['Callback'])) {
                            $callBacksArr[$fieldName] = $filterable['Callback'];
                        }
                    } else {
                        $field->setName($fieldName);
                        $FilterableFields->add($field);
                    }
                }
            }
        }
        if ($callBacks) {
            return $callBacksArr;
        } else {
            return $FilterableFields;
        }
    }

    /**
     * All recipients defined by the set filter
     * @return DataList
     */
    public function FilteredRecipients() {

        $filtersApplied = unserialize($this->FiltersApplied);
        $members = Member::get();

        $members = $members->exclude('Blacklisted', 1);



        foreach (self::get_filterable_fields_or_callbacks(true) as $fieldName => $callBack) {
            $restraint = isset($filtersApplied[$fieldName]) ? $filtersApplied[$fieldName] : false;
            if ($restraint) {
                $members = $callBack($members, $restraint);
            }
        }


        return $members;
    }

    /**
     * Returns all recipients who aren't blacklisted, and are verified.
     */
    public function ActiveRecipients() {
        if($this->Members()  instanceof UnsavedRelationList ) {
            return new ArrayList();
        }
        $Members = $this->Members();
        $Members = $Members->exclude('Blacklisted', 1);
        //return $this->Members()->exclude('Blacklisted', 1)->exclude('Verified', 0);
        return $Members;
    }

    public function updateRecipientsforMailingList() {

        $Recipients = $this->FilteredRecipients();

        // First delete all Member for this Mailing List
        $this->Members()->removeAll();

        // Now add the Members which fit the filter settings
        foreach ($Recipients as $Recipient) {
            if ($Recipient) {
                $this->Members()->add($Recipient);
            }
        }

    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();

        // Saving selected filters
        $data = $this->record;
        $keys = array_keys($data);

        // Find all Fields having a "Filter_" prefix
        $filterArray = preg_grep('/Filter_/', $keys);

        $filterKeyValue = array();
        foreach($filterArray as $key => $val) {
            // ignore empty values
            if ( $this->$val && !empty($this->$val) ) {
                $filterKeyValue[$val] = $this->$val;
            }
        }
        $this->FiltersApplied = serialize($filterKeyValue);

        $this->updateRecipientsforMailingList();

    }

}
