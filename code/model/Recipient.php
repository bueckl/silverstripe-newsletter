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

	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['Salutation'] = _t('Newsletter.FieldSalutation', 'Salutation');
		$labels['FirstName'] = _t('Newsletter.FieldFirstName', 'FirstName');
		$labels['Surname'] = _t('Newsletter.FieldSurname', 'Surname');
		$labels['MiddleName'] = _t('Newsletter.FieldMiddleName', 'Middle Name');
		$labels['Mailinglists'] = _t('Newsletter.FieldMailinglists', 'Mailinglists');
		$labels['BouncedCount'] = _t('Newsletter.FieldBouncedCount', 'Bounced Count');
		$labels['Verified'] = _t('Newsletter.FieldVerified', 'Verified?');
		$labels['Blacklisted'] = _t('Newsletter.FieldBlacklisted', 'Blacklisted?');
		$labels['ReceivedCount'] = _t('Newsletter.FieldReceivedCount', 'Received Count');

		return $labels;
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
					throw new ValidationException('Already exists. please link an existing contact instead',0);
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


	// Data Generator http://www.generatedata.com/
	public function requireDefaultRecords() { return;
		
$contacts = array(	

array("ID"=> 1,"FirstName"=>"Cheyenne","Surname"=>"House","Email"=>"luctus.lobortis@felisorciadipiscing.net","ClassName"=>"Exhibitor"), array("ID"=>2,"FirstName"=>"Giacomo","Surname"=>"Burns","Email"=>"Donec@utaliquam.co.uk","ClassName"=>"Contact"), array("ID"=>3,"FirstName"=>"Emily","Surname"=>"Powers","Email"=>"tortor.Integer.aliquam@gravidanunc.org","ClassName"=>"Subscriber"), array("ID"=>4,"FirstName"=>"Amena","Surname"=>"Fitzgerald","Email"=>"facilisis.facilisis@eratSednunc.co.uk","ClassName"=>"Contact"), array("ID"=>5,"FirstName"=>"Brody","Surname"=>"Pickett","Email"=>"velit.egestas@estac.edu","ClassName"=>"Exhibitor"), array("ID"=>6,"FirstName"=>"Candace","Surname"=>"Foreman","Email"=>"malesuada.Integer.id@Nullafacilisis.co.uk","ClassName"=>"Visitor"), array("ID"=>7,"FirstName"=>"Josephine","Surname"=>"Thornton","Email"=>"Integer.id@luctusetultrices.edu","ClassName"=>"Recipient"), array("ID"=>8,"FirstName"=>"Garrison","Surname"=>"White","Email"=>"Phasellus@Suspendissealiquetmolestie.net","ClassName"=>"Recipient"), array("ID"=>9,"FirstName"=>"Brody","Surname"=>"Stuart","Email"=>"nascetur.ridiculus@arcuMorbi.net","ClassName"=>"Visitor"), array("ID"=>10,"FirstName"=>"Evan","Surname"=>"Rocha","Email"=>"litora.torquent@Nunccommodo.co.uk","ClassName"=>"Custom"), array("ID"=>11,"FirstName"=>"Ivy","Surname"=>"Justice","Email"=>"Suspendisse@tellusPhasellus.edu","ClassName"=>"Custom"), array("ID"=>12,"FirstName"=>"Knox","Surname"=>"Morris","Email"=>"egestas.urna.justo@diamvelarcu.co.uk","ClassName"=>"Contact"), array("ID"=>13,"FirstName"=>"Roth","Surname"=>"Livingston","Email"=>"Mauris.quis@Pellentesquehabitant.net","ClassName"=>"Contact"), array("ID"=>14,"FirstName"=>"Hayfa","Surname"=>"Newman","Email"=>"varius@egestas.org","ClassName"=>"Recipient"), array("ID"=>15,"FirstName"=>"Diana","Surname"=>"Kirby","Email"=>"nec.mauris@Sedeunibh.com","ClassName"=>"Exhibitor"), array("ID"=>16,"FirstName"=>"Teegan","Surname"=>"Benton","Email"=>"eget.massa.Suspendisse@neque.com","ClassName"=>"Subscriber"), array("ID"=>17,"FirstName"=>"Simon","Surname"=>"Herrera","Email"=>"vitae.mauris.sit@libero.com","ClassName"=>"Contact"), array("ID"=>18,"FirstName"=>"Reese","Surname"=>"Valencia","Email"=>"metus.In.lorem@mifelis.edu","ClassName"=>"Custom"), array("ID"=>19,"FirstName"=>"Marah","Surname"=>"Bryan","Email"=>"non.lorem@nunc.net","ClassName"=>"Recipient"), array("ID"=>20,"FirstName"=>"Inez","Surname"=>"Mcleod","Email"=>"semper.dui@diamProin.net","ClassName"=>"Exhibitor"), array("ID"=>21,"FirstName"=>"Savannah","Surname"=>"Montgomery","Email"=>"eu@lobortistellusjusto.com","ClassName"=>"Recipient"), array("ID"=>22,"FirstName"=>"Clare","Surname"=>"Mcclure","Email"=>"Cum.sociis.natoque@Donectempus.net","ClassName"=>"Contact"), array("ID"=>23,"FirstName"=>"Abraham","Surname"=>"Moreno","Email"=>"a@dapibusgravidaAliquam.net","ClassName"=>"Contact"), array("ID"=>24,"FirstName"=>"Brendan","Surname"=>"Espinoza","Email"=>"imperdiet.ullamcorper.Duis@utodiovel.com","ClassName"=>"Custom"), array("ID"=>25,"FirstName"=>"Kevin","Surname"=>"Alford","Email"=>"at@Quisqueac.edu","ClassName"=>"Custom"), array("ID"=>26,"FirstName"=>"Baker","Surname"=>"Haney","Email"=>"convallis.convallis@montes.co.uk","ClassName"=>"Visitor"), array("ID"=>27,"FirstName"=>"Myles","Surname"=>"Snider","Email"=>"ultrices.posuere.cubilia@luctussit.net","ClassName"=>"Recipient"), array("ID"=>28,"FirstName"=>"Mannix","Surname"=>"Adams","Email"=>"Sed.dictum@nisi.net","ClassName"=>"Custom"), array("ID"=>29,"FirstName"=>"Amela","Surname"=>"Maxwell","Email"=>"et.tristique.pellentesque@tinciduntorciquis.net","ClassName"=>"Exhibitor"), array("ID"=>30,"FirstName"=>"Xander","Surname"=>"Figueroa","Email"=>"risus.a.ultricies@luctusvulputate.edu","ClassName"=>"Contact"), array("ID"=>31,"FirstName"=>"Indigo","Surname"=>"Mccarty","Email"=>"arcu@dolortempusnon.edu","ClassName"=>"Contact"), array("ID"=>32,"FirstName"=>"Nash","Surname"=>"Haley","Email"=>"lorem@Fuscefermentumfermentum.ca","ClassName"=>"Contact"), array("ID"=>33,"FirstName"=>"Miranda","Surname"=>"Ellis","Email"=>"non.quam.Pellentesque@maurisut.co.uk","ClassName"=>"Exhibitor"), array("ID"=>34,"FirstName"=>"Nevada","Surname"=>"Jimenez","Email"=>"ullamcorper.magna@urnajustofaucibus.com","ClassName"=>"Visitor"), array("ID"=>35,"FirstName"=>"Faith","Surname"=>"Buckner","Email"=>"ligula.Aliquam.erat@vel.org","ClassName"=>"Custom"), array("ID"=>36,"FirstName"=>"Nash","Surname"=>"Perez","Email"=>"Proin@Maecenasmalesuada.ca","ClassName"=>"Exhibitor"), array("ID"=>37,"FirstName"=>"Megan","Surname"=>"Wynn","Email"=>"non@lobortisrisus.edu","ClassName"=>"Exhibitor"), array("ID"=>38,"FirstName"=>"Jordan","Surname"=>"Sutton","Email"=>"In.faucibus.Morbi@sodalespurusin.ca","ClassName"=>"Exhibitor"), array("ID"=>39,"FirstName"=>"Iola","Surname"=>"Garcia","Email"=>"Fusce@sem.edu","ClassName"=>"Visitor"), array("ID"=>40,"FirstName"=>"Reece","Surname"=>"Langley","Email"=>"Maecenas.mi@augueeu.com","ClassName"=>"Subscriber"), array("ID"=>41,"FirstName"=>"Hadley","Surname"=>"Levine","Email"=>"Quisque@Cum.co.uk","ClassName"=>"Recipient"), array("ID"=>42,"FirstName"=>"Aaron","Surname"=>"Mcmahon","Email"=>"diam.vel.arcu@enim.org","ClassName"=>"Subscriber"), array("ID"=>43,"FirstName"=>"Fuller","Surname"=>"Hyde","Email"=>"Suspendisse@non.org","ClassName"=>"Subscriber"), array("ID"=>44,"FirstName"=>"Upton","Surname"=>"Molina","Email"=>"ante@dui.ca","ClassName"=>"Subscriber"), array("ID"=>45,"FirstName"=>"Paloma","Surname"=>"Battle","Email"=>"ipsum.Curabitur.consequat@posuereat.co.uk","ClassName"=>"Contact"), array("ID"=>46,"FirstName"=>"Philip","Surname"=>"Kelly","Email"=>"In.mi.pede@Vivamusnibhdolor.net","ClassName"=>"Contact"), array("ID"=>47,"FirstName"=>"Yuri","Surname"=>"Payne","Email"=>"ullamcorper@justositamet.co.uk","ClassName"=>"Subscriber"), array("ID"=>48,"FirstName"=>"Aspen","Surname"=>"Dominguez","Email"=>"Maecenas.ornare.egestas@egestasligula.org","ClassName"=>"Contact"), array("ID"=>49,"FirstName"=>"Roth","Surname"=>"Noel","Email"=>"eros.Proin.ultrices@enim.ca","ClassName"=>"Exhibitor"), array("ID"=>50,"FirstName"=>"Alfreda","Surname"=>"Adkins","Email"=>"volutpat.Nulla@vestibulumnequesed.ca","ClassName"=>"Contact"), array("ID"=>51,"FirstName"=>"Kelly","Surname"=>"Carson","Email"=>"risus.at.fringilla@eutelluseu.net","ClassName"=>"Subscriber"), array("ID"=>52,"FirstName"=>"Abra","Surname"=>"Rocha","Email"=>"ligula.consectetuer@lectus.org","ClassName"=>"Subscriber"), array("ID"=>53,"FirstName"=>"Deborah","Surname"=>"Kim","Email"=>"orci@litoratorquent.edu","ClassName"=>"Visitor"), array("ID"=>54,"FirstName"=>"Russell","Surname"=>"Ewing","Email"=>"molestie@afelisullamcorper.edu","ClassName"=>"Exhibitor"), array("ID"=>55,"FirstName"=>"Jameson","Surname"=>"Taylor","Email"=>"Phasellus.vitae@elit.org","ClassName"=>"Contact"), array("ID"=>56,"FirstName"=>"Louis","Surname"=>"Rosales","Email"=>"Donec.est@nibh.net","ClassName"=>"Contact"), array("ID"=>57,"FirstName"=>"Bertha","Surname"=>"Cannon","Email"=>"vel.lectus.Cum@Duisvolutpat.edu","ClassName"=>"Exhibitor"), array("ID"=>58,"FirstName"=>"Brynn","Surname"=>"French","Email"=>"eros.non@eleifendCrassed.ca","ClassName"=>"Exhibitor"), array("ID"=>59,"FirstName"=>"Reese","Surname"=>"Finch","Email"=>"id@felisadipiscingfringilla.co.uk","ClassName"=>"Custom"), array("ID"=>60,"FirstName"=>"Guinevere","Surname"=>"Horton","Email"=>"lectus@tellus.org","ClassName"=>"Contact"), array("ID"=>61,"FirstName"=>"Melinda","Surname"=>"Aguilar","Email"=>"Sed.nec.metus@orci.net","ClassName"=>"Exhibitor"), array("ID"=>62,"FirstName"=>"Shay","Surname"=>"Sears","Email"=>"Duis.sit.amet@ipsum.com","ClassName"=>"Exhibitor"), array("ID"=>63,"FirstName"=>"Virginia","Surname"=>"Madden","Email"=>"odio@inlobortis.edu","ClassName"=>"Custom"), array("ID"=>64,"FirstName"=>"Maggy","Surname"=>"Johnson","Email"=>"tempus@quamelementum.net","ClassName"=>"Custom"), array("ID"=>65,"FirstName"=>"Adam","Surname"=>"Burris","Email"=>"tellus.sem.mollis@egetmollis.ca","ClassName"=>"Contact"), array("ID"=>66,"FirstName"=>"Harding","Surname"=>"Henderson","Email"=>"nulla.In@ornarelectusjusto.com","ClassName"=>"Subscriber"), array("ID"=>67,"FirstName"=>"Ori","Surname"=>"Shaffer","Email"=>"dictum.magna@neceleifend.org","ClassName"=>"Contact"), array("ID"=>68,"FirstName"=>"Logan","Surname"=>"Mcmillan","Email"=>"egestas@nunc.ca","ClassName"=>"Visitor"), array("ID"=>69,"FirstName"=>"Iliana","Surname"=>"Bryan","Email"=>"vitae.erat@turpisegestas.org","ClassName"=>"Subscriber"), array("ID"=>70,"FirstName"=>"Hadley","Surname"=>"Heath","Email"=>"accumsan.sed@faucibus.net","ClassName"=>"Recipient"), array("ID"=>71,"FirstName"=>"Yeo","Surname"=>"Dejesus","Email"=>"enim.condimentum.eget@necdiamDuis.net","ClassName"=>"Exhibitor"), array("ID"=>72,"FirstName"=>"Martin","Surname"=>"Frederick","Email"=>"Donec.consectetuer@Nunc.org","ClassName"=>"Visitor"), array("ID"=>73,"FirstName"=>"Kay","Surname"=>"Riddle","Email"=>"ante@estmollisnon.org","ClassName"=>"Recipient"), array("ID"=>74,"FirstName"=>"Brenden","Surname"=>"Hurley","Email"=>"ut@vitaesodales.com","ClassName"=>"Exhibitor"), array("ID"=>75,"FirstName"=>"Pearl","Surname"=>"Noble","Email"=>"Phasellus.dolor@orcilobortis.edu","ClassName"=>"Contact"), array("ID"=>76,"FirstName"=>"Francesca","Surname"=>"Bennett","Email"=>"Proin.dolor.Nulla@pedenonummy.com","ClassName"=>"Subscriber"), array("ID"=>77,"FirstName"=>"Amanda","Surname"=>"Dennis","Email"=>"Mauris.ut@dignissim.com","ClassName"=>"Visitor"), array("ID"=>78,"FirstName"=>"Carly","Surname"=>"Kirkland","Email"=>"Vivamus.nisi@Quisquevarius.net","ClassName"=>"Exhibitor"), array("ID"=>79,"FirstName"=>"Warren","Surname"=>"Williams","Email"=>"euismod@sapienmolestieorci.co.uk","ClassName"=>"Custom"), array("ID"=>80,"FirstName"=>"Grant","Surname"=>"Dejesus","Email"=>"aliquam.arcu.Aliquam@convalliserat.com","ClassName"=>"Exhibitor"), array("ID"=>81,"FirstName"=>"Shafira","Surname"=>"Schroeder","Email"=>"varius@In.net","ClassName"=>"Contact"), array("ID"=>82,"FirstName"=>"Ian","Surname"=>"Gibbs","Email"=>"Proin.nisl.sem@libero.edu","ClassName"=>"Contact"), array("ID"=>83,"FirstName"=>"Kaye","Surname"=>"Pickett","Email"=>"justo@Phasellus.org","ClassName"=>"Visitor"), array("ID"=>84,"FirstName"=>"Halla","Surname"=>"Fry","Email"=>"lobortis.tellus@Aliquamadipiscing.ca","ClassName"=>"Custom"), array("ID"=>85,"FirstName"=>"Quyn","Surname"=>"Stokes","Email"=>"velit.eu@euerosNam.org","ClassName"=>"Subscriber"), array("ID"=>86,"FirstName"=>"Nora","Surname"=>"Rich","Email"=>"tristique@afacilisis.co.uk","ClassName"=>"Subscriber"), array("ID"=>87,"FirstName"=>"Chava","Surname"=>"Valencia","Email"=>"nisl@ultricessitamet.com","ClassName"=>"Visitor"), array("ID"=>88,"FirstName"=>"Harlan","Surname"=>"Horne","Email"=>"metus@enimconsequatpurus.co.uk","ClassName"=>"Custom"), array("ID"=>89,"FirstName"=>"Xaviera","Surname"=>"Obrien","Email"=>"convallis@at.com","ClassName"=>"Exhibitor"), array("ID"=>90,"FirstName"=>"Steel","Surname"=>"Hodge","Email"=>"nisi.a@sodaleselit.ca","ClassName"=>"Custom"), array("ID"=>91,"FirstName"=>"Emerald","Surname"=>"Wheeler","Email"=>"lobortis.mauris@nequeInornare.co.uk","ClassName"=>"Subscriber"), array("ID"=>92,"FirstName"=>"Shoshana","Surname"=>"Henson","Email"=>"interdum.ligula.eu@quam.edu","ClassName"=>"Custom"), array("ID"=>93,"FirstName"=>"Noble","Surname"=>"Schneider","Email"=>"Quisque.nonummy.ipsum@tempor.edu","ClassName"=>"Subscriber"), array("ID"=>94,"FirstName"=>"Ivy","Surname"=>"Ferguson","Email"=>"ullamcorper.Duis@pellentesquetellussem.ca","ClassName"=>"Subscriber"), array("ID"=>95,"FirstName"=>"Dale","Surname"=>"Barry","Email"=>"egestas@fringilla.net","ClassName"=>"Custom"), array("ID"=>96,"FirstName"=>"Natalie","Surname"=>"Boone","Email"=>"Integer@nec.co.uk","ClassName"=>"Subscriber"), array("ID"=>97,"FirstName"=>"Shelly","Surname"=>"Hawkins","Email"=>"penatibus@quis.co.uk","ClassName"=>"Visitor"), array("ID"=>98,"FirstName"=>"Adam","Surname"=>"Schneider","Email"=>"gravida.mauris@enimcondimentumeget.com","ClassName"=>"Custom"), array("ID"=>99,"FirstName"=>"Hayfa","Surname"=>"Watts","Email"=>"varius.ultrices.mauris@nondapibus.edu","ClassName"=>"Visitor"), array("ID"=>100,"FirstName"=>"Raja","Surname"=>"Hess","Email"=>"elit@elementumategestas.org","ClassName"=>"Subscriber") 

); 
		
	foreach ( $contacts as $Contact) {
		
		$Recipient =  Recipient::create();
		$Recipient->ID = $Contact['ID'];
		$Recipient->FirstName = $Contact['FirstName'];
		$Recipient->Surname = $Contact['Surname'];
		$Recipient->Email = $Contact['Email'];
		$Recipient->write();
	}
	
	
	}
	
}


