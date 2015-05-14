<?php

class CategoryController extends ControllerBase
{
    public function indexAction(){
        $currentPage = (int) $_GET["page"];
        
        
//        $category = self::list_cate($parent_id = 0);
        
        $category = Category::find(
            "division_id = 3"
        );
        
        $paginator = new \Phalcon\Paginator\Adapter\Model(
            array(
                "data" => $category,
                "limit"=> 10,
                "page" => $currentPage
            )
        );

        // Get the paginated results
        $page = $paginator->getPaginate();
        $this->view->setVar('pages',$page);
    }
}

