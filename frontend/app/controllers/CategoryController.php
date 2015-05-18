<?php
class CategoryController extends ControllerBase
{

	public $basedir = '/www/hx9999.com/inf.hx9999.com';
	
	public function indexAction()
	{

        
        //print_r($category);
	}
	public function htmlAction($template_id, $parent_id){
		
		if(empty($parent_id) || empty($template_id)){
			echo '404';
		}
		
		$this->view->disable();
		
		$template_file = $this->basedir."/template/category/".$template_id.".phtml";
		$categroy_file = $this->basedir."/static/category/html/$template_id/$parent_id.html";
		
		$template = Template::findFirst(array(
				"category_id = :category_id: AND id = :template_id: AND status = :status:",
				"bind" => array(
						'category_id' => $parent_id,
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
		
		$conditions = 'parent_id = :parent_id: AND visibility = :visibility:';
		
		$parameters = array(
				'parent_id' => $parent_id,
				'visibility' => 'Visible'
		);
		$categorys = Category::find(array(
				$conditions,
    			"bind" => $parameters
		));
// 		foreach ($categorys as $category){
// 			printf("%s, %s", $category->id, $category->name);
// 		}
		
		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($this->basedir.'/template');
		$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
		$view->setVar('categorys',$categorys);
		$view->start();
		$view->render("category","$template_id");
		$view->finish();
		
		$content =  $view->getContent();
		
		if(!is_dir(dirname($categroy_file))){
			mkdir(dirname($categroy_file), 0755, TRUE);
		}
		file_put_contents($categroy_file, $content);
		
		print($content);
		
// 		print(time());
		
	}
	public function jsonAction($parent_id){
		$result = array();
		$this->view->disable();
		
		$conditions = 'parent_id = :parent_id: AND visibility = :visibility:';
		
		$parameters = array(
				'parent_id' => $parent_id,
				'visibility' => 'Visible'
		);
		$categorys = Category::find(array(
				$conditions,
				"bind" => $parameters
		));
		
		foreach ($categorys as $category){
			$result[$category->id]=$category->name;
		}
		$response = new Phalcon\Http\Response();
		$response->setHeader('Cache-Control', 'max-age=60');
		$response->setContentType('application/json', 'utf-8');
		$response->setContent(json_encode($result));
		return $response;
	}
	
	public function pageAction(){
		 
	}
	public function purgeAction($template_id, $parent_id = 0){
		$categroy_dir = $this->basedir."/static/category/html/$template_id/";
		delete($categroy_dir);
	}
}
