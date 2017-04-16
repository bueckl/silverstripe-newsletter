<?php

/**
 * Creates MailingLists based on Tags!!! Button to be added to the bottom of a {@link GridField}
 */

class GridFieldSyncMailingListButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler {

    /**
     * @var array Map of a property name on the exported objects, with values being the column title in the CSV file.
     * Note that titles are only used when {@link $csvHasHeader} is set to TRUE.
     */
    protected $exportColumns;


    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param array $exportColumns The columns to include in the export
     */
    public function __construct($targetFragment = "after") {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField) {
        $button = new GridField_FormAction(
            $gridField,
            'sync_mailinglists',
            'Update/Sync ALL Mailing Lists',
            'sync_mailinglists',
            null
        );


        $button->setAttribute('data-icon', 'download-csv');
        $button->addExtraClass('action_sync_mailinglists');
        $button->setForm($gridField->getForm());
        return array(
            $this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        );
    }

    /**
     * tagss_to_mailinglists is an action button
     */
    public function getActions($gridField) {
        return array('sync_mailinglists');
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
        if($actionName == 'sync_mailinglists') {
            return $this->handleSyncMailinglists($gridField);
        }
    }

    /**
     * it is also a URL
     */
    public function getURLHandlers($gridField) {
        return array(
            'export' => 'handleSyncMailinglists',
        );
    }




    public function AddRecipientsToMailingList($TagID, $MailingListID) {


        $Recipients = Recipient::get();

        foreach ($Recipients as $Recipient) {

            // Check if Recipient has an Tag with the current TagID
            $RecipientTags = $Recipient->Tags();

            foreach($RecipientTags as $RecipientTag) {

                if ( $RecipientTag->ID ==  $TagID) {
                    // Now add to Recipient to MailingList
                    // TODO Check if already on List
                    $MailingList = MailingList::get()->filter('ID', $MailingListID );
                    $MailingList->First()->Recipients()->add($Recipient);

                }

            }


        }

    }


    /**
     * Handle the logic, for both the action button and the URL
     */

    public function handleSyncMailinglists($gridField, $request = null) {

        Controller::curr()->getResponse()->setStatusCode(
            200,
            'Implement Logic to sync/update Mailing lists based on filters ...'
        );

        return;


        $Tags = CustomerTag::get();

        foreach ($Tags as $Tag ) {

            // We write mailinglists if not exist already
            if ( MailingList::get()->filter('TagID', $Tag->ID)->Count() == 0 ) {

                $ml = MailingList::create(array(
                    'Title' => $Tag->getTitle(),
                    'TagID' => $Tag->ID
                ));

                $ml->write();
            }

            $MailingList = MailingList::get()->filter('TagID', $Tag->ID);
            // Case Mailing list already exist we only connect the recipients

            if ( $MailingList->First() ) {
                // We add the respective Recipients to the list
                $this->AddRecipientsToMailingList($Tag->ID, $MailingList->First()->ID);
            }
        }

        // output a success message to the user
        Controller::curr()->getResponse()->setStatusCode(
            200,
            'Yay! Your Mailing Lists are all up to date now!'
        );

    }



}
