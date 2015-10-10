<?php

class Group extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Group::skipAttributes(array('ctime'));
        $this->hasMany("id", "Task", "group_id");
    }
}

