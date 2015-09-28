<?php

class Queue extends \Phalcon\Mvc\Model
{
    public function initialize(){
        $this->belongsTo("robots_id", "Contact", "id");
    }
    public function getList($where , $appendix = null ){
        $num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
        $page = isset($appendix['page']) ? $appendix['page'] : 1;

        $data =  new \Phalcon\Paginator\Adapter\Model(
                        array(
                                        "data" => Queue::find(array($where , 'limit'=>$num,'offset'=>($page-1)*$num)),
                                        "count"=> Queue::count(),
                                        "limit"=> $num,
                                        "page" => $page
                        )
        );
        return $data;
    }
}

