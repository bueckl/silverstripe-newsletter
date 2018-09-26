<?php
/**
 * @package  newsletter
 */

/**
 * Single newsletter instance.
 */
class Newsletter extends DataObject implements CMSPreviewable{

    private static $db = array(
        "Status" => "Enum('Draft, Sending, Sent', 'Draft')",
        "Subject" => "Varchar(255)",
        "Content" => "HTMLText",
        "SentDate" => "Datetime",
        "SendFrom" => "Varchar(255)",
        "ReplyTo" => "Varchar(255)",
        "RenderTemplate" => "Varchar",
        "ParentID" => "Int",
        "NewAddedOnly" => "Boolean"
    );

    private static $has_many = array(
        "SendRecipientQueue" => "SendRecipientQueue",
        "TrackedLinks" => "Newsletter_TrackedLink"
    );

    private static $many_many = array(
        "MailingLists" => "MailingList"
    );

    private static $singular_name = 'Newsletter';
    private static $plural_name = 'Newsletters';

    private static $searchable_fields = array(
        "Subject",
        "Content",
        "SendFrom",
        "SentDate"
    );

    private static $default_sort = array(
        "LastEdited DESC"
    );

    private static $summary_fields = array(
        "Subject",
        "SentDate",
        "Status"
    );

    static $required_fields = array(
        'Subject', 'SendFrom'
    );

    static $required_relations = array(
        'MailingLists'
    );

    private static $field_labels = array();

    public function fieldLabels($includelrelations = true) {
        $labels = parent::fieldLabels($includelrelations);
        $labels["Subject"] = _t('Newsletter.FieldSubject', "Subject");
        $labels["Status"] = _t('Newsletter.FieldStatus', "Status");
        $labels["SendFrom"] = _t('Newsletter.FieldSendFrom', "From Address");
        $labels["ReplyTo"] = _t('Newsletter.FieldReplyTo', "Reply To Address");
        $labels["Content"] = _t('Newsletter.FieldContent', "Content");
        return $labels;
    }

    public function validate() {

        $result = parent::validate();

        foreach(self::$required_fields as $field) {
            if (empty($this->$field)) {
                $result->error(_t('Newsletter.FieldRequired',
                '"{field}" field is required',
                array('field' => isset(self::$field_labels[$field])?self::$field_labels[$field]:$field)
                ));
            }
        }

        if (!empty($this->ID)) {
            foreach(self::$required_relations as $relation) {
                if ($this->$relation()->Count() == 0) {
                    $result->error(_t('Newsletter.RelationRequired',
                        'Select at least one "{relation}"',
                            array('relation' => $relation)
                    ));
                }
            }
        }

        return $result;
    }

    // Faking parent method
    public function parent() {
        if ($this->ParentID && $this->ParentID > 0) {
            return DataObject::get_by_id('Newsletter', $this->ParentID);
        } else {
            return false;
        }
    }
    /**
     * Returns a FieldSet with which to create the CMS editing form.
     * You can use the extend() method of FieldSet to create customised forms for your other
     * data objects.
     *
     * @param Controller
     * @return FieldSet
     */
    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $fields->removeFieldFromTab('Root', 'MailingLists');
        $fields->removeFieldFromTab('Root', 'Member');
        $fields->removeFieldFromTab('Root.SendRecipientQueue',"SendRecipientQueue");
        $fields->removeByName('SendRecipientQueue');
        $fields->removeByName('TrackedLinks');

        $Important = new LiteralField('Hint1', '<div class="message bad" style="font-size: 17px; margin: 0px 0; background: #cc0000; color: #fff; padding: 15px; 10px;">*** <strong>WICHTIG ***</strong> Vor dem Versand eines Newsletters müssen immer alle bestehenden Mailing-Listen aktualisiert werden. </div>');
        $fields->insertBefore('Status', $Important );


        if ( $this->ParentID ) {

            $OriginalNewsletter = DataObject::get_by_id('Newsletter', $this->ParentID);

            $Dup = new LiteralField('Hint', '<div class="message notice">Dieser Newsletter ist ein Duplikat von <strong>'.$OriginalNewsletter->Subject.'</strong></div>');

            $fields->insertBefore('Status', $Dup );
        }

        $fields->addFieldToTab(
            'Root.Main',
            new ReadonlyField('Status', $this->fieldLabel('Status')),
            'Subject'
        );

        if ($this->Status == "Sent") {
            $fields->addFieldToTab(
                'Root.Main',
                new ReadonlyField('SentDate',$this->fieldLabel('SentDate')),
                'Subject'
            );
        }

        $fields->dataFieldByName('SendFrom')
            ->setValue(Email::getAdminEmail())
            ->setAttribute('placeholder', 'My Name <admin@example.org>');

        $fields->dataFieldByName('ReplyTo')
            ->setValue(Email::getAdminEmail())
            ->setAttribute('placeholder', 'admin@example.org')
            ->setDescription(_t(
                'Newsletter.ReplyToDesc',
                'Any undeliverable emails will be collected in this mailbox'
        ));


        if($this->Status != 'Sent') {
            $contentHelp = '<strong>'
                . _t('Newsletter.FormattingHelp', 'Formatting Help')
                . '</strong><br />';
            $contentHelp .= '<ul>';
            foreach($this->getAvailablePlaceholders() as $title => $description) {
                $contentHelp .= sprintf('<li><em>$%s</em>: %s</li>', $title, $description);
            }
            $contentHelp .= '</ul>';
            $contentField = $fields->dataFieldByName('Content');
            if($contentField) $contentField->setDescription($contentHelp);
        }

        // Only show template selection if there's more than one template set
        $templateSource = $this->templateSource();
        ksort($templateSource);

        
        if(count($templateSource) > 1) {
            $fields->replaceField(
                "RenderTemplate",
                $Drop =new DropdownField("RenderTemplate", _t('NewsletterAdmin.RENDERTEMPLATE',
                    'Template the newsletter render to'),
                $templateSource)
            );

            $Drop->setDisabledItems(array('VW_Simple_Template'));

        } else {
            $fields->replaceField("RenderTemplate",
                new HiddenField('RenderTemplate', false, key($templateSource))
            );
        }


        if($this && $this->exists()){

            $mailinglists = MailingList::get();

            $map = $mailinglists->map('ID', 'Title');
            
            foreach ($map as $key => $m) {
                $map->push($key, $m);
            }
            

            
            $fields->addFieldsToTab("Root.Main", array(
                new CheckboxSetField(
                    "MailingLists",
                    _t('Newsletter.SendTo', "Send To", 'Selects mailing lists from set of checkboxes'),
                    $map
                )
            ));
        }

        $fields->removeByName('NewAddedOnly');

        $fields->insertAfter('MailingLists', FieldGroup::create('Cb',
            $cb = new CheckboxField('NewAddedOnly', 'NUR an Teilnehmer, welche diesen Newsletter noch nicht erhalten haben.')
        ));

        $cb->setDescription('Diese Checkbox setzen wir nur, wenn es sich bei dem Newsletter um ein Duplikat handelt. Man also z.B Die Einladung erneut verschicken möchte – und seit dem letzten Versand neue Teilnehmer hinzugekommen sind!');


        if($this->Status === 'Sending' || $this->Status === 'Sent') {

            $fields->push(new HiddenField("NEWSLETTER_ORIGINAL_ID", "", $this->ID));

            $gridFieldConfig = GridFieldConfig::create()->addComponents(
                new GridFieldNewsletterSummaryHeader(),    //only works on SendRecipientQueue items, not TrackedLinks
                new GridFieldSortableHeader(),
                new GridFieldDataColumns(),
                new GridFieldFilterHeader(),
                new GridFieldPageCount(),
                new GridFieldPaginator(30)
            );

//             if ( !$this->ParentID || !$this->ParentID == 0 ) {
//
//                //Create the Sent To Queue grid
//                if (class_exists("GridFieldAjaxRefresh") && $this->SendRecipientQueue()->exists()) {
//                    //only use auto-refresh if there is a send out currently in-progress, otherwise no-point
//                if ($this->SendRecipientQueue()->filter(
//                    array('Status'=>array('Scheduled','InProgress')))->count() > 0) {
//                    $gridFieldConfig->addComponent(new GridFieldAjaxRefresh(5000,true));
//                }
//
//            }
//        }



        if ( $this->ParentID > 0 ) {

            // We show the SendRecipientQueue based on the Parent ID
            $sendRecipientGrid = GridField::create(
                'SendRecipientQueue',
                // _t('NewsletterAdmin.SentTo', 'Sent to'),
                'Empfänger',
                $this->SendRecipientQueue(),
                $gridFieldConfig
            );

        } else {

            // On the Parent Record we show all recipients of the original AND Duplicated Newsletters …

            $sendRecipientGrid = GridField::create(
                'SendRecipientQueue',
                // _t('NewsletterAdmin.SentTo', 'Sent to'),
                'Empfänger',
                SendRecipientQueue::get()->filterAny(array(
                    'NewsletterID' => $this->ID,
                    'ParentID' => $this->ID
                )),
                $gridFieldConfig
            );

            // $fields->addFieldToTab( 'Root.'._t('NewsletterAdmin.SentTo', 'Sent to'), $sendRecipientGrid );
        }

        $fields->addFieldToTab( 'Root.Empfänger', $sendRecipientGrid );

        //only show restart queue button if the newsletter is stuck in "sending"
        //only show the restart queue button if the user can run the build task (i.e. has full admin permissions)
        if ($this->Status == "Sending" && Permission::check('ADMIN')) {

            $restartLink = Controller::join_links(
                Director::absoluteBaseURL(),
                'dev/tasks/NewsletterSendController?newsletter='.$this->ID
            );

            $fields->addFieldToTab('Root.Restart if stucked …',
                new LiteralField(
                    'RestartQueue',
                    sprintf(
                    '<a href="%s" class="ss-ui-button" data-icon="arrow-circle-double">%s</a>',
                    $restartLink,
                    _t('Newsletter.RestartQueue', 'Restart queue processing')
                    )
                )
            );
        }

        //only show the TrackedLinks tab, if there are tracked links in the newsletter and the status is "Sent"
        if($this->TrackedLinks()->count() > 0) {
            $fields->addFieldToTab('Root.TrackedLinks',GridField::create(
                'TrackedLinks',
                _t('NewsletterAdmin.TrackedLinks', 'Tracked Links'),
                $this->TrackedLinks(),
                $gridFieldConfig
                )
            );
        }



    }

    $this->extend('updateCMSFields', $fields);

    return $fields;

    }

    /**
     * return array containing all possible email templates file name
     * under the folders of both theme and project specific folder.
     *
     * @return array
     */
    public function templateSource(){
        $paths = NewsletterAdmin::template_paths();

        $templates = array(
            // We dont want the plugin default template.
            // "SimpleNewsletterTemplate" => _t('TemplateList.SimpleNewsletterTemplate', 'Simple Newsletter Template')
        );

        if(isset($paths) && is_array($paths)){
            $absPath = Director::baseFolder();
            if( $absPath{strlen($absPath)-1} != "/" )
                $absPath .= "/";
            foreach($paths as $path){
                $path = $absPath.$path;

                if(is_dir($path)) {
                    $templateDir = opendir( $path );

                    // read all files in the directory
                    while(($templateFile = readdir($templateDir)) !== false) {
                        // *.ss files are templates
                        if( preg_match( '/(.*)\.ss$/', $templateFile, $match )){
                            // only grab those haveing $Body coded
                            if(strpos("\$Body", file_get_contents($path."/".$templateFile)) === false){
                                $templates[$match[1]] = preg_replace('/_?([A-Z])/', " $1", $match[1]);
                            }
                        }
                    }
                }
            }
        }
        return $templates;
    }

    /**
     * @return Array Map of place holder name to a description of its usage
     */
    public function getAvailablePlaceholders() {
        return array(
            'UnsubscribeLink' => _t(
                'Newsletter.PlaceholderUnsub',
                'Personalized link to unsubscribe from newsletter'
            ),
            'AbsoluteBaseURL' => _t(
                'Newsletter.PlaceholderAbsoluteUrl',
                'Absolute URL to the website'
            ),
            'To' => _t(
                'Newsletter.PlaceholderTo',
                'Recipient email address'
            ),
            'From' => _t(
                'Newsletter.PlaceholderFrom',
                'Sender email address'
            ),
            'Subject' => _t(
                'Newsletter.PlaceholderSubject',
                'Newsletter subject'
            ),
            'Recipient.Title' => _t(
                'Newsletter.PlaceholderTitle',
                'Recipient full name, including salutation, first/middle/last name (all optional)'
            ),
            'Recipient.Salutation' => _t(
                'Newsletter.PlaceholderSalutation',
                'Recipient salutation'
            ),
            'Recipient.FirstName' => _t(
                'Newsletter.PlaceholderFirstName',
                'Recipient first name'
            ),
            'Recipient.Surname' => _t(
                'Newsletter.PlaceholderSurname',
                'Recipient surname'
            ),
            'Recipient.Email' => _t(
                'Newsletter.PlaceholderEmail',
                'Recipient email address'
            ),
            'Now' => _t(
                'Newsletter.PlaceholderDate',
                'Current date and time (format e.g. with $Now.Nice)'
            )
        );
    }

    function getTitle() {
        return $this->getField('Subject');
    }

    function render() {
        if(!$templateName = $this->RenderTemplate) {
            $templateName = 'SimpleNewsletterTemplate';
        }
        // Block stylesheets and JS that are not required (email templates should have inline CSS/JS)
        Requirements::clear();

        // Create recipient with some test data
        //$recipient = new Member(Member::$test_data);
        $recipient = Member::currentUser()->toMap();
        $newsletterEmail = new NewsletterEmail($this, $recipient, true);
        return HTTP::absoluteURLs($newsletterEmail->getData()->renderWith($templateName));
    }

    public function canDelete($member = null) {
        $can = parent::canDelete($member);
        if($this->Status !== 'Sending') return $can;
        else return false;
    }

    function getContentBody(){
        $content = $this->obj('Content');

        $this->extend("updateContentBody", $content);
        return $content;
    }

    public function Link($action = null) {
        return Controller::join_links(singleton('NewsletterAdmin')->Link('Newsletter'),
            '/EditForm/field/Newsletter/item/', $this->ID, $action);
    }

    /**
     * @return String
     */
    public function CMSEditLink() {
        return Controller::join_links(singleton('NewsletterAdmin')->Link('Newsletter'),
        '/EditForm/field/Newsletter/item/', $this->ID, 'edit');
    }

    public function onBeforeDelete(){
        parent::onBeforeDelete();
        $queueditems = $this->SendRecipientQueue();
        if($queueditems && $queueditems->exists()){
            foreach($queueditems as $item){
                $item->delete();
            }
        }
        $trackedLinks = $this->TrackedLinks();
        if($trackedLinks && $trackedLinks->exists()){
            foreach($trackedLinks as $link){
                $link->delete();
            }
        }
        //remove this from its belonged mailing lists
        $this->MailingLists()->removeAll();
    }

}

/**
 * Tracked link is a record of a link from the {@link Newsletter}
 *
 * @package newsletter
 */
class Newsletter_TrackedLink extends DataObject {

    private static $db = array(
        'Original' => 'Varchar(255)',
        'Hash' => 'Varchar(100)',
        'Visits' => 'Int'
    );

    private static $has_one = array(
        'Newsletter' => 'Newsletter'
    );

    private static $summary_fields = array(
        "Newsletter.Subject" => "Newsletter",
        "Original" => "Link URL",
        "Visits" => "Visit Counts"
    );

    /**
     * Generate a unique hash
     */
    function onBeforeWrite() {
        parent::onBeforeWrite();

        if(!$this->Hash) $this->Hash = md5(time() + rand());
    }

    /**
     * Return the full link to the hashed url, not the
     * actual link location
     *
     * @return String
     */
    function Link() {
        if(!$this->Hash) $this->write();

        return 'newsletterlinks/'. $this->Hash;
    }
}
