<?php


namespace Newsletter\Model;


use SilverStripe\ORM\DataObject;

class NewsletterFeedback extends DataObject
{

    private static $table_name = 'NewsletterFeedback';

    private static $db = [
        'Email' => 'Varchar(255)',
        'Message' => 'HTMLText'
    ];

    private static $has_one = [
        'Newsletter' => Newsletter::class
    ];
}