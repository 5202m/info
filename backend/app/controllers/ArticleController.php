<?php
class ArticleController extends ControllerBase
{

    public function indexAction(){
		/*$action = $this->request->getQuery('action');
		if($action=='list'){
			$this->removeSearchSession();
		}*/
		$this->listAction(1, 10);
    }
    
	public function listAction($page=1, $pageSize=10){
		$where = ' 1 ';
		$condition = array();
		if($this->request->isPost()){
			$title = $this->request->getPost('title', 'trim');
			$language = $this->request->getPost('language');
			$division_category_id = $this->request->getPost('division_category_id');
			$visibility = $this->request->getPost('visibility');
			$share = $this->request->getPost('share');
			$this->session->set('title', $title);
			$this->session->set('language', $language);
			$this->session->set('division_category_id', $division_category_id);
			$this->session->set('share', $share);
			$this->session->set('visibility', $visibility);
		}
		if($this->session->has("title")){
			$title = $this->session->get('title');
			$condition['title'] = $title;
			if(!empty($title)){
				$where .= " and article.`title` like '%{$title}%' ";
			}
		}
		if($this->session->has("language")){
			$language = $this->session->get('language');
			$condition['language'] = $language;
			if(!empty($language)){
				$where .= " and article.`language` = '{$language}' ";
			}
		}
		if($this->session->has("division_category_id")){
			$division_category_id = $this->session->get('division_category_id');
			$condition['division_category_id'] = $division_category_id;
			if(!empty($division_category_id)){
				$where .= " and article.`division_category_id` = '{$division_category_id}' ";
			}
		}
		if($this->session->has("share")){
			$share = $this->session->get('share');
			$condition['share'] = $share;
			if(!empty($share)){
				$where .= " and article.`share` = '{$share}'";
			}
		}
		if($this->session->has("visibility")){
			$visibility = $this->session->get('visibility');
			$condition['visibility'] = $visibility;
			if(!empty($visibility)){
				$where .= " and article.`visibility` = '{$visibility}' ";
			}
		}
		if($condition){
			$condition = $this->arrayToObj->tran($condition);
		}
		$appendix = array('page'=>$page, 'pageSize'=>$pageSize, 'order'=>'article.id desc');
		$list = Article::getList($this->modelsManager , $where , $appendix);
		$page = $list->getPaginate();
		//echo '<pre>';print_r($page);exit;
		$divisionCategory = $this->getDivisionCategory();
        
        $this->view->searchData = $condition;
        $this->view->divisionCategory = $divisionCategory;
		$page->pageSize = $appendix['pageSize'];
		$this->view->page = $page;
	}
	
	/**
	 * 修改文章
	 * Enter description here ...
	 * @param int $id 文章ID
	 */
	public function editAction($id){
		$parameters = array(
            "id = ?0",
            "bind" => array($id)
        );
		$article = Article::findFirst($parameters);
		$parameters = array(
            "article_id = ?0",
            "bind" => array($id)
        );
		$images = Images::findFirst($parameters);
		if($this->request->isPost()){
			$article->id = $this->request->getPost('id');
			$article->title = $this->request->getPost('title', 'trim');
			$article->content = $this->request->getPost('arcontent', 'trim');
			$oriPath = '';
			$imagesId = 0;
			$imageVal = $this->request->getPost('hdimage', 'trim');
			if($imageVal){
				$image = explode(';', $imageVal);
				if($image){
					$oriPath = $image[0];
					$imagesId = $image[1];
				}
			}
			if($images){
				$images->id = $imagesId;
				$images->article_id = $article->id;
				$images->url = $this->uploadImage($article->id, $oriPath);
				$images->save();
			}
			else{
				$images = new Images();
				$images->article_id = $article->id;
				$images->url = $this->uploadImage($article->id, $oriPath);
				$images->save();
			}
            $article->keyword = $this->request->getPost('keyword', 'trim');
            $article->description = $this->request->getPost('description', 'trim');
            $article->language = $this->request->getPost('language');
            $article->visibility = $this->request->getPost('visibility');
            //$category_id = $this->request->getPost('category_id');
            //$article->status = $this->request->getPost('status');
            $article->division_category_id = $this->request->getPost('division_category_id');
            $article->author = $this->request->getPost('author', 'trim');
            $article->share = $this->request->getPost('share');
            if($article->save()){
            	$this->response->redirect('/article');
            }
            
            foreach ($article->getMessages() as $message) {
                $this->flash->error($message);
            }
		}
		$divisionCategory = $this->getDivisionCategory();
        $this->view->divisionCategory = $divisionCategory;
		$this->view->article = $article;
		$this->view->images = $images;
	}
	
	/**
	 * 添加文章
	 */
	public function createAction(){
		if ($this->request->isPost()) {
			//$this->db->begin();
			
			$article = new Article();
			$article->title = $this->request->getPost('title', 'trim');
			$article->content = $this->request->getPost('arcontent', 'trim');
            $article->keyword = $this->request->getPost('keyword', 'trim');
            $article->description = $this->request->getPost('description', 'trim');
            $article->language = $this->request->getPost('language');
            $article->visibility = $this->request->getPost('visibility');
            //$category_id = $this->request->getPost('category_id');
            //$article->status = $this->request->getPost('status');
            $article->division_category_id = $this->request->getPost('division_category_id');
            $article->author = $this->request->getPost('author', 'trim');
            $article->share = $this->request->getPost('share');
            if($article->save()){
            	/*$this->db->rollback();
	            foreach ($article->getMessages() as $message) {
	                $this->flash->error($message);
	            }
            	return;*/
            	$images = new Images();
				$images->article_id = $article->id;
				$images->url = $this->uploadImage($article->id, $oriPath);
				$images->save();
				
				$this->response->redirect('/article');
            }
            
            /*$images = new Images();
			$images->article_id = $article->id;
			$images->url = $this->uploadImage($article->id, $oriPath);
			
			if($images->create() == false){
				$this->db->rollback();
				foreach ($images->getMessages() as $message) {
	                $this->flash->error($message);
	            }
            	return;
			}*/
			//$this->db->commit();
            //$this->response->redirect('/article');
            foreach ($article->getMessages() as $message) {
                $this->flash->error($message);
            }
		}
		
		$divisionCategory = $this->getDivisionCategory();
        $this->view->divisionCategory = $divisionCategory;
	}
	
	public function moveAction($from, $to){
		
	}
	
	/**
	 * 上传图片
	 * @param int $articleId 文章ID
	 * @param string $oriPath 原图片路径
	 */
	private function uploadImage($articleId, $oriPath = ''){
		$image = '';
		$savePath = dirname(dirname(dirname(dirname(__FILE__)))).'/images/'.$articleId.'/';
		$returnPage = 'images/'.$articleId.'/';
		if(!file_exists($savePath)){
			mkdir($savePath, 0777);
		}
		//Check if the user has uploaded files
		if ($this->request->hasFiles() == true) {
			//Print the real file names and their sizes
			foreach ($this->request->getUploadedFiles() as $file){
				//echo $file->getName(), " ", $file->getSize(), "\n";
				if($file->isUploadedFile()){
					if($file->moveTo($savePath.$file->getName())){
						$image = $returnPage.$file->getName();
						if($oriPath!=''){
							unlink($savePath.$oriPath);
						}
					}
					else{
						//echo 'false';
					}
				}
				else{
					//echo 'can not upload';
				}
			}
		}
		else{
			if($oriPath){
				$image = $oriPath;
			}
		}
		return $image;
	}
	
	/**
	 * 获取事业部分类
	 * Enter description here ...
	 */
	private function getDivisionCategory(){
		$divisionId = Division::getID();
		return $divisionCategory = Category::find(
            "division_id = {$divisionId}"
        );
	}
	
	/**
	 * 移除提交的查询条件
	 * Enter description here ...
	 */
	private function removeSearchSession(){
		$this->session->remove("title");
		$this->session->remove("language");
		$this->session->remove("division_category_id");
		$this->session->remove("share");
		$this->session->remove("visibility");
	}
}