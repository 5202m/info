<?php

class Category extends \Phalcon\Mvc\Model
{
    public function initialize(){
        Category::skipAttributes(array('ctime','mtime'));
    }
    
}

