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
		'Title'					=> "Varchar",
	);

	/* a mailing list could contains many newsletter recipients */
	private static $many_many = array(
		'Recipients'			=> "Recipient",
	);

	private static $belongs_many_many = array(
		'Newsletters'			=> "Newsletter",
	);

	private static $singular_name = 'Mailing-Liste';

	private static $plural_name = 'Mailing-Listen';
	
	private static $default_sort = 'ID DESC';
	
	private static $summary_fields = array(
		'Title',
		'ActiveRecipients.Count',
	);
	
	public function requireDefaultRecords() {
		
		//parent::requireDefaultRecords();
		return;
		$defaultLists =  MailingList::create();
		$defaultLists->ID = 1;
		$defaultLists->Title = 'Visitors Mailing List';
		$defaultLists->write();
		
		$defaultLists->write();


		$defaultLists =  MailingList::create();
		$defaultLists->ID = 2;
		$defaultLists->Title = 'Exhibitors Mailing List';
		$defaultLists->write();

		$defaultLists =  MailingList::create();
		$defaultLists->ID = 3;
		$defaultLists->Title = 'Subscriber Mailing List';
		$defaultLists->write();

	}
	
	
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
		$auto->setTitle('Bestehenden Teilnehmer dieser Mailing-Liste zuweisen.');


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
}

