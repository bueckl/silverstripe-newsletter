<?php
/**
 * @package  newsletter
 */

/**
 * Create a form that a user can use to unsubscribe from a mailing list
 */
class UnsubscribeController extends Page_Controller {

    static public $days_unsubscribe_link_alive = 30;

    private static $allowed_actions = array(
        'index',
        'done',
        'Form',
        'sendUnsubscribeLink'
    );

    function __construct($data = null) {
        parent::__construct($data);
    }

    function init() {
        parent::init();


        // Important hack to make translations work. JOCHEN!
        i18n::set_locale(  i18n::get_locale_from_lang( $this->request->allParams()['Lang'] ) );
        
        
        // Requirements::css('newsletter/css/SubscriptionPage.css');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-validate/jquery.validate.min.js');
    }

    static public function set_days_unsubscribe_link_alive($days){
        self::$days_unsubscribe_link_alive = $days;
    }

    static public function get_days_unsubscribe_link_alive(){
        return self::$days_unsubscribe_link_alive;
    }

    function RelativeLink($action = null) {
        return "unsubscribe/$action";
    }

    private function getRecipient(){
        $validateHash = Convert::raw2sql($this->urlParams['ValidateHash']);
        if($validateHash) {
            $recipient = Member::get()->filter("ValidateHash", $validateHash)->first();
            $now = date('Y-m-d H:i:s');
            if($now <= $recipient->ValidateHashExpired) return $recipient;
        }
    }

    private function getMailingLists($recipient = null){
        $siteConfig = DataObject::get_one("SiteConfig");
        if($siteConfig->GlobalUnsubscribe){
            return $mailinglists = $recipient->MailingLists();
        }else{
            $mailinglistIDs = $this->urlParams['IDs'];
            if($mailinglistIDs) {
                $mailinglistIDs = explode(',', $mailinglistIDs);
                return $mailinglists = DataList::create("MailingList")
                    ->filter(array('ID' => $mailinglistIDs));
            }
        }
    }

    private function getMailingListsByUnsubscribeRecords($recordIDs){
        $recordIDs = explode(',', $recordIDs);
        $unsubscribeRecords = DataList::create("UnsubscribeRecord")
            ->filter(array('ID' => $recordIDs));
        $mailinglists = new ArrayList();
        if($unsubscribeRecords->count()){
            foreach($unsubscribeRecords as $record){
                $list = DataObject::get_by_id("MailingList", $record->MailingListID);
                if($list && $list->exists()){
                    $mailinglists->push($list);
                }
            }
        }
        return $mailinglists;
    }

    function index() {
        $recipient = $this->getRecipient();
        $mailinglists = $this->getMailingLists($recipient);
        if($recipient && $recipient->exists() && $mailinglists && $mailinglists->count()) {
            
            $unsubscribeRecordIDs = array();
            
            $this->unsubscribeFromLists($recipient, $mailinglists, $unsubscribeRecordIDs);

            $url = Director::absoluteBaseURL() .'/'.i18n::get_lang_from_locale($this->Locale).'/'. $this->RelativeLink('done') . "/" . $recipient->ValidateHash . "/" .
                implode(",", $unsubscribeRecordIDs);
            Controller::curr()->redirect($url, 302);
            return $url;
        }else{
            return $this->customise(array(
                'Title' => _t('Newsletter.INVALIDLINK', 'Invalid Link'),
                'Content' => _t('Newsletter.INVALIDUNSUBSCRIBECONTENT', 'This unsubscribe link is invalid')
            ))->renderWith('Page');
        }
    }

    function done() {
        $unsubscribeRecordIDs = $this->urlParams['IDs'];
        $hash = $this->urlParams['ID'];
        if($unsubscribeRecordIDs){
            $fields = new FieldList(
                new HiddenField("UnsubscribeRecordIDs", "", $unsubscribeRecordIDs),
                new HiddenField("Hash", "", $hash)
                );
                // new LiteralField("ResubscribeText",
                //     "Click the \"Resubscribe\" if you unsubscribed by accident and want to re-subscribe")
                // );
            $actions = new FieldList(
                new FormAction("resubscribe", "Resubscribe")
            );

            $form = new Form($this, "ResubscribeForm", $fields, $actions);
            $form->setFormAction($this->Link('resubscribe'));

            $mailinglists = $this->getMailingListsByUnsubscribeRecords($unsubscribeRecordIDs);

            if($mailinglists && $mailinglists->count()){
                $listTitles = "";
                foreach($mailinglists as $list) {
                    $listTitles .= "<li>".$list->Title."</li>";
                }
                $recipient = $this->getRecipient();
                $title = $recipient->FirstName?$recipient->FirstName:$recipient->Email;
                $content = sprintf(
                    _t('Newsletter.UNSUBSCRIBEFROMLISTSSUCCESS',
                        '<h3>Thank you, %s.</h3><br />You will no longer receive: %s.'),
                    $title,
                    "<ul>".$listTitles."</ul>"
                );
            }else{
                $content =
                    _t('Newsletter.UNSUBSCRIBESUCCESS', 'Thank you.<br />You have been unsubscribed successfully');
            }
        }

        return $this->customise(array(
            'Title' => '<br><br>',
            'Content' => $content,
            // 'Form' => $form
        ))->renderWith('Page');
        }


    protected function unsubscribeFromLists($recipient, $lists, &$recordsIDs) {
        if($lists && $lists->count()){
            foreach($lists as $list){
                $recipient->Mailinglists()->remove($list);
                $unsubscribeRecord = new UnsubscribeRecord();
                $unsubscribeRecord->unsubscribe($recipient, $list);
                $recordsIDs[] = $unsubscribeRecord->ID;
            }
        }
    }

    /** Send an email with a link to unsubscribe from all this user's newsletters */
    public function sendUnsubscribeLink(SS_HTTPRequest $request) {
        //get the form object (we just need its name to set the session message)
        $form = NewsletterContentControllerExtension::getUnsubscribeFormObject($this);

        $email = Convert::raw2sql($request->requestVar('email'));
        $recipient = Member::get()->filter('Email',$email)->First();

        if ($recipient) {
            //get the IDs of all the Mailing Lists this user is subscribed to
            $lists = $recipient->MailingLists()->column('ID');
            $listIDs = implode(',',$lists);

            $days = UnsubscribeController::get_days_unsubscribe_link_alive();

            if($recipient->ValidateHash){
                $recipient->ValidateHashExpired = date('Y-m-d H:i:s', time() + (86400 * $days));
                $recipient->write();
            }else{
                $recipient->generateValidateHashAndStore($days);
            }


            $templateData = array(
                'FirstName' => $recipient->FirstName,
                'UnsubscribeLink' =>
                    Director::absoluteBaseURL() . $request->allParams()['Lang']. "/unsubscribe/index/".$recipient->ValidateHash."/$listIDs"
            );
            //send unsubscribe link email
            // i18n::set_locale(Controller::curr()->Locale);


            $email = new Email();
            $email->setTo($recipient->Email);
            $from = Email::getAdminEmail();
            $email->setFrom($from);
            
            if ( $request->allParams()['Lang'] == "en" ) {
                $email->setTemplate('UnsubscribeLinkEmail');
            }else {
                $email->setTemplate('UnsubscribeLinkEmail_es');
            }
            
            $email->setSubject(_t(
                'Newsletter.ConfirmUnsubscribeSubject',
                "Confirmation of your unsubscribe request"
            ));
            $email->populateTemplate( $templateData );
            $email->send();

            $form->sessionMessage(_t('Newsletter.GoodEmailMessage',
            'You have been sent an email containing an unsubscribe link'), "good");
        } else {
            //not found Recipient, just reload the form
            $form->sessionMessage(_t('Newsletter.BadEmailMessage','Email address not found'), "bad");
        }
        Controller::curr()->redirectBack();
    }
}
