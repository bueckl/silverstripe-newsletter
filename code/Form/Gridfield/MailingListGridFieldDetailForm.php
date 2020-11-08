<?php

namespace Newsletter\Form\Gridfield;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use Newsletter\Model\MailingList;

/**
 * @package  newsletter
 */

/**
 * Provides view and edit forms at Newsletter gridfield dataobjects,
 * giving special buttons for sending out the newsletter
 */
class MailingListGridFieldDetailForm extends GridFieldDetailForm
{
}

class MailingListGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{
    private static $allowed_actions = array(
        'ItemEditForm',
        'doSave',
    );

    public function updateCMSActions(FieldList $actions)
    {

        $actions->fieldByName("action_doSave")
            ->setTitle(_t('MailingList.SAVEANDSYNC', "Save & Update Recipients for this Mailing List"))
            ->removeExtraClass('ss-ui-action-constructive')
            ->setAttribute('data-icon', 'addpage');
        return $actions;
    }

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $form->setActions($this->updateCMSActions($form->Actions()));
        return $form;
    }
}
