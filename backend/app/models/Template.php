<?php 
class Template extends \Phalcon\Mvc\Model
{
	static public function initialize()
	{
	}
	static function insert($params = null){
		$t = new Template;
		$t->id = 0;
		if(is_array($params)){
			foreach($params as $k=>$v){
				$t->$k = $v;
			}
		}
		if($t->save()){
			return $t->id;
		}
		
		return false;
		
	}
	static function getList($where , $appendix = null ){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		return  new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Template::find(array($where , 'limit'=>$num,'offset'=>($page-1)*$num)),
						"count"=> Template::count(),
						"limit"=> $num,
						"page" => $page
				)
		);
	}
	
	
}
?>