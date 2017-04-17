<?php

class MailingListAdmin extends ModelAdmin {

    private static $managed_models = array(
        'MailingList'  => array('title' => 'Mailing-Listen')
    );

    public $showImportForm = false;

    private static $url_segment = 'mailinglists';

    private static $menu_title = 'Mailing List Admin';

    public function getEditForm($id = null, $fields = null) {

        $form = parent::getEditForm($id, $fields);
        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
        $config = $gridField->getConfig();
        $config->addComponents(new GridFieldSyncMailingListButton('before'));
        $config->addComponent(new GridFieldCopyButton(), 'GridFieldEditButton');

        $config->getComponentByType('GridFieldDataColumns')->setDisplayFields(array(
            'ID' => '#',
            'Title' => 'Name',
            'filteredRecipients.Count' => 'Contacts on list',
        ));


        return $form;
    }

}
