<?php
class CategoryController extends ControllerBase
{
    public function indexAction(){
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
    }
    //编辑分类页面
    public function editCategoryAction(){
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
    }
    //添加分类页面
    public function addCategoryAction(){
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
    }
    //查看分类
    public function showCategoryAction(){
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
    }
    //处理编辑分类
    public function editHandleAction(){
        
    }
    //处理添加分类
    public function addHandleAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $form = new CategoryForm();
        
        $category = new Category();
        $category->name = $this->request->getPost('name');
        $category->division_id = '3';
        $category->status = $this->request->getPost('status'); 
        $category->path = '/';
        $category->ctime = date("Y-m-d H:i:s",  time());
        $category->parent_id = $this->request->getPost('parent_id');
        $category->description = $this->request->getPost('description');
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('addCategory');
        }
        if ($category->save() == false) {
            foreach ($category->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('addCategory');
        }
        $form->clear();
        return $this->response->redirect("index");
    }
}

