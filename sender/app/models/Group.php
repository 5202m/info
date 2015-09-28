<?php

class Group extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Group::skipAttributes(array('ctime'));
    }
}

