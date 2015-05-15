<?php
class CategoryController extends ControllerBase
{

	public function indexAction()
	{

        
        //print_r($category);
	}
	public function htmlAction($division_id){
		
		$conditions = "division_id = :division_id: AND status = :status:";
		
		$parameters = array(
				"division_id" => $division_id,
				'status' => "Enabled"
		);
		$categorys = Category::find(array(
				$conditions,
    			"bind" => $parameters
		));
// 		foreach ($categorys as $category){
// 			printf("%s, %s", $category->id, $category->name);
// 		}
		$this->view->setVar('categorys',$categorys);
	}
	public function jsonAction($division_id){
		$result = array();
		$this->view->disable();
		
		$conditions = "division_id = :division_id: AND status = :status:";
		
		$parameters = array(
				"division_id" => $division_id,
				'status' => "Enabled"
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