<?php

class GroupHasContact extends \Phalcon\Mvc\Model
{
    public function initialize(){
        GroupHasContact::skipAttributes(array('ctime'));
    }
}

