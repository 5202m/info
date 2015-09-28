<?php

class Queue extends \Phalcon\Mvc\Model
{
    public function initialize(){
        $this->belongsTo("robots_id", "Contact", "id");
    }
    static function getList($modelsManager , $where , $appendix = null ){
		
		

            $num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
            $page = isset($appendix['page']) ? $appendix['page'] : 1;
            
            $builder = $modelsManager->createBuilder()
                   ->columns('*')
                   ->from('Queue');
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

