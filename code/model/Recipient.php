<?php
/**
 * @package  newsletter
 */

/**
 * Represents newsletter recipient
 */
class Recipient extends DataObject {

	private static $db = array(
		'Email'					=> "Varchar(255)",
		'FirstName'				=> "Varchar(255)",
		'MiddleName'			=> "Varchar(255)",
		'Surname'				=> "Varchar(255)",
		'Salutation'			=> "Varchar(255)",
		'BouncedCount'	    	=> "Int", // if 0, never been bounced
		'Blacklisted'			=> "Boolean",
		
		// everytime, one of its belonged mailing lists is selected when sending the newletter,
		// plus one to the count, if belong to more than one
		// mailing lists that has been selected when sending the newletter, counts as '1'.
		'ReceivedCount'			=> "Int",

		// both subscribe and unsebscribe process need to valid this hash for security
		'ValidateHash'			=> "Varchar(160)",
		'ValidateHashExpired'	=> "SS_Datetime",
		'Verified'				=> "Boolean(1)",
	);

	// a newsletter recipient could belong to many mailing lists.
	private static $belongs_many_many = array(
		'MailingLists'			=> 'MailingList',
	);
	private static $has_many = array(
		'SendRecipientQueue' => 'SendRecipientQueue',
	);

	private static $indexes = array(
		'Email'					=> true,
		'ReceivedCount'			=> true,
	);

	private static $default_sort = '"FirstName", "Surname"';


	public function getCMSActions() {
		$fields = parent::getCMSActions();

		if($this->IsPublished){
			$unPublishButton = FormAction::create('unpublish');
			$unPublishButton->setTitle('Hide');
			$unPublishButton->setDescription("UnPublish this artists");
			$unPublishButton->addExtraClass('ss-ui-action-destructive');
			$unPublishButton->setAttribute('data-icon', 'accept');
			$fields->push($unPublishButton);
		}  else {
			$publishButton = FormAction::create('publish');
			$publishButton->setTitle('Publish');
			$publishButton->setDescription("Publish this artists");
			$publishButton->addExtraClass('ss-ui-action-constructive');
			$publishButton->setAttribute('data-icon', 'accept');
			$fields->push($publishButton);
		}
		return $fields;
	}
			
	/**
	 *
	 * @var array
	 * @todo Generic implementation of $searchable_fields on Recipient object,
	 * with definition for different searching algorithms
	 * (LIKE, FULLTEXT) and default FormFields to construct a searchform.
	 */
	private static $searchable_fields = array(
		'FirstName',
		'MiddleName',
		'Surname',
		'Email',
		'Blacklisted',
		'MailingLists.Title' => array('title' => 'Mailing List'),
		'Verified',
		'Tags.Title' => array('title' => 'Tag (eg. Visitor, Press, etc.)')
	);

	
	private static $summary_fields = array(
		'FirstName'			=> 'First Name',
		'Surname'			=> 'Last Name',
		'Company.CompanyName' => 'Autohaus',
		'Company.CompanyCity' => 'Ort',
		'Company.CompanyAreaCode' => 'Gebiet',
		'Email'				=> 'Email',
		'Blacklisted'		=> 'Blacklisted',
		'BouncedCount'		=> 'Bounced Count',
		'ReceivedCount'		=> 'Count Received',
		'getTagCollection' =>'Tags',
		'Tags.Title' =>'Tag Filter'
	);

	/**
	 * @var array Data used for test emails and previews.
	 */
	public static $test_data = array(
		'FirstName' => 'John',
		'MiddleName' => 'Jack',
		'Surname' => 'Doe',
		'Salutation' => 'Mr.',
		'Email' => 'john@example.org'
	);


	public function validate() {
		$result = parent::validate();
		return $result;
	}

	/**
	 * The unique field used to identify this recipient.
	 * Duplication will not be allowed for this feild.
	 * 
	 * @var string
	 */
	protected static $unique_identifier_field = 'Email';


	
	/**
	 * Check if already subscribed
	 */
	
	public static function RecipientExists($email) {

		$Recipient = Recipient::get()->Filter('Email', $email);
		
		// THE RECIPIENT EXISTS. NOW LET'S CHECK IF THE USER JUST WANTS TO SIGNUP FOR AN ADDITIONAL MAILING LIST
		if ( $Recipient->Count() > 0 ) {
				return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if already in MailingList
	 */
	public static function inMailingList($Recipient, $MailingListID) {

			// THE RECIPIENT EXISTS. NOW LET'S CHECK IF THE USER JUST WANTS TO SIGNUP FOR AN ADDITIONAL MAILING LIST

			// Those are the Mailing Lists the user already has subscribed to
			$CurrentMailingLists = $Recipient->MailingLists();
			foreach ( $CurrentMailingLists as $List ) {
				if ($List->ID == $MailingListID) {
					return true;
				}
			} 
			
			return false;
			
	}

	public function getCMSFields() {
		$fields = new FieldList();
		$fields->push(new TabSet("Root", $mainTab = new Tab("Main")));
		$mainTab->setTitle(_t('SiteTree.TABMAIN', "Main"));

		$fields->addFieldToTab('Root.Main',new TextField('Email',$this->fieldLabel('Email')));

		$fields->addFieldsToTab(
			'Root.Main',
			array(
				Object::create('TextField', 'Salutation',$this->fieldLabel('Salutation')),
				Object::create('TextField', 'FirstName',$this->fieldLabel('First Name')),
				Object::create('TextField', 'MiddleName',$this->fieldLabel('Middle Name')),
				Object::create('TextField', 'Surname',$this->fieldLabel('Surname'))
			)
		);

		if (!empty($this->ID)) {
			$fields->addFieldToTab('Root.Main',
				Object::create('CheckboxSetField', 
					'MailingLists',
					$this->fieldLabel('MailingLists'),
					MailingList::get()->map('ID', 'FullTitle')
				));
		}

		$fields->addFieldsToTab(
			'Root.Main',
			array(
				Object::create('ReadonlyField', 'BouncedCount',$this->fieldLabel('BouncedCount')),
				Object::create('CheckboxField', 'Verified',$this->fieldLabel('Verified'))
					->setDescription(
						_t('Newsletter.VerifiedDesc', 'Has this user verified his subscription?')
					),
				Object::create('CheckboxField', 'Blacklisted',$this->fieldLabel('Blacklisted'))
					->setDescription(
						_t(
							'Newsletter.BlacklistedDesc', 
							'Excluded from emails, either by automated process or manually. '
							. 'An invalid address or undeliverable email will eventually result in blacklisting.'
						)
					),
				Object::create('ReadonlyField', 'ReceivedCount',$this->fieldLabel('ReceivedCount'))
					->setDescription(
						_t(
							'Newsletter.ReceivedCountDesc', 
							'Number of emails sent without undeliverable errors. '
							. 'Only one indication that an email has actually been received and read.'
						)
					)
			)
		);
		
		$map = CustomerTag::get()->map('ID', 'Title');
		
		$tagDropdown = CheckboxSetField::create(
			'Tags', 
			'Tags', 
			$map
		);
		$fields->insertAfter('Surname', $tagDropdown);
		// return $fields;
		
		
		$this->extend('updateCMSFields', $fields);

		return $fields;
	}


	/** Returns the title of this Recipient for the MailingList auto-complete add field. The title includes the
	 * email address, so that users with the same name can be distinguished. */
	public function getTitle() {
		$f = '';
		if (!empty($this->FirstName)) $f = "$this->FirstName ";
		$m = '';
		if (!empty($this->MiddleName)) $m = "$this->MiddleName ";
		$s = '';
		if (!empty($this->Surname)) $s = "$this->Surname ";
		$e = '';
		if (!empty($this->Email)) $e = "($this->Email)";
		return $f.$m.$s.$e;
	}

	public function getHashText(){
		return substr($this->ValidateHash, 0, 10)."******".substr($this->ValidateHash, -10);
	}

	/**
	 * Generate an auto login token which can be used to reset the password,
	 * at the same time hashing it and storing in the database.
	 *
	 * @param int $lifetime The lifetime of the auto login hash in days (by default 2 days)
	 *
	 * @returns string Token that should be passed to the client (but NOT persisted).
	 *
	 * @todo Make it possible to handle database errors such as a "duplicate key" error
	 */
	public function generateValidateHashAndStore($lifetime = 2) {
		do {
			$generator = new RandomGenerator();
			$hash = $generator->randomToken();
		} while(DataObject::get_one('Recipient', "\"ValidateHash\" = '$hash'"));

		$this->ValidateHash = $hash;
		$this->ValidateHashExpired = date('Y-m-d H:i:s', time() + (86400 * $lifetime));

		$this->write();

		return $hash;
	}

	public function onBeforeDelete(){
		parent::onBeforeDelete();

		//SendRecipientQueue
		$queueditems = $this->SendRecipientQueue();
		if($queueditems && $queueditems->exists()){
			foreach($queueditems as $item){
				$item->delete();
			}
		}
		
		//remove this from its belonged mailing lists
		//$mailingLists = $this->MailingLists()->removeAll();
		
		// HEADS UP. WE DONT WANT TO DELETE THE RECIPIENT FROM ALL LISTS HERE. JUST DELETE IT FROM THE CURRENT LIST!
		// $obj = singleton($this->owner->ClassName);
// 		$MailingListID = $obj::$mailing_list_id;
// 		$MailingList = DataObject::get_by_id('MailingList', $MailingListID);
// 		debug::dump($MailingList->ID);
// 		$this->MailingLists()->remove($MailingList);
//
// 		throw new ValidationException("I Will not delete Recipient completely from this context. To do so got to the \"All Recipients Tab\"",0);
// 		return;
		
	}
	
	
	/**
	 * Event handler called before writing to the database. we need to deal with the unique_identifier_field here
	 */
	public function onBeforeWrite() {
		
		parent::onBeforeWrite();
		
		
		// If a recipient with the same "unique identifier" already exists with a different ID, don't allow merging.
		// Note: This does not a full replacement for safeguards in the controller layer (e.g. in a subscription form), 
		// but rather a last line of defense against data inconsistencies.
		
		// BACKEND CONTEXT!!!
		if( is_subclass_of(Controller::curr(), "LeftAndMain") ) {
			
			if (empty($this->Email)) {
				throw new ValidationException('Email is required',0);
			}
			
			// Check if an Email exists but belongs to a different User
			// If so we dont want to add this user and so avoid duplicates
			$ExistingRecipient = Recipient::get()->filter('Email', $this->Email)->First();
			
			// Es gibt schon einen User mit dieser Email
			if (isset($ExistingRecipient)) {
				
				// Wir ändern
				$ExistingRecipientID = $ExistingRecipient->ID;
				
				if($ExistingRecipientID == $this->ID) {
					// Wir machen nix. Der record wird in der Folge aktualisert
					
				} else {
				// Wir versuchen erneut einen User mit der selben Email anzulegen
				// STOP IT
				
				// THIS BREAKS NEWSLETTER SENDING
					//throw new ValidationException('Already exists. please link an existing contact instead',0);
				}
			
			// Wir legen komplett neu an
			} else {
				// Wir legen neu an
			}

		}

	}
	
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		$Recipient = Recipient::get()->Filter('Email', $this->Email);
		
		// Lets add user to the default mailing list
		switch ( $this->owner->ClassName ) {

			case "Subscriber":
			// Subscriber Mailing List
				$MailingListID = 3;
				break;

			case "Exhibitor":
				// Exhibitor Mailing List
				$MailingListID = 2;
				break;

			case "Visitor":
				// Exhibitor Mailing List
				$MailingListID = 1;
				break;
			
			default:
				$MailingListID = false;
		}
	
		if ($MailingListID) {
			//Is already subscribed. maybe just add the user to another list?
			if (self::inMailingList( $Recipient->First(), $MailingListID )) {
				throw new ValidationException($this->Email.' already exists on this mailing list. In case you have made changes to this record; All good. Those have been stored.',0);
			} else {
				// Add the existing user to this new mailing list
				$MailingList = DataObject::get_by_id('MailingList', $MailingListID);
				$MailingList->Recipients()->add($Recipient->First());
				$MailingList->write();
			}
		}
		
		
	}

	public function canDelete($member = null) {
		$can = parent::canDelete($member);
		$queueditems = $this->SendRecipientQueue();
		if($queueditems->count()){
			foreach($queueditems as $queueditem){
				$can = $can && !($queueditem->Status === 'Scheduled' && $queueditem->Status === 'InProgress');
			}
		}
		return $can;
	}

	public function getFrontEndFields($params = null) {
		$fields = parent::getFrontEndFields($params);
		$exludes = array(
			"BouncedCount",
			"Blacklisted",
			"ReceivedCount",
			"ValidateHash",
			"ValidateHashExpired",
			"Verified",
		);

		foreach($exludes as $exclude) {
			$fields->removeByName($exclude);
		}
		return $fields;
	}



}
