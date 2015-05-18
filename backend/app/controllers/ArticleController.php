<?php
class ArticleController extends ControllerBase
{

    public function indexAction()
    {
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
		$page = (array)$pages->getPaginate();
		$page['pageSize'] = $appendix['pageSize'];//把每页显示的条数放到数组里传递到view上去
		$page = (object)$page;
		$this->view->page = $page;	
		//$this->view->page->PageSize = $pageSize;
	}
	
	public function editAction($id){
		$article = Article::getOne($id);
		$this->view->page = $article;
		//echo '<pre>';
		//print_r($article);exit;
	}
	
	public function addAction(){
		
	}
	
	public function modifyAction(){
		
	}
	public function moveAction($from, $to){
		
	}
}