<?php

class Article extends \Phalcon\Mvc\Model
{
	public function initialize(){
		//$this->hasOne('id', 'Category', 'division_category_id');
		$this->skipAttributes(array('from', 'status', 'ctime'));
	}
	
	/**
	 * 获取文章列表数据
	 * @param unknown_type $modelsManager
	 * @param unknown_type $where
	 * @param unknown_type $appendix
	 */
	static function getList($modelsManager , $where , $appendix = array()){
		$num = isset($appendix['pageSize'])  ? $appendix['pageSize'] : 10;
		$page = isset($appendix['page']) ? $appendix['page'] : 1;
		
		$builder = $modelsManager->createBuilder()
					->columns("*")//->columns("article.*,category.name cname")//连接查询的表中有相同名称的字段不能使用as别名的方式，否则用相同名称的字段进行条件筛选时会报错
					->from("article");
		$strWhere = null;
		if($where){
			foreach($where as $k=>$v){
				if($k=='title'){
					$strWhere[]  =  "article.{$k} LIKE  '%{$v}%'";
				}
				elseif($k=='ctime'){
					$strWhere[] = "article.{$k} LIKE '{$v}%'";
				}
				else{
					$strWhere[]  =  "article.{$k} = '{$v}'";
				}
			}
			$strWhere = implode(' AND ', $strWhere);
		}
		$builder = $builder->where($strWhere)
					//->leftjoin("category", "category.id = article.division_category_id")
					->orderby($appendix['order']);
		//echo '<pre>';print_r($builder);exit;
		return $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			    "builder" => $builder,
			    "limit"=> $num,
				"page" => $page
			));
	}
	
	/**
	 * 获取单条文章数据
	 * @param unknown_type $id
	 */
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
	
	/**
	 * 删除文章
	 * @param unknown_type $modelsManager
	 * @param unknown_type $ids
	 */
	static function deleteArticle($modelsManager, $ids){
		if(!empty($ids)){
			//$parameters = array('id'=>$ids);
			$phql = "UPDATE article SET article.visibility='Hidden', article.status='Disabled' WHERE article.id in ({$ids})";
			$status = $modelsManager->executeQuery($phql);
		    //print_r($status);exit;
		    return $status;
		}
		return false;
	}
	
}
?>