<?php
/**
 * @package  newsletter
 */

/**
 * Represents a specific containner of newsletter recipients
 */
class MailingList extends DataObject {

    /* the database fields */
    private static $db = array(
        'Title' => "Varchar",
        'FiltersApplied' => 'Text'
    );

    /* a mailing list could contains many newsletter recipients */
    private static $many_many = array(
        'Members' => "Member"
    );

    private static $belongs_many_many = array(
        'Newsletters' => "Newsletter",
    );

    private static $singular_name = 'Mailing-Liste';

    private static $plural_name = 'Mailing-Listen';

    private static $default_sort = 'ID DESC';

    private static $summary_fields = array(
        'Title',
        'ActiveRecipients.Count',
    );

    private static $searchable_fields = array(
        'Title'
    );


    private static $filter_classes = [];

    public function fieldLabels($includelrelations = true) {
        $labels = parent::fieldLabels($includelrelations);
        $labels["Title"] = _t('Newsletter.FieldTitle', "Title");
        $labels["FullTitle"] = _t('Newsletter.FieldTitle', "Title");
        $labels["ActiveRecipients.Count"] = _t('Newsletter.Recipients', "Recipients");
        return $labels;

    }


    function getCMSFields() {
        $fields = new FieldList();

        $fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
        $mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));

        $fields->addFieldToTab('Root.Main',
            $TitleField = new TextField('Title',_t('NewsletterAdmin.MailingListTitle','Mailing List Title'))
        );

        $FilterableFields = self::get_filterable_fields_or_callbacks();

        $fields->addFieldsToTab('Root.Main', [
            HeaderField::create('FiltersHeader', 'Filters'),
            LiteralField::create(
                'FiltersDesc',
                '
                <em>
                    The mailing list will include all members that fit filters defined here. <br> 
                    Additional members can be added manually.
                </em>
                <br>
                <br>
                '
            )
        ]);

        $fields->addFieldsToTab('Root.Main', $FilterableFields);

        $fields->addFieldsToTab('Root.Main', LiteralField::create(
            'MemberCount',
            'Applicable members: ' . $this->FilteredRecipients()->count()
        ));


        $FiltersApplied = unserialize($this->FiltersApplied);

        if ($FiltersApplied) foreach ( $FiltersApplied as $key => $filter ) {
            $field = $FilterableFields->dataFieldByName($key);
            if ($field) $field->setValue($filter);
        }


        // Populate Data
        $grid = new GridField(
            'Members',
            _t('NewsletterAdmin.Recipients', 'Additional recipients for this mailing list'),
            $this->Members(),
            $config = GridFieldConfig::create()
                ->addComponent(new GridFieldButtonRow('before'))
                ->addComponent(new GridFieldToolbarHeader())
                ->addComponent(new GridFieldFilterHeader())
                ->addComponent(new GridFieldSortableHeader())
                ->addComponent(new GridFieldEditableColumns())
                ->addComponent(new GridFieldDeleteAction())
                ->addComponent(new GridFieldRecipientUnlinkAction())
                ->addComponent(new GridFieldEditButton())
                ->addComponent(new GridFieldDetailForm())
                ->addComponent(new GridFieldPaginator(100))
                ->addComponent( $autocomplete = new GridFieldAddExistingAutocompleter('toolbar-header-right'))
                ->addComponent(new GridFieldAddNewButton('before'))
        );

        $autocomplete->setSearchList(Member::get());
        $autocomplete->setSearchFields(array(
            'FirstName',
            'Surname',
            'Email'
        ));

        $config->removeComponentsByType('GridFieldAddNewButton');

        // Nicht zwingend ein Eventteilnehmer. Kann irgendein Recipient sein
        $config->addComponent($auto = new GridFieldAddExistingSearchButton());
        $auto->setTitle(_t('Newsletter.AssignExistingRecipient', "Assign Recipient to Mailing List"));

        $fields->addFieldToTab('Root.Additional recipients',new CompositeField($grid));

        $this->extend("updateCMSFields", $fields);

        if(!$this->ID)
            $fields->removeByName('Recipients');

        return $fields;
    }


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
     *              'Callback' => function($members, $restraint) {
     *                  return $members->filter('Country', $restraint);
     *              }
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

        foreach (Config::inst()->get('MailingList', 'filter_classes') as $fc) {
            $filters = singleton($fc)->mailinglistFilters();
            if ( $filters && count($filters > 0) ) {
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
        //return $this->Members()->exclude('Blacklisted', 1)->exclude('Verified', 0);
        return $this->Members();
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

    }
}
