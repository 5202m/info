<?php
class CategoryController extends ControllerBase
{

	public function indexAction()
	{

        
        //print_r($category);
	}
	public function htmlAction($division_id){
		$categorys = Category::find("division_id = $division_id");
		foreach ($categorys as $category){
			printf("%s, %s", $category->id, $category->name);
		}
	}
	public function jsonAction($division_id){
		$result = array();
		$this->view->disable();
		$categorys = Category::find("division_id = $division_id");
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