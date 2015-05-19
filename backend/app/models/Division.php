<?php

class Division extends \Phalcon\Mvc\Model
{
    public function initialize(){
        
    }
    public function getID(){
        if ($this->session->has("auth")) {
            $name = $this->session->get("auth");
            $id = $name['id'];
            return $id;
        }
    }
    
}

