<?php
class VideoController extends ControllerBase{
	
	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
//	public categoryAction(){}
	public function listAction($template_id, $category_id, $limit = 20, $page = 0){
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
    	
    	$template_file = $this->basedir."/template/video/".$template_id.".phtml";
    	$categroy_file = $this->basedir."/static/video/list/$template_id/$category_id.html";
    	
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
    	
    	$conditions = "(category_id = :category_id: OR division_id = :division_id:) AND visibility = :visibility:";
    	//language = :language: AND
    
    	$parameters = array(
    			'category_id' => $category_id,
    			'division_id' => $category_id,
    			/*'language' => 'cn',*/
    			'visibility' => 'Visible'
    	);
    	$key = sprintf(":list:html:%s:%s:%s:%s", $template_id,$category_id, $limit, $page );
    	$videos = Video::find(array(
    			$conditions,
    			"bind" => $parameters,
    			//'columns'=>'id,title,author,ctime',
    			"order" => "ctime DESC",
    			'limit' => array('number'=>$limit, 'offset'=>$offset)
    			, "cache" => array("service"=> 'cache', "key" => $key, "lifetime" => 60)
    	));

    	if(count($videos) == 0){
    		$this->response->setStatusCode(404, 'Video List Not Found');
    		echo 'Video List Not Found';
    	}else{
    		//$pages = $this->paginator($category_id, $limit, $page);
    		
    		$view = new \Phalcon\Mvc\View();
    		$view->setViewsDir($this->basedir.'/template');
    		$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
    		$view->setVar('video',$videos);
    		$view->setVar('template_id',$template_id);
    		$view->setVar('category_id',$category_id);
    		$view->setVar('limit',$limit);
    		$view->setVar('pagenumber',$page);
    		//$view->setVar('pages',$pages);
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
	
	public function playAction($template_id, $category_id, $video_id){
		
	}
}
?>