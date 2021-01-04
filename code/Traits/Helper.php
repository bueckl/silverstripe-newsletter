<?php
namespace Newsletter\Traits;

use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;

trait Helper {
    public function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }

    private function getRecipient($params){
        $validateHash = Convert::raw2sql($params['ValidateHash']);
        if($validateHash) {
            $recipient = Member::get()->filter("ValidateHash", $validateHash)->first();
            $now = date('Y-m-d H:i:s');
            if($now <= $recipient->ValidateHashExpired) {
                return $recipient;
            }
        }
    }
}
