<?php

class Task extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Task::skipAttributes(array('mtime','gateway','ctime'));
    }
}

