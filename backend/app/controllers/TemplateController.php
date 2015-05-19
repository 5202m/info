<?php 
class TemplateController extends ControllerBase
{

	public function indexAction()
	{
		var_dump($this);
		exit();
	}
	
	public function listAction($page = 1 , $pageSize = 10){
		$search_key = 'template_list_search';
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$this->session->set($search_key, $params);
		}
		$where = array();
		if($this->session->has($search_key)){
			$where = $this->session->get($search_key);
			$this->view->where  = $where;
			foreach($where as $k=>$v){
				if(empty($v)){
					unset($where[$k]);
				}
			}
		}
		
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
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
	public function deleteAction($id = 0){
		if($id){
			$template = new Template();
			if($template->find($id)){
				$template->id = $id;
				if($template->delete()){
					return $this->response->redirect("/template/list");
				}
			}
			
		}
	}
}
?>