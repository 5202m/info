<?php
class QueueController extends ControllerBase
{
    public function indexAction(){
        $datas = Queue::find();
        $this->view->setVar('datas', $datas);
    }
}

