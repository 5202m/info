<?php

class DetailController extends ControllerBase
{

	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
    public function indexAction()
    {
// 		echo "hello";
// 		var_dump(array(1, 2, 3, 4));
// 		var_export(new stdclass());	
    }
    
    public function htmlAction($template_id, $category_id, $article_id){
    
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	$article_id = intval($article_id);
    	 
    	if(empty($category_id) || empty($template_id) || empty($article_id)){
    		echo '404';
    	}
    	    	 
    	$this->view->disable();
    	 
    	$template_file = $this->basedir."/template/detail/".$template_id.".phtml";
    	$article_file = $this->basedir."/static/detail/html/$template_id/$category_id/$article_id.html";
    	 
    	if(!is_file($template_file)){
    		 
    		$template = Template::findFirst(array(
    				"id = :template_id: AND status = :status:",
    				"bind" => array(
    						'template_id' => $template_id,
    						'status' => 'Enabled'
    				)
    		));
    		 
    		if($template){
    			if(!is_dir(dirname($template_file))){
    				mkdir(dirname($template_file), 0755, TRUE);
    			}
    			file_put_contents($template_file , $template->content);
    		}
    	}    	
    	
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id = :article_id: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'article_id' => $article_id,
    			'visibility' => 'Visible'
    	);
    	$article = Article::findFirst(array(
    			$conditions,
    			"bind" => $parameters
    	));
 

    	
    	$view = new \Phalcon\Mvc\View();
    	$view->setViewsDir($this->basedir.'/template');
    	$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
    	$view->setVar('article',$article);
    	$view->start();
    	$view->render("detail","$template_id");
    	$view->finish();
    	 
    	$content =  $view->getContent();
    	 
    	if(!is_dir(dirname($article_file))){
    		mkdir(dirname($article_file), 0755, TRUE);
    	}
    	file_put_contents($article_file, $content);
    	 
    	print($content);    	
    	
    }
    public function jsonAction($template_id, $category_id, $article_id){

    	$this->view->disable();
    
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND id = :article_id: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'article_id' => $article_id,
    			'visibility' => 'Visible'
    	);
    	$article = Article::findFirst(array(
    			$conditions,
    			"bind" => $parameters
    	));
   		print_r($article);
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

