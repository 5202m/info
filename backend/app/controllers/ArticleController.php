<?php
class ArticleController extends ControllerBase
{

	/**
	 * 默认页，显示文章列表
	 */
    public function indexAction(){
		$this->removeSearchSession();
		$this->listAction(1, 10);
    }
    
    /**
     * 文章列表
     * @param $page
     * @param $pageSize
     */
	public function listAction($page=1, $pageSize=10){
		$search_key = 'article_list_search';
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
		$appendix = array('page'=>$page, 'pageSize'=>$pageSize, 'order'=>'article.id desc');
		
		/*$builder = $this->modelsManager->createBuilder()
					->columns("article.*,category.name cname")//连接查询的表中有相同名称的字段不能使用as别名的方式，否则用相同名称的字段进行条件筛选时会报错
					->from("article");
		$strWhere = null;
		if($where){
			foreach($where as $k=>$v){
				if($k=='title'){
					$strWhere[]  =  "article.{$k} LIKE  '%{$v}%'";
				}else{
					$strWhere[]  =  "article.{$k} = '{$v}'";
				}
			}
			$strWhere = implode(' AND ', $strWhere);
		}
		$builder = $builder->where($strWhere)
					->leftjoin("category", "category.id = article.division_category_id")
					->orderby($appendix['order']);
		//echo '<pre>';print_r($builder);exit;
	    $paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			    "builder" => $builder,
			    "limit"=> $pageSize,
				"page" => $page
			));*/
		$list = Article::getList($this->modelsManager , $where , $appendix);
		$page = $list->getPaginate();
		//$page = $paginator->getPaginate();
		//echo '<pre>';print_r($page);exit;
		$divisionCategory = $this->getDivisionCategory();
        
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
			//$form = new ArticleForm();
			$article->id = $this->request->getPost('id');
			$article->title = $this->request->getPost('title', 'trim');
			$article->content = $this->request->getPost('content', 'trim');
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
            /*if(!$form->isValid($this->request->getPost())){
	            foreach ($form->getMessages() as $message) {
	                $this->flash->error($message);
	            }
	            $this->response->redirect('/article/edit/'.$id);
            }*/
            if($article->save()){
            	//$form->clear();
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
			//$form = new ArticleForm();
			$article = new Article();
			$article->title = $this->request->getPost('title', 'trim');
			$article->content = $this->request->getPost('content', 'trim');
            $article->keyword = $this->request->getPost('keyword', 'trim');
            $article->description = $this->request->getPost('description', 'trim');
            $article->language = $this->request->getPost('language');
            $article->visibility = $this->request->getPost('visibility');
            //$category_id = $this->request->getPost('category_id');
            //$article->status = $this->request->getPost('status');
            $article->division_category_id = $this->request->getPost('division_category_id');
            $article->author = $this->request->getPost('author', 'trim');
            $article->share = $this->request->getPost('share');
			/*if(!$form->isValid($this->request->getPost())){
	            foreach ($form->getMessages() as $message) {
	                $this->flash->error($message);
	            }
	            $this->response->redirect('/article/create');
            }*/
            
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
				
            	//$form->clear();
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
	 * 删除文章
	 */
	public function deleteAction(){
		if($this->request->isAjax()){
			$ids = $this->request->getPost('ids', 'trim');
			if(!empty($ids)){
				$status = Article::deleteArticle($this->modelsManager, $ids);
				$response = new Phalcon\Http\Response();
				if ($status->success() == true) {
			        $response->setJsonContent(array('status' => true));
			    } else {
			        //Change the HTTP status
			        $response->setStatusCode(409, "Conflict");
			        $errors = array();
			        foreach ($status->getMessages() as $message) {
			            $errors[] = $message->getMessage();
			        }
			        $response->setJsonContent(array('status' => false, 'messages' => $errors));
			    }
			    return $response;
			}
		}
	}
	
	/**
	 * 上传图片
	 * @param int $articleId 文章ID
	 * @param string $oriPath 原图片路径
	 */
	private function uploadImage($articleId, $oriPath = ''){
		$image = '';
		$savePath = $this->imagesPath.$articleId.'/';//dirname(dirname(dirname(dirname(__FILE__)))).'/images/'.$articleId.'/';
		$returnPage = 'images/'.$articleId.'/';
    	if(php_uname('s')=='Windows NT'){
    		$savePath = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/'.$articleId.'/';
    	}
		if(!file_exists($savePath)){
			mkdir($savePath, 0777, true);
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
		$search_key = 'article_list_search';
		$this->session->remove($search_key);
	}
}