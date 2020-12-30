<?php
namespace Newsletter\Admin;

use Newsletter\Form\Gridfield\NewsletterGridFieldDetailForm;
use Newsletter\Form\Gridfield\NewsletterGridFieldDetailForm_ItemRequest;
use Newsletter\Model\Newsletter;
use Newsletter\Model\Newsletter_Sent;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;


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
        Newsletter::class => array('title' => 'Mailing')
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

            $request = $this->getRequest();
            if (!empty($request->getVar('mail'))) {
                //add new button and header filter removed
                $config->removeComponentsByType(GridFieldAddNewButton::class);
                $config->removeComponentsByType(GridFieldFilterHeader::class);
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
            $newsletterAdminConfigs = Config::inst()->get(NewsletterAdmin::class);
            $templatePaths = $newsletterAdminConfigs['template_paths'];

            if($templatePaths && !empty($templatePaths)) {
                self::$template_paths = $templatePaths;
            }

        } else {
            if(is_string(self::$template_paths)) {
                self::$template_paths = array(self::$template_paths);
            }
        }

        return self::$template_paths;
    }

    public function getList() {
        $list = parent::getList();

        $request = $this->getRequest();
        $paramsSet = $request->postVar('filter');
        $params = $paramsSet[$this->sanitiseClassName($this->modelClass)];
        $fields = Config::inst()->get(Newsletter::class, 'searchable_fields');

        if ($this->modelClass == Newsletter::class){
            //this custom condition is for sent newsletter saveasnew and emailpreview fixes
            if(!$request->postVar('action_doSaveAsNew') && ($request->getVar('preview') != true)) {
                if ($request->getVar('mail') || $request->postVar('mail')) {
                    $statusFilter = "Sent";
                } else {
                    $statusFilter = array("Draft", "Sending");
                }
                $list = $list->filter(array("Status" => $statusFilter));
            }

            if(is_array($params) && count($params)) {
                foreach($fields as $field) {
                    if(isset($params[$field])) {
                        $list = $list->filter($field.':PartialMatch', $params[$field]);
                    }
                }
            }
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
            foreach($context->getFields() as $field) {
                $field->setName(sprintf('q[%s]', $field->getName()));
            }
            foreach($context->getFilters() as $filter) {
                $filter->setFullName(sprintf('q[%s]', $filter->getFullName()));
            }
        }

        return $context;
    }

    protected function getManagedModelTabs()
    {
        $models = $this->getManagedModels();
        $forms = new ArrayList();
        $request = $this->getRequest();
        $var = $request->getVar('mail');

        foreach ($models as $tab => $options) {
            $forms->push(new ArrayData(array(
                'Title' => $options['title'],
                'Tab' => $tab,
                'ClassName' => $options['dataClass'],
                'Link' => $this->Link($this->sanitiseClassName($tab)),
                'LinkOrCurrent' => (empty($var) && $tab == $this->modelTab) ? 'current' : 'link'
            )));
        }

        $forms->push(new ArrayData(array(
            'Title' => "Verschickte Mailings",
            'Tab' => "Newsletter\Model\Newsletter",
            'ClassName' => "Newsletter\Model\Newsletter",
            'Link' => $this->Link($this->sanitiseClassName("Newsletter\Model\Newsletter")).'?mail=sent',
            'LinkOrCurrent' => ($var == 'sent') ? 'current' : 'link'
        )));

        return $forms;
    }

}
