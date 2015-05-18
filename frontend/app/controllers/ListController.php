<?php
use Phalcon\Mvc\View, 
	Phalcon\Mvc\Controller;

class ListController extends ControllerBase
{

    public function indexAction()
    {
// 	echo "hello";
// 	var_dump(array(1, 2, 3, 4));
// 	var_export(new stdclass());	
    }
    public function htmlAction($template_id,$category_id, $limit, $offset){
    
    	if($limit > 100){
    		$limit = 100;
    	}
    	$conditions = "category_id = :category_id: AND language = :language: AND status = :status:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'language' => 'cn',
    			'status' => 'Display'
    	);
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'limit' => $limit
    	));
    
    	$this->view->setVar('articles',$articles);
    }
    public function rssAction($template_id,$category_id, $limit, $offset){
    
    	if($limit > 100){
    		$limit = 100;
    	}
    	$conditions = "category_id = :category_id: AND language = :language: AND status = :status:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'language' => 'cn',
    			'status' => 'Display'
    	);
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'limit' => $limit
    	));
    
    	$this->view->setVar('articles',$articles);
    	$this->view->disableLevel(View::LEVEL_MAIN_LAYOUT);
    }
    public function jsonAction($template_id,$category_id, $article_id){
    
    	$this->view->disable();
    
    	$conditions = "category_id = :category_id: AND language = :language: AND status = :status:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'language' => 'cn',
    			'status' => 'Display'
    	);
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'limit' => $limit
    	));
    	$result = array();
    	foreach ($articles as $article){
    		unset($article->status);
    		unset($article->from);
    		unset($article->from);
    		$result[]=$article;
    	}
    	$response = new Phalcon\Http\Response();
    	$response->setHeader('Cache-Control', 'max-age=60');
    	$response->setContentType('application/json', 'utf-8');
    	$response->setContent(json_encode($result));
    	return $response;
    }
    
    public function pageAction(){
    	
    }
    public function purgeAction(){
    }
}

