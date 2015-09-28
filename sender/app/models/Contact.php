<?php

class Contact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        $this->hasMany("id", "Queue", "contact_id");
    }
    public function checkLogin($data,$dbkey){
        $contact = Contact::findFirst(
            "AES_DECRYPT(mobile,'{$dbkey}') = '{$data[1]}' or AES_DECRYPT(email,'{$dbkey}') = '{$data[2]}'"
        );
        return $contact;
    }
    
}

