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
        'Recipients' => "Recipient",
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

        /*
         We construct a UI to be able to select the filters we want to apply when creating a MailingList
         FilterableFields are set using the mailinglistFilterableFields() method, to be set on the DataObject
         FilterableFields of relations should be set on the Main DataObject, which is "Member" / respectivley its extension
         This is not very clean, but this is rather custom stuff anyway …
        */

        $FilterableFields = new FieldList();

        if ( singleton('Member')->mailinglistFilterableFields() && count(singleton('Member')->mailinglistFilterableFields()>0) ) {

            $FilterableFieldsArray = singleton('Member')->mailinglistFilterableFields();

            foreach ( $FilterableFieldsArray as $Filterable ) {
                $FilterableField = singleton('Member')->getCMSFields()->dataFieldByName($Filterable);
                $FilterableField->setName( 'Filter_' . $FilterableField->getName());
                $FilterableFields->add( $FilterableField );
            }

        }

        if ( singleton('Hotel')->mailinglistFilterableFields() && count(singleton('Hotel')->mailinglistFilterableFields() > 0) ) {

            $FilterableFieldsArrayHotel = singleton('Hotel')->mailinglistFilterableFields();

            foreach ( $FilterableFieldsArrayHotel as $Filterable ) {
                $FilterableField = singleton('Member')->getCMSFields()->dataFieldByName($Filterable);
                $FilterableField->setName( 'Filter_' . $FilterableField->getName());
                $FilterableFields->add( $FilterableField );
            }

            $FilterableFields = array_merge($FilterableFieldsArray, $FilterableFieldsArrayHotel);

        }

        $fields->addFieldsToTab('Root.MailingListConfig', $FilterableFields);


        // Prepopulate Fields from given data. This won't work on relations. Maybe we need to prefix FilterableFields with the DataObject's name of the relation

        $FiltersApplied = unserialize($this->FiltersApplied);

        foreach ( $FiltersApplied as $key => $filter ) {
            $FilterableFields->dataFieldByName($key)->setValue($filter);
        }


        // Populate Data
        $grid = new GridField(
            'Recipients',
            _t('NewsletterAdmin.Recipients', 'Mailing list recipients'),
            $this->Recipients(),
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

        $autocomplete->setSearchList(Recipient::get());
        $autocomplete->setSearchFields(array(
            'FirstName',
            'Surname',
            'Email',
            'Company.CompanyName'
        ));

        $config->removeComponentsByType('GridFieldAddNewButton');

        // Nicht zwingend ein Eventteilnehmer. Kann irgendein Recipient sein
        $config->addComponent($auto = new GridFieldAddExistingSearchButton());
        $auto->setTitle(_t('Newsletter.AssignExistingRecipient', "Assign Recipient to Mailing List"));

        $fields->addFieldToTab('Root.Main',new CompositeField($grid));
        $fields->findOrMakeTab('Root.Main')->setTitle('');

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
     * Returns all recipients who aren't blacklisted, and are verified.
     */
    public function ActiveRecipients() {
        if($this->Recipients()  instanceof UnsavedRelationList ) {
            return new ArrayList();
        }
        //return $this->Recipients()->exclude('Blacklisted', 1)->exclude('Verified', 0);
        return $this->Recipients();
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
