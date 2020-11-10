<?php
namespace Newsletter\Admin;

use Newsletter\Form\Gridfield\NewsletterGridFieldDetailForm;
use Newsletter\Form\Gridfield\NewsletterGridFieldDetailForm_ItemRequest;
use Newsletter\Model\Newsletter;
use Newsletter\Model\Newsletter_Sent;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * @package  newsletter
 */

/**
 * Newsletter administration section
 */
class NewsletterAdmin extends ModelAdmin {

    private static $url_segment  = 'newsletter';
    private static $menu_title   = '6 - Newsletter';
    private static $menu_icon    = '_resources/vendor/silverstripe/newsletter/images/newsletter-icon.png';
    public $showImportForm       = false;

    private static $managed_models = [
        Newsletter::class => array('title' => 'Mailing'),
        Newsletter_Sent::class => array('title' => 'Verschickte Mailings'),
        // "MailingList"
        // "Member"
    ];

    /**
     * @var array Array of template paths to check
     */
    static $template_paths = null; //could be customised in _config.php

    public function init() {
        parent::init();

//        Requirements::javascript('silverstripe/newsletter:javascript/SilverStripeNavigator.js');
        Requirements::javascript('silverstripe/newsletter:javascript/ActionOnConfirmation.js');
        Requirements::javascript('silverstripe/newsletter:javascript/RecipientsPreviewPopup.js');
        Requirements::javascript('silverstripe/newsletter:javascript/EmailPreviewPopup.js');
        Requirements::javascript('silverstripe/newsletter:javascript/ProcessQueue.js');
        Requirements::javascript('silverstripe/newsletter:javascript/DeleteQueue.js');
        Requirements::css('silverstripe/newsletter:css/NewsletterAdmin.css');
    }


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        //custom handling of the newsletter modeladmin with a specialized action menu for the detail form
        if ($this->modelClass == Newsletter::class || $this->modelClass == Newsletter_Sent::class) {
            $config = $form->Fields()->first()->getConfig();
            $config->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponents(new NewsletterGridFieldDetailForm());
            if ($this->modelClass == Newsletter_Sent::class) {
                $config->removeComponentsByType(GridFieldAddNewButton::class);
            }
            $config->getComponentByType(GridFieldDataColumns::class)
                ->setFieldCasting(array(
                    "Content" => "HTMLText->LimitSentences",
            ));

            $gridFieldName = $this->sanitiseClassName($this->modelClass);
            $gridField = $form->Fields()->fieldByName($gridFieldName);
            $gridFieldForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);
            $gridFieldForm->setItemRequestClass(NewsletterGridFieldDetailForm_ItemRequest::class);

        }
        if ($this->modelClass == Member::class) {
            $config = $form->Fields()->first()->getConfig();
            $config->getComponentByType(GridFieldDataColumns::class)
                ->setFieldCasting(array(
                    "Blacklisted" => "Boolean->Nice",
                    "Verified" => "Boolean->Nice",
            ));
        }
        return $form;
    }

    /**
    * looked-up the email template_paths.
    * if not set, will look up both theme folder and project folder
    * in both cases, email folder exsits or Email folder exists
    * return an array containing all folders pointing to the bunch of email templates
    *
    * @return array
    */
    public static function template_paths() {

        if(!isset(self::$template_paths)) {
            if(class_exists(SiteConfig::class) &&
                ($config = SiteConfig::current_site_config()) && $config->Theme) {
                $theme = $config->Theme;
            }
//            elseif(SSViewer::current_custom_theme()) {
//                $theme = SSViewer::current_custom_theme();
//            } else if(SSViewer::current_theme()){
//                $theme = SSViewer::current_theme();
//            }
            else {
                $theme = false;
            }

            if($theme) {
                if(file_exists("../".THEMES_DIR."/".$theme."/templates/email")){
                    self::$template_paths[] = THEMES_DIR."/".$theme."/templates/email";
                }
                if(file_exists("../".THEMES_DIR."/".$theme."/templates/Email")){
                    self::$template_paths[] = THEMES_DIR."/".$theme."/templates/Email";
                }
            }

            $project = project();

            if(file_exists("../". $project . '/templates/email')){
            	self::$template_paths[] = $project . '/templates/email';
            }

            if(file_exists("../". $project . '/templates/Email')){
                self::$template_paths[] = $project . '/templates/Email';
            }
        }
        else {
            if(is_string(self::$template_paths)) {
            self::$template_paths = array(self::$template_paths);
            }
        }
        return self::$template_paths;
    }

    public function getList() {
        $list = parent::getList();
        if ($this->modelClass == Newsletter::class || $this->modelClass == Newsletter_Sent::class ){
            if ($this->modelClass == Newsletter::class) {
                $statusFilter = array("Draft", "Sending");

                //using a editform detail request, that should allow Newsletter_Sent objects and regular Newsletters
                if (!empty($_REQUEST['url'])) {
                    if (strpos($_REQUEST['url'],'/EditForm/field/Newsletter/item/') !== false) {
                        $statusFilter[] = "Sent";
                    }
                }
            } else {
                $statusFilter = array("Sent");
            }

            $list = $list->addFilter(array("Status" => $statusFilter));
        }

        return $list;
    }

    /**
    * @return SearchContext
    */
    public function getSearchContext() {
        $context = Injectable::singleton($this->modelClass)->getDefaultSearchContext();

        if($this->modelClass === Newsletter_Sent::class) {
            $context = singleton(Newsletter::class)->getDefaultSearchContext();
            foreach($context->getFields() as $field) $field->setName(sprintf('q[%s]', $field->getName()));
            foreach($context->getFilters() as $filter) $filter->setFullName(sprintf('q[%s]', $filter->getFullName()));
        }

        return $context;
    }
}
