<?php
class CategoryController extends ControllerBase
{

	public function indexAction()
	{

        
        //print_r($category);
	}
	public function htmlAction($parent_id){
		
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
		$this->view->setVar('categorys',$categorys);
		
		$this->view->disable();
		
		$basedir = '/www/hx9999.com/inf.hx9999.com';
		$template_dir = $basedir."/template";
		$categroy_html_dir = $basedir."/static/category/html";
		
		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($template_dir);
		$view->setRenderLevel(Phalcon\Mvc\View::LEVEL_LAYOUT);
		$view->setVar('categorys',$categorys);
		$view->start();
		$view->render("category","index");
		$view->finish();
		
		$content =  $view->getContent();
		
// 		if(){
			
// 		}
		
		if(!is_dir($categroy_html_dir)){
			mkdir($categroy_html_dir, 0755, TRUE);
		}
		file_put_contents($categroy_html_dir."/".$parent_id.".html", $content);
		print(time());
		print($content);
		
	}
	public function jsonAction($division_id){
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
	public function purgeAction(){
	}
}
