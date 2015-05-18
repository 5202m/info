<?php 
class Template extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		$this->skipAttributes(array('ctime'));
	}
	static function insert($params = null){
		
		$temp = new Template;
		if(is_array($params)){
			foreach($params as $k=>$v){
				$temp->$k = $v;
			}
		}
		if($temp->save()){
			return $temp->id;
		}else{
			return $temp->getMessages();
		}
	}
	static function getList($modelsManager , $where , $appendix = null ){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$builder = $modelsManager->createBuilder()
					->columns('template.*,category.name cname')
					->from('template')
					->leftjoin('category','category.id = template.category_id');
		
		return $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			    "builder" => $builder,
			    "limit"=> $num,
				"page" => $page
			));
		
	}
	static function defaultObject(){
		$obj = new stdClass;
		$columns = array('id'=>0,'category_id','name','decription','content','status','engine');
		foreach($columns as $k=>$v){
			if(is_string($k)){
				$obj->{$k} = $v;
			}else{
				$obj->{$v} = '';
			}
		}
		return $obj;
	}
	
	
}
?>