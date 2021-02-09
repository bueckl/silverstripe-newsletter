<?php

namespace Newsletter\Form\Gridfield;

use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use Newsletter\Model\MailingList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Dev\Debug;
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

        $button = FormAction::create('doSave',  _t('MailingList.SAVEANDSYNC', "Save & Update Recipients for this Mailing List"));
        $actions->replaceField("action_doSave",
            $button
            ->addExtraClass('ss-ui-action-constructive')
            ->setAttribute('data-icon', 'addpage')
            ->setUseButtonTag(true), 'action_doSave');


       
        return $actions;
    }

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $form->setActions($this->updateCMSActions($form->Actions()));
        return $form;
    }

    public function doSave($data, $form) {
                
        parent::doSave($data, $form);        
        return $this->redirectBack();
        
    }
}
