<?php
namespace Newsletter\Extensions;

use Newsletter\Controller\UnsubscribeController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * @package  newsletter
 * Adds functions to the ContentController, functions that can be accessed on any page. For example,
 * the global unsubscribe form.
 */

class NewsletterContentControllerExtension extends Extension {

    /**
     * Utility method to get the unsubscribe form
     */
    public static function getUnsubscribeFormObject($self, $fields = null, $actions = null) {
        if (!$fields) {
            $fields = FieldList::create();
        }
        if (!$actions) {
            $actions = FieldList::create();
        }
        return new Form(null, 'unsubscribeLink', $fields, $actions);
    }

    public function UnsubscribeRequestForm() {
        $fields = new FieldList(
            EmailField::create('email',_t("Newsletter.UnsubscribeEmail","Your subscription email address"))
        );

        $actions = new FieldList(
            FormAction::create('sendLink',  _t('Newsletter.SendUnsubscribeLink', 'Send unsubscribe link'))
                ->addExtraClass('ss-ui-action-constructive'),
            FormAction::create('ResetFormAction', 'clear', _t('CMSMain_left.ss.RESET', 'Reset'))
                ->setAttribute('type', 'reset')
        );

        $unsubscribeController = new UnsubscribeController();

        $form = NewsletterContentControllerExtension::getUnsubscribeFormObject($this, $fields, $actions);
        $form->setFormMethod('GET');
        $form->setFormAction(Controller::join_links(
            Director::absoluteBaseURL(),
            $unsubscribeController->relativeLink('sendUnsubscribeLink')
        ));
        $form->addExtraClass('cms-search-form');

        return $form;
    }
}
