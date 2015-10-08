<?php

class Contact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Contact::skipAttributes(array('ctime'));
        $this->hasMany("id", "Queue", "contact_id");
        $this->hasMany("id", "GroupHasContact", "contact_id");
    }
    public function findGroupByMobileOrEmail($dbkey, $data,$action){
        $group = array();
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
        if($contact){
            $grouphascontacts = $contact->GroupHasContact; 
        }
        if(isset($grouphascontacts)){
           foreach($grouphascontacts as $grouphascontact){
                $group[] = $grouphascontact->group_id;
            } 
        }
        
        return $group;
    }
    static function getList($modelsManager , $where , $appendix = null ){
		
		

            $num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 25;
            $page = isset($appendix['page']) ? $appendix['page'] : 1;
            
            $builder = $modelsManager->createBuilder()
                   ->columns('Contact.id as id,Contact.name as name,Contact.mobile_digest as mobile_digest,Contact.email_digest as email_digest,Contact.description as description,Contact.status as status,Contact.ctime as ctime,Contact.mtime as mtime')
                   ->from('Contact')
                   ->leftjoin('GroupHasContact');
            $strWhere = null;
            if($where){
                    foreach($where as $k=>$v){
                        $strWhere[]  =  "{$k} = '{$v}'";    
                    }
                    $strWhere = implode(' AND ', $strWhere);
            }
            $builder =$builder->where($strWhere);

            $data =  new Phalcon\Paginator\Adapter\QueryBuilder(
                            array(
                                            "builder" => $builder,
                                            "limit"=> $num,
                                            "page" => $page
                            )
            );
            return $data;


    }
    
}

