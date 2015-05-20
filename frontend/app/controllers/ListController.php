<?php
use Phalcon\Mvc\View, 
	Phalcon\Mvc\Controller;

class ListController extends ControllerBase
{

	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
    public function indexAction()
    {

    }
    public function htmlAction($template_id,$category_id, $limit = 20, $offset = 1){
    
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	$limit 		 = intval($limit);
    	$offset 	 = intval($offset);
    	
    	if(empty($category_id) || empty($template_id)){
    		$this->response->setStatusCode(404, 'Not Found');
    	}
    	
    	if($limit > 100){
    		$limit = 100;
    	}
    	
    	$this->view->disable();
    	
    	$template_file = $this->basedir."/template/list/".$template_id.".phtml";
    	$categroy_file = $this->basedir."/static/list/html/$template_id/$category_id.html";
    	
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
    	
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:";
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'language' => 'cn',
    			'visibility' => 'Visible'
    	);
    	$key = sprintf(":list:html:%s:%s:%s:%s", $template_id,$category_id, $limit, $offset );
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'columns'=>'id,title,author,ctime',
    			"order" => "ctime DESC",
    			'limit' => array('number'=>$limit, 'offset'=>$offset)
    			, "cache" => array("service"=> 'cache', "key" => $key, "lifetime" => 60)
    	));

    	//$this->cache->save('my-data', array(1, 2, 3, 4, 5));
    	//print_r($this->cache->get('my-data')) ;
    	
    	$pages = $this->paginator($category_id, $limit, $offset);
    	
    	$view = new \Phalcon\Mvc\View();
    	$view->setViewsDir($this->basedir.'/template');
    	$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
    	$view->setVar('articles',$articles);
    	$view->setVar('template_id',$template_id);
    	$view->setVar('category_id',$category_id);
    	$view->setVar('limit',$limit);
    	$view->setVar('offset',$offset);
    	$view->setVar('pages',$pages);
    	$view->start();
    	$view->render("list","$template_id");
    	$view->finish();
    	
    	$content =  $view->getContent();
//     	if($content){
// 	    	if(!is_dir(dirname($categroy_file))){
// 	    		mkdir(dirname($categroy_file), 0755, TRUE);
// 	    	}
// 	    	file_put_contents($categroy_file, $content);
//     	}
    	$this->response->setHeader('Cache-Control', 'max-age=60');
    	print($content);
    	
    }
    public function pageAction($category_id, $limit, $offset = 1){
    	$pager = $this->paginator($category_id, $limit, $offset);
    	print_r($pager);
    }
    public function paginator($category_id, $limit, $offset = 1){	
    	$category_id = intval($category_id);
    	if(!$category_id){
    		$this->response->setStatusCode(404, 'Not Found');
    	}
    	
    	$count = Article::count(array(
    			"(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:",
    			'bind' => array(
	    			'category_id' => $category_id,
	    			'division_category_id' => $category_id,
	    			'language' => 'cn',
	    			'visibility' => 'Visible'
    				)
    			));
    	
    	$total 	= ceil($count / $limit);
    	$before = $offset<=$total && $offset > 1?$offset-1:1;
    	$next 	= $offset>=$total?$total:$offset+1;
    	$paginator = array(
    			'count' 	=> $count,
    			'first' 	=> 1,
    			'last' 		=> $total,
    			'before' 	=> $before,
    			'current' 	=> $offset,
    			'next' 		=> $next,
    			'total' 	=> $total
    	); 
    	return ($paginator);
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
    public function jsonAction($category_id, $limit = 20, $offset = 1){
    
    	$category_id = intval($category_id);
    	$limit 		 = intval($limit);
    	$offset 	 = intval($offset);
    	 
    	if(empty($category_id) || empty($template_id)){
    		$this->response->setStatusCode(404, 'Not Found');
    	}
    	 
    	if($limit > 100){
    		$limit = 100;
    	}
    	 
    	$this->view->disable();
    	 
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND language = :language: AND visibility = :visibility:";
    	
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			'language' => 'cn',
    			'visibility' => 'Visible'
    	);
    	$key = sprintf(":list:json:%s:%s:%s", $category_id, $limit, $offset );
    	$articles = Article::find(array(
    			$conditions,
    			"bind" 		=> $parameters,
    			'columns'=>'id,title,author,ctime',
    			"order" 	=> "ctime DESC",
    			'limit' 	=> array('number'=>$limit, 'offset'=>$offset)
    			, "cache" 	=> array("service"=> 'cache', "key" => $key, "lifetime" => 60)
    	));
    	print_r($articles);
    	$result = array();
    	foreach ($articles as $article){
    		//unset($article->status);
    		//unset($article->from);
    		//unset($article->from);
    		$result[]=$article;
    	}
    	$result['pages'] = $this->paginator($category_id, $limit, $offset);
    	
    	$response = new Phalcon\Http\Response();
    	$response->setHeader('Cache-Control', 'max-age=60');
    	$response->setContentType('application/json', 'utf-8');
    	$response->setContent(json_encode($result));
    	return $response;
    }
    
    public function purgeAction(){
    }
}

