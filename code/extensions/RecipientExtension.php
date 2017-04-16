<?php

/**
 * Class RecipientExtension.
 */
class RecipientExtension extends DataExtension
{
    // a newsletter recipient could belong to many mailing lists.
    private static $belongs_many_many = array(
        'MailingLists' => 'MailingList',
    );
}
