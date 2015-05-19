<?php

class Article extends \Phalcon\Mvc\Model
{
	public function initialize()
	{
		//$this->hasOne('id', 'Category', 'category_id');
		$this->skipAttributes(array('from', 'status', 'ctime'));
	}
	
	static function getList($appendix = array())
	{
		/*$manager = new \Phalcon\Mvc\Model\Manager;
		
		$builder = $manager->createBuilder()
		->columns('*,')
		->from('article')
		->leftjoin('category', 'article.category_id = c.id','c');  
		
		$paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			"builder" => $builder,
			"limit"=> $appendix['pageSize'],
			"page" => $appendix['page'],
		)); */
		
		$paginator = new \Phalcon\Paginator\Adapter\Model(
				array(
						"data" => Article::find(array('fields'=>'*', 'limit'=>$appendix['pageSize'], 'offset'=>(($appendix['page']-1)*$appendix['pageSize']))),
						"total_items" => Article::count(),//find(array('fields'=>'*','limit'=>$appendix['pageSize'])),
						"limit"=> $appendix['pageSize'],
						"page" => $appendix['page'],
						//"pageSize" => 
				)
		);
		/*echo '<pre>';
		print_r($builder);exit;*/
		return $paginator;
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