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
    public function editAction($id){
        $cates = Category::findFirst(
            "id = '{$id}'"
        );
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
        $this->view->setVar('cates',$cates);
    }
    //添加分类页面
    public function addAction(){
        $category = Category::find(
            "division_id = 3"
        );
        $this->view->setVar('pages',$category);
    }
    //查看分类
    public function showAction($id){
        $category = Category::findFirst(
            "id = '{$id}'"
        );
        $this->view->setVar('cates',$category);
    }
    //处理编辑分类
    public function editHandleAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $id = $this->request->getPost('id'); 
        $category = Category::findFirstById($id);
        if(!$category){
            $this->flash->error("Category does not exist");
            return $this->response->redirect("index");
        }
        $form = new CategoryForm();
        $category->name = $this->request->getPost('name');
        $category->division_id = '3';
        $category->visibility = $this->request->getPost('visibility');
        $category->mtime = date("Y-m-d H:i:s",  time());
        $category->parent_id = $this->request->getPost('parent_id');
        $category->description = $this->request->getPost('description');
         if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }
        if ($category->save() == false) {
            foreach ($category->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }
        $form->clear();
        return $this->response->redirect("index");
        
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
        $category->visibility = $this->request->getPost('visibility'); 
        $category->path = '/';
        $category->status = 'Disabled';
//        $category->ctime = date("Y-m-d H:i:s",  time());
        $category->parent_id = $this->request->getPost('parent_id');
        $category->description = $this->request->getPost('description');
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        
        if ($category->save() == false) {
            foreach ($category->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        $form->clear();
        return $this->response->redirect("index");
    }
}

