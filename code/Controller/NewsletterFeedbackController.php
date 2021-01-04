<?php


namespace Newsletter\Controller;


use Newsletter\Model\NewsletterFeedback;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class NewsletterFeedbackController extends \PageController
{
    private static $allowed_actions = [
        'feedback',
        'processFeedback'
    ];

    public function feedback()
    {
        Requirements::javascript( 'silverstripe/newsletter:javascript/NewsletterFeedback.js');
        $fields = new FieldList(
            HiddenField::create('Newsletter', false, $this->getRequest()->param('OtherID')),
            TextField::create('Email', 'Your Email'),
            TextField::create('Message', 'Your Message'),
            LiteralField::create('Submit', '<button class="action_doFeedback">Submit</button>')
        );

        $form = new Form($this, 'FeedbackForm', $fields);

        return $this->customise(array(
            'Title' => 'Feedback Form',
            'Form' => $form
        ))->renderWith('FeedbackPage');
    }

    public function processFeedback(HTTPRequest $request = null)
    {
        $newsletterID = $request->postVar('id');
        $email = $request->postVar('email');
        $message = $request->postVar('message');

        $feedback = NewsletterFeedback::create();
        $feedback->NewsletterID = $newsletterID;
        $feedback->Email = $email;
        $feedback->Message = $message;
        $feedback->write();
        return 'Done';
    }

}
