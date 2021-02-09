<?php
namespace Newsletter\Admin;

use CopyButton\GridFieldCopyButton;
use Newsletter\Form\Gridfield\MailingListGridFieldDetailForm;
use Newsletter\Model\MailingList;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use Newsletter\Form\Gridfield\MailingListGridFieldDetailForm_ItemRequest;

class MailingListAdmin extends ModelAdmin {

    private static $managed_models = array(
        MailingList::class  => array('title' => 'Mailing-Listen')
    );

    public $showImportForm = false;

    private static $url_segment = 'mailinglists';

    private static $menu_title = '7 - Mailing Listen';


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        //custom handling of the newsletter modeladmin with a specialized action menu for the detail form
        if ($this->modelClass == MailingList::class) {
            $config = $form->Fields()->first()->getConfig();
            $config->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponents(new MailingListGridFieldDetailForm());
            
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            $config->removeComponentsByType(GridFieldFilterHeader::class);            
            
            $gridFieldName = $this->sanitiseClassName($this->modelClass);
            $gridField = $form->Fields()->fieldByName($gridFieldName);
            $gridFieldForm = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);
            $gridFieldForm->setItemRequestClass(MailingListGridFieldDetailForm_ItemRequest::class);

        }
        
        return $form;
    }
}
