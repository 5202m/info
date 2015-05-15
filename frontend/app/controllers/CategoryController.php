<?php
class CategoryController extends ControllerBase
{

	public function indexAction()
	{
		echo 'asdfasf';
		exit();
        $category = Category::find(
            "division_id = 3"
        );
        print_r($category);
	}
	public function pageAction(){
		 
	}
	public function purgeAction(){
	}
}