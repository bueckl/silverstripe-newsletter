<?php
namespace Newsletter\Admin;

use CopyButton\GridFieldCopyButton;
use Newsletter\Form\Gridfield\MailingListGridFieldDetailForm;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;

class MailingListAdmin extends ModelAdmin {

    private static $managed_models = array(
        'MailingList'  => array('title' => 'Mailing-Listen')
    );

    public $showImportForm = false;

    private static $url_segment = 'mailinglists';

    private static $menu_title = '7 - Mailing Listen';


    public function getEditForm($id = null, $fields = null) {

        $form = parent::getEditForm($id, $fields);

        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
        $config = $gridField->getConfig();
        $config->removeComponentsByType(GridFieldDetailForm::class)->addComponents(new MailingListGridFieldDetailForm());
        $config->addComponent(new GridFieldCopyButton(), GridFieldEditButton::class);
        $config->getComponentByType(GridFieldDataColumns::class)->setDisplayFields(array(
            'ID' => '#',
            'Title' => 'Name'
            // 'filteredRecipients.Count' => 'Contacts on list',
        ));
        return $form;
    }
}
