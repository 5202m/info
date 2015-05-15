<?php
class ArticleController extends ControllerBase
{

    public function indexAction()
    {
		$this->listAction(1, 10);
    }
    
	public function listAction($page,$pageSize){
    	$appendix['page'] = $page;
    	$appendix['pageSize'] = $pageSize == 0 ? 10 : $pageSize;
		$pages = Article::getList($appendix);
		$page = (array)$pages->getPaginate();
		$page['pageSize'] = $appendix['pageSize'];//把每页显示的条数放到数组里传递到view上去
		$page = (object)$page;
		$this->view->page = $page;	
	}
}