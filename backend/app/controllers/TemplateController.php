<?php 
class TemplateController extends ControllerBase
{
	public function initialize(){
		$this->division_id = 3 ;//Division::getID();
		$this->view->division_id = $this->division_id;
		
	}
	public function indexAction()
	{
		$search_key = 'template_list_search';
		$this->session->remove($search_key);
		$this->listAction(1,10);
		$this->view->partial('template/list');
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
		$where['division_id'] = $this->division_id;
		
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
		$list = Template::getList($this->modelsManager , $where , $appendix);
		$page = $list->getPaginate();
		
		$page->pageSize = $appendix['pageSize'];
		$this->view->page = $page;
	}
	public function editAction($id = 0){
		
		if(isset($this->templateDir->template_list)){
			$this->view->template_list = $this->templateDir->template_list;
		}else{
			$this->view->message_info = array('默认模板配置不存在');
		}
		
		if($this->request->isPost()){
			$params = $this->request->getPost();
			$last_id = Template::insert($params);
			$isError = true ;
			if(is_numeric($last_id)){
				$message_info = array('success'=>isset($params['id']) ? '修改成功' : '添加成功');
				$isSuccess = false ; 
			}else{
				$message_info = $last_id;
			}
			if(isset($params['ajax']) && $params['ajax']==1){
				echo json_encode(array('status'=>$isError,'msg'=> !isset($message_info['success']) ?   '添加失败' : implode(',', $message_info)));
				$this->view->disable();
			
			}else{
				$this->view->message_info = message_info;
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
	public function previewAction($template_id = 0, $category_id = 0){
		$message_info = array();
		$preview_url= '';
		if(!is_numeric($template_id)){
			$message_info[]='分类不存在';
		}
		if(!is_numeric($template_id)){
			$message_info[]='模板不存在 ';
			
		}elseif($template_id){
			
			$info = Template::findFirst("id={$template_id}");
			if(isset($info->type)){
				$type = strtolower($info->type);
				if(isset($this->templateDir->preview->$type)){
					$preview_url = $this->templateDir->preview->$type;
				}else{
					$message_info[] = array('模板预览配置不存在');
				}
				$this->view->type = $info->type;
			}else{
				$message_info[] = '无效的模板';
			}
		}
		$this->view->url = $preview_url;
		if($message_info){
			$this->view->message_info = $message_info;
		}
		
		$this->view->getData = array('template_id'=>$template_id,'category_id'=>$category_id);
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
	public function categoryAction($type='category'){
		
		if($this->request->isPost()){
			$msg = null;
			$params = $this->request->getPost();
			
			if(isset($params['category_id']) && is_numeric($params['category_id'])){
				if(isset($params['template_id']) && is_array($params['template_id'])){
					
					try {
						$transactionManager = new \Phalcon\Mvc\Model\Transaction\Manager();
						$transaction = $transactionManager->get();
						$hasErrors = 0 ;
						$isCommit = false;
						$i=0;
						foreach($params['template_id'] as $k=>$v){
							$category_ht = new CategoryHasTemplate;
							$category_ht->setTransaction($transaction);
							$category_ht->category_id = $params['category_id'];
							if($hasErrors === 0){
								$category_ht->template_id = $v;
								if(!$category_ht->count("template_id='{$category_ht->template_id}' AND category_id='{$category_ht->category_id}'")){
									if(!$category_ht->save()){
										$hasErrors +=1 ;
									}else{
										$isCommit =  true ;
										$i+=1;
									}
								}else{
									$msg = '关联已存在 ';
								}
							}
						}
						if(count($params['template_id']) === $hasErrors){
							$isCommit = false;
						}elseif($hasErrors){
							$msg = '忽略已经关联的模板 ';
						}
						
						if($isCommit === true){
							 $msg = $msg.($transaction->commit() ? $i.'模板关联成功' : '模板关联失败');
							
						}else{
							$category_ht->getMessages();
							$msg = $msg ? $msg : $category_ht->getMessages();
							if(isset($category_ht->id)){
								$transaction->rollback();
							}
						}
					}catch(Phalcon\Mvc\Model\Transaction\Failed $e){
					  	$msg = $msg ? $msg : $e->getMessage();
					}
					
				}else{
					$hasErrors = 1 ;
					$msg = '模板不存在';
				}
			}else{
				$hasErrors = 1 ;
				$msg = '分类不存在';
			}
			if(isset($params['ajax']) && $params['ajax']==1){
				$this->view->disable();
				$list = null;
				if(isset($params['list'])){
					$list = categorylistAction();
				}
				
				echo json_encode(array('status'=>$hasErrors ? true : false ,'list'=>$list,'msg'=>$msg));
				return ;
			}
		}
		$this->view->type = strtoupper($type);
	}
	public function categorylistAction($page = 1 , $pageSize = 1000 ,$isPartView = false){
		
		$where = array();
		$appendix = array('page'=>$page,'pageSize'=>$pageSize);
		$this->view->list =  CategoryHasTemplate::getList($this->db , $where , $appendix);
		
		
		if($isPartView){
			$this->view->partial('template/categorylist');
			$this->view->isPartView = 1;
			$this->view->disable();
		}
	}
	public function ajaxtemplateAction(){
		
		
		if($this->request->isPost()){
			$category_id = $this->request->getPost('category_id'); 
			$template_id = $this->request->getPost('template_id'); 
			$sql="SELECT id,name FROM template WHERE id NOT 
					in(SELECT template_id FROM category_has_template WHERE category_id = '{$category_id}')";
			$list = $this->db->fetchAll($sql,PDO::FETCH_ASSOC);
			$new_list = array();
			foreach($list as $k=>$v){
				$new_list[$v['id']]=$v['name'];
			}
			$this->view->list = $new_list;
			
		}
		$this->view->partial('template/ajaxtemplate');
		
		$this->view->disable();
	}
	public function deleteHasCategoryAction($id = 0){
		$status = false;
		$msg = 0;
		if($id){
			$category_hx = new CategoryHasTemplate();
			if($category_hx->count($id)){
					$category_hx->id = $id;
					if($category_hx->delete()){
						$msg = '关联删除成功';
					}else{
						$status = true ; 
						$msg = '关联删除失败';
					}
			}else{
				$status = true ;
				$msg = '关联已经删除或不存在';
			}
			if($this->request->getPost('ajax')==1){
				$this->view->disable();
				echo json_encode(array('status'=>$status,'msg'=>$msg));
				return ;
			}
			return $this->response->redirect("/template/category");
		}
	}
	
	public function contentAction($id = 0){
	
		if($id){
			$info = Template::findFirst('id='.$id);
			echo $info->content;
			$this->view->disable();
		}
		exit();
	}
}
?>