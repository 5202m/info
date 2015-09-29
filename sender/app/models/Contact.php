<?php

class Contact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Contact::skipAttributes(array('ctime'));
        $this->hasMany("id", "Queue", "contact_id");
    }
    public function checkLogin($action,$data,$dbkey){
        if($action == 'upload'){
            $contact = Contact::findFirst(
                "AES_DECRYPT(mobile,'{$dbkey}') = '{$data[1]}' or AES_DECRYPT(email,'{$dbkey}') = '{$data[2]}' or mobile_digest = md5('{$data[1]}') or email_digest = md5('{$data[2]}')"
            );
        }
        if($action == 'add' ||  $action == 'edit'){
            $contact = Contact::findFirst(
                "AES_DECRYPT(mobile,'{$dbkey}') = '{$data['mobile']}' or AES_DECRYPT(email,'{$dbkey}') = '{$data['email']}' or mobile_digest = md5('{$data['mobile']}') or email_digest = md5('{$data['email']}')"
            );
        }
        
        return $contact;
    }
    
}

