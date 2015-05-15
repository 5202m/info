<?php 
class TemplateController extends ControllerBase
{

	public function indexAction()
	{
		
	}
	public function formAction(){
		
	}
	public function listAction(){
		$appendix = array('pageSize'=>20);
		$where = array();
		$list = Template::getList($where , $appendix);
		$page = $list->getPaginate();
		$page->pageSize = $appendix['pageSize'];
		$this->view->page = $page;
	}
	public function editAction(){
		
		
		 
		$params = array();
		$params['id'] = 3;
		$params['category_id'] = 1;
		$params['name'] = 'test';
		$params['decription'] = 'test1decription';
		$params['content'] = 'test.content';
		$params['status'] = 'N';
		$params['engine'] = 'PHP';
		//$params['ctime'] = '2015-5-15 15:35:29';
		//$params['mtime'] = '2015-5-15 15:35:20';
		

		//var_dump(Template::insert($params));
		
		//exit();
	}

}
?>