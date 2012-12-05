<?php

/**
 * Represents newsletter recipient
 * 
 * @package newsletter
 */
class Recipient extends DataObject {
	static $db = array(
		'Email'					=> "Varchar(255)",
		'FirstName'				=> "Varchar(255)",
		'MiddleName'			=> "Varchar(255)",
		'Surname'				=> "Varchar(255)",
		'Salutation'			=> "Varchar(255)",
		'LanguagePreferred' 	=> "Varchar(6)", // the locale code
		'BouncedCount'	    	=> "Int", // if 0, never been bounced
		'Blacklisted'			=> "Boolean",
		// everytime, one of its belonged mailing lists is selected when sending the newletter,
		// plus one to the count, if belong to more than one
		// mailing lists that has been selected when sending the newletter, counts as '1'.
		'ReceivedCount'			=> "Int",

		// both subscribe and unsebscribe process need to valid this hash for security
		'ValidateHash'			=> "Varchar(160)",
		'ValidateHashExpired'	=> "SS_Datetime",
		'Archived'				=> "Boolean",
	);

	// a newsletter recipient could belong to many mailing lists.
	static $belongs_many_many = array(
		'MailingLists'			=> 'MailingList',
	);
	static $has_many = array(
		'SendRecipientQueue' => 'SendRecipientQueue'
	);

	static $indexes = array(
		'Email'					=> true,
		'ReceivedCount'			=> true,
	);

	static $default_sort = '"FirstName", "Surname"';

	/**
	 *
	 * @var array
	 * @todo Generic implementation of $searchable_fields on Recipient object,
	 * with definition for different searching algorithms
	 * (LIKE, FULLTEXT) and default FormFields to construct a searchform.
	 */
	static $searchable_fields = array(
		'FirstName',
		'MiddleName',
		'Surname',
		'Email',
		'Blacklisted',
		'MailingLists.Title'=> 'Mailing List',
	);

	static $summary_fields = array(
		'FirstName'			=> 'First Name',
		'MiddleName'		=> 'Middle Name',
		'Surname'			=> 'Last Name',
		'Email'				=> 'Email',
		'Blacklisted'		=> 'Black listed?',
		'BouncedCount'		=> 'Bounced Count',
		'ReceivedCount'		=> 'Count for Received newsletters'
	);

	/**
	 * The unique field used to identify this recipient.
	 * Duplication will not be allowed for this feild.
	 * 
	 * @var string
	 */
	protected static $unique_identifier_field = 'Email';

	/**
	 * Ensure the LanguagePreferred is set to something sensible by default.
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->LanguagePreferred = i18n::get_locale();
	}

	public function getCMSFields() {
		$fields =parent::getCMSFields();
		$fields->removeByName("ValidateHash");
		$fields->removeByName("ValidateHashExpired");

		if($this && $this->exists()){
			$bouncedCount = $fields->dataFieldByName("BouncedCount")->performDisabledTransformation();
			$receivedCount = $fields->dataFieldByName("ReceivedCount")->performDisabledTransformation();
			$fields->replaceField("BouncedCount", $bouncedCount);
			$fields->replaceField("ReceivedCount", $receivedCount);
		}else{
			$fields->removeByName("BouncedCount");
			$fields->removeByName("ReceivedCount");
		}


		//We will hide LanguagePreferred for now till if demoed for hooking newsletter module to multi-lang support.
		$fields->removeByName("LanguagePreferred");

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
		$this->ValidateHashExpired = date('Y-m-d', time() + (86400 * $lifetime));

		$this->write();

		return $hash;
	}
}