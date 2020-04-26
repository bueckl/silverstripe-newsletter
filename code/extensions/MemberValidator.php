<?php

class MemberValidator extends RequiredFields {

    function php($data) {

        $bRet = parent::php($data);

        if (empty($data['Email'])) {
            $this->validationError('Email','Your Email, please?','required');
            return;
        }

        if (!Email::validEmailAddress($data['Email'])) {
            $this->validationError('Email','This is not a valid Email','required');
            return;
        }
        
        
        // Check if an Email exists but belongs to a different User
        // If so we dont want to add this user and so avoid duplicates
        $ExistingRecipient = Member::get()->filter('Email', $data['Email'])->First();
        
        
        if ($ExistingRecipient->ID > 0) {

            if($ExistingRecipient->Email == $data['Email']) {
              
              
                // Es gibt schon einen User mit dieser Email

                $MailingListID = $data['MailingListID'];
                $Recipient = new RecipientExtension();
                // Is already subscribed. maybe just add the user to another list?
                if ( $Recipient::inMailingList( $ExistingRecipient, $MailingListID )) {
                    // TODO write correct error message and show message in form
                    $this->validationError('Email','You have already subscribed to this mailing list','required');
                    $bRet = false;
                    return $bRet;
                } else {
                  
                }

            } else {
            }

        // Wir legen komplett neu an
        } else {
            // Wir legen neu an
        }

        return $this->getErrors();
    }
}