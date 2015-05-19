<?php

class ControllerBase extends \Phalcon\Mvc\Controller
{
    public function initialize(){
        if (!$this->session->has("auth")) {
            return $this->response->redirect('/login/index');
        }
    }
}