<?php
class ArticleController extends ControllerBase
{

    public function indexAction(){
		$this->listAction(1, 10);
    }
    
	public function listAction($page,$pageSize){
		
		/*$builder = $this->modelsManager->createBuilder()
		->columns('article.*,c.name')
		->from('article')
		->leftjoin('category', 'article.category_id = c.id or article.division_category_id=c.division_id','c')
		->getQuery()
		->execute();
		
		$paginator = new Phalcon\Paginator\Adapter\QueryBuilder(array(
			"builder" => $builder,
			"limit"=> $pageSize,
			"page" => $page,
		)); 
		
		$this->view->page = $paginator;*/
		//echo '<pre>';
		//print_r($paginator);exit;
    	$appendix['page'] = $page;
    	$appendix['pageSize'] = $pageSize == 0 ? 10 : $pageSize;
		$pages = Article::getList($appendix);
		$page = $pages->getPaginate();
		//$page['pageSize'] = $appendix['pageSize'];//把每页显示的条数放到数组里传递到view上去
		//$page = (object)$page;
		$this->view->page = $page;
		$this->view->pageSize = $appendix['pageSize'];//把每页显示的条数放到数组里传递到view上去
		//$this->view->page->PageSize = $pageSize;
	}
	
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
            /*echo '<pre>';
            print_r($article);exit;*/
		}
		$divisionCategory = Category::find(
            "division_id = 3"//该值应该是根据登录用户所在的事业部的id
        );
        $this->view->divisionCategory = $divisionCategory;
		$this->view->article = $article;
		$this->view->images = $images;
		//echo '<pre>';
		//print_r($article);exit;
	}
	
	/**
	 * 添加文章
	 * Enter description here ...
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
		
		$divisionCategory = Category::find(
            "division_id = 3"//该值应该是根据登录用户所在的事业部的id
        );
        $this->view->divisionCategory = $divisionCategory;
	}
	
	public function moveAction($from, $to){
		
	}
	
	/**
	 * 上传图片
	 * Enter description here ...
	 */
	private function uploadImage($articleId, $oriPath = ''){
		$image = '';
		$savePath = dirname(dirname(dirname(dirname(__FILE__)))).'/images/'.$articleId.'/';
		$returnPage = 'images/'.$articleId.'/';
		//var_dump($savePath);exit;
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
}