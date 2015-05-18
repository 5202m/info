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
	public function editAction($id = 0){
		
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$last_id = Template::insert($params);
			if(is_numeric($last_id)){
				$this->view->message_info = array('success'=>$id ? '修改成功' : '添加成功');
				$id = $last_id;
			}else{
				$this->view->message_info = $last_id;
			}
		}
		if($id>0){
			$info = Template::findFirst("id={$id}");
		}
		if(isset($info) && $info){
			$this->view->info = $info;
		}else{
			$this->view->info = Template::defaultObject();
		}
		
	}
	public function previewAction(){
		
	}
}
?>