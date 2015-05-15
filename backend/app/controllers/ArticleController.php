<?php
class ArticleController extends ControllerBase
{

    public function indexAction()
    {
    	$currentPage = $this->request->getQuery('page', 'int');
    	$pageSize = $this->request->getQuery('pagesize', 'int');
    	$appendix['page'] = $currentPage;
    	$appendix['pageSize'] = $pageSize == 0 ? 10 : $pageSize;
		$pages = Article::getList($appendix);
		$page = $pages->getPaginate();
		$this->view->page = $page;
		//print_r($page->items);exit;
    }

}