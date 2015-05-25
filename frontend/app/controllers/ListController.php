<?php
use Phalcon\Mvc\View, 
	Phalcon\Mvc\Controller;

class ListController extends ControllerBase
{

	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
    public function indexAction()
    {

    }
    public function htmlAction($template_id,$category_id, $limit = 20, $page = 0){
    
    	$template_id = intval($template_id);
    	$category_id = intval($category_id);
    	$limit 		 = intval($limit);
    	$page 	 	= intval($page);
    	
    	$offset 	 = $limit * $page;
    	
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
    		}else{
    			$this->response->setStatusCode(404, 'Template Not Found');
    			echo 'Template Not Found';
    			return;
    		}
    	}
    	
    	$conditions = "(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility:";
    	//language = :language: AND
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_category_id' => $category_id,
    			/*'language' => 'cn',*/
    			'visibility' => 'Visible'
    	);
    	$key = sprintf(":list:html:%s:%s:%s:%s", $template_id,$category_id, $limit, $page );
    	$articles = Article::find(array(
    			$conditions,
    			"bind" => $parameters,
    			'columns'=>'id,title,author,ctime',
    			"order" => "ctime DESC",
    			'limit' => array('number'=>$limit, 'offset'=>$offset)
    			, "cache" => array("service"=> 'cache', "key" => $key, "lifetime" => 60)
    	));

    	if(count($articles) == 0){
    		$this->response->setStatusCode(404, 'Article List Not Found');
    		echo 'Article List Not Found';
    	}else{
    		$pages = $this->paginator($category_id, $limit, $page);
    		
    		$view = new \Phalcon\Mvc\View();
    		$view->setViewsDir($this->basedir.'/template');
    		$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
    		$view->setVar('articles',$articles);
    		$view->setVar('template_id',$template_id);
    		$view->setVar('category_id',$category_id);
    		$view->setVar('limit',$limit);
    		$view->setVar('pagenumber',$pagenumber);
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

    }
    public function pageAction($category_id, $limit, $page = 0){
    	$pager = $this->paginator($category_id, $limit, $page);
    	print_r($pager);
    }
    public function paginator($category_id, $limit, $page = 1){	
    	$category_id = intval($category_id);
    	$limit 		= intval($limit);
    	$page 	= intval($page);

    	if(!$category_id){
    		$this->response->setStatusCode(404, 'Not Found');
    	}
    	
    	$count = Article::count(array(
    			"(category_id = :category_id: OR division_category_id = :division_category_id:) AND visibility = :visibility:",
    			'bind' => array(
	    			'category_id' => $category_id,
	    			'division_category_id' => $category_id,
	    			'visibility' => 'Visible'
    				)
    			));
    	
    	$total 	= ceil($count / $limit)-1;
    	$before = $page<=$total && $page > 1?$page-1:0;
    	$next 	= $page>=$total?$total:$page+1;
    	$paginator = array(
    			'count' 	=> $count,
    			'first' 	=> 0,
    			'last' 		=> $total,
    			'before' 	=> $before,
    			'current' 	=> $page,
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
    public function jsonAction($category_id, $limit = 20, $offset = 0){
    
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
    
    public function purgeAction($template_id,$category_id){
    	$template_id = intval($template_id);
    	$parent_id = intval($parent_id);
    	
    	$template_file = $this->basedir."/template/list/".$template_id.".phtml";
    	
    	unlink($template_file);
    	
    	if($category_id > 0){
    		$categroy_path = $this->basedir."/static/list/html/$template_id/$category_id.html";
    	}else{
    		$categroy_path = $this->basedir."/static/list/html/$template_id/*";
    	}
    	
    	array_map('unlink', glob($categroy_path));
   
    	$this->cache->flush ();
    }
}


