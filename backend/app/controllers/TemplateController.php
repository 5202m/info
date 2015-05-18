<?php 
class TemplateController extends ControllerBase
{

	public function indexAction()
	{
		
	}
	
	public function listAction($page = 1 , $pageSize = 5){
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
		$where = array();
		$list = Template::getList($this->modelsManager , $where , $appendix);
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