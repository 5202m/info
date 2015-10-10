<?php
class TaskController extends ControllerBase
{
    public function indexAction(){
        $datas = Task::find();
        $this->view->setVar('datas', $datas);
    }
    //添加任务
    public function addAction(){
        $group_ids = Group::find();
        $init_message_ids = Message::find("type = 'Email' and status = 'New'");
        $init_template_ids = Template::find("type = 'Email' and status = 'Enabled'");
        
        
        $this->view->setVar('group_ids', $group_ids);
        $this->view->setVar('init_message_ids', $init_message_ids);
        $this->view->setVar('init_template_ids', $init_template_ids);
        
    }
    public function addHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        $form = new TaskForm();
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        $exits_task = Task::findFirst(
           " name = '{$this->request->getPost('name')}'"
        );
        if(!empty($exits_task)){
            echo json_encode(array('status'=>false,'msg'=> '任务名称不能重复'));
            exit;
        }
        $task = new Task();
        $task->name = $this->request->getPost('name');
        $task->type = $this->request->getPost('type');
        $task->group_id = $this->request->getPost('group_id') != ''? $this->request->getPost('group_id') : null;
        $task->template_id = $this->request->getPost('template_id');
        $task->message_id = $this->request->getPost('message_id');
        $task->status = 'New';
        if ($task->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加任务失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加任务成功'));
            exit;
        }
        exit;
    }
    public function deleteAction($id) {
        if ($id) {
            $status = false;
            $task = new Task();
            foreach ($task->find("id='{$id}'") as $item) {
                if ($item->delete() == false) {
                    $status = true;
                } else {
                    
                }
            }
            if ($status == false) {
                return $this->response->redirect("/task/index");
            }
            echo '删除失败';
        }
    }

    //获取不同类型的message_id,template_id
    public function getIdsAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("add");
        }
        $type = $this->request->getPost('type'); 
        $message_ids = Message::find("type = '{$type}'");
        $template_ids = Template::find("type = '{$type}'");
        
        echo json_encode(array(
            'status'=>"true",
            'message_ids'=>$this->objToArray->ohYeah($message_ids),
            'template_ids'=>$this->objToArray->ohYeah($template_ids),
        )) ;
        exit;
    }
    
    public function getDatasAction(){
        if (!$this->request->isPost()) {
            return $this->response->redirect("add");
        }
        $message_ids = $this->request->getPost('message_ids'); 
        $template_ids = $this->request->getPost('template_ids'); 
        
        $this->view->setVar('change_message_ids', $message_ids);
        $this->view->setVar('change_template_ids', $template_ids);
        exit;
    }
}

