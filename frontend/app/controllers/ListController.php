<?php
use Phalcon\Mvc\View, 
	Phalcon\Mvc\Controller;

class ListController extends ControllerBase
{

	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
    public function indexAction()
    {

    }
    public function htmlAction($template_id,$category_id, $limit = 50, $offset = 0){
    
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	
    	if(empty($category_id) || empty($template_id)){
    		echo '404';
    	}
    	
    	if($limit > 100){
    		$limit = 100;
    	}
    	
    	$this->view->disable();
    	
    	$template_file = $this->basedir."/template/list/".$template_id.".phtml";
    	$categroy_file = $this->basedir."/static/list/html/$template_id/$category_id.html";
    	
    	if(!is_file($template_file)){
    	
    		$template = Template::findFirst(array(
    				"category_id = :category_id: AND id = :template_id: AND status = :status:",
    				"bind" => array(
    						'category_id' => $category_id,
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
    	
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'language' => 'cn',
    			'visibility' => 'Visible'
    	);
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'limit' => $limit
    	));
//     	print_r($articles);

    	$view = new \Phalcon\Mvc\View();
    	$view->setViewsDir($this->basedir.'/template');
    	$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
    	$view->setVar('articles',$articles);
    	$view->setVar('template_id',$template_id);
    	$view->setVar('category_id',$category_id);
    	
    	$view->start();
    	$view->render("list","$template_id");
    	$view->finish();
    	
    	$content =  $view->getContent();
    	
    	if(!is_dir(dirname($categroy_file))){
    		mkdir(dirname($categroy_file), 0755, TRUE);
    	}
    	file_put_contents($categroy_file, $content);
    	
    	print($content);
    	
    }
    public function rssAction($template_id,$category_id, $limit, $offset){
    
    	if($limit > 100){
    		$limit = 100;
    	}
    	$conditions = "category_id = :category_id: AND language = :language: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'language' => 'cn',
    			'visibility' => 'Visible'
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
    
    	$conditions = "category_id = :category_id: AND language = :language: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'language' => 'cn',
    			'visibility' => 'Visible'
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

