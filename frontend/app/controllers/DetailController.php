<?php

class DetailController extends ControllerBase
{

    public function indexAction()
    {
// 		echo "hello";
// 		var_dump(array(1, 2, 3, 4));
// 		var_export(new stdclass());	
    }
    
    public function htmlAction($template_id, $category_id, $article_id){
    
    	$conditions = "category_id = :category_id: AND id = :article_id: AND status = :status:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'article_id' => $article_id,
    			'status' => 'Display'
    	);
    	$article = Article::findFirst(array(
    			$conditions,
    			"bind" => $parameters
    	));
 
    	$this->view->setVar('article',$article);
    }
    public function jsonAction($template_id, $category_id, $article_id){

    	$this->view->disable();
    
    	$conditions = "category_id = :category_id: AND id = :article_id: AND status = :status:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'article_id' => $article_id,
    			'status' => 'Display'
    	);
    	$article = Article::findFirst(array(
    			$conditions,
    			"bind" => $parameters
    	));
   	
    	$response = new Phalcon\Http\Response();
    	$response->setHeader('Cache-Control', 'max-age=60');
    	$response->setContentType('application/json', 'utf-8');
    	$response->setContent(json_encode($article));
    	return $response;
    }    
    
    public function pageAction(){
    	 
    }
    public function purgeAction(){
    }
}

