<?php

class Article extends \Phalcon\Mvc\Model
{
	public function initialize(){
		//$this->hasOne('id', 'Category', 'category_id');
		$this->skipAttributes(array('from', 'status', 'ctime'));
	}
	
	static function getList($modelsManager , $where , $appendix = array()){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$builder = $modelsManager->createBuilder()
					->columns('article.*,category.name cname')
					->from('article')
					->leftjoin('category','category.id = article.division_category_id');
		
		return $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			    "builder" => $builder,
			    "limit"=> $num,
				"page" => $page
			));
	}
	
	static function getOne($id){
		if($id){
			$conditions = " id= :id: ";
			$parameters = array("id"=>$id);
			$articles = Article::find(array($conditions, 'bind' => $parameters));
			//echo '<pre>';print_r($article);exit;
			$article = array();
			foreach ($articles as $article){
				$article = (array)$article;
			}
			return (object)$article;
		}
		return null;
	}
	
}
?>