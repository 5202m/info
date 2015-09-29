<?php
class ContactController extends ControllerBase
{
    public function indexAction(){
        
//        $phql = "select *,AES_DECRYPT(mobile,'{$this->dbkey}') as new_mobile,AES_DECRYPT(email,'{$this->dbkey}') as new_email from  contact";
        $contact = Contact::find( );
//        $contact = $this->modelsManager->executeQuery($phql);
        $this->view->setVar('contact',$contact);
    }
    //导入联系人
    public function uploadAction($format){
        if($format == 1){
            $file = '../images/contact.list.import.csv';
            header('Content-Description: File Transfer');   
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        }
    }
    public function uploadHandleAction($group_id){
        $new_data = array();
        $dbkey = $this->config->database->key;
        $mb = 1.4; //Mb
        $maxFileSize = $mb * 1024 * 1024;
        if ($_FILES['file']) {
            $failed_username_arr = array();
            $file = (object) $_FILES['file'];
            if ($file->error == 0) {
                if (file_exists($file->tmp_name)) {
                    // 文件扩展名检查
                    $ext = preg_replace("/.*\.([^\.]+)$/", "\$1", $file->name);
                    $ext = strtolower($ext);
                    // 文件大小检查
                    if ($file->size > $maxFileSize) {
                        echo "'Sorry,只能上传文件大小为'.$mb.'MB以内的文件(当前文件大小为<u>' .
                        round($file->size/1024/1024,2) . 'MB</u>)'";
                        die();
                    }
                    //end
                    $column = 3; //csv列数
                    $row = 0;
                    $sum = 0; //总金额
                    $handle = fopen($file->tmp_name, "r");
                    $dataArray = array();
                    while (($filedata = fgetcsv($handle, 100000, ",")) !== false) {
                        $num = count($filedata);
                        if ($row == 0) {
                            $row++;
                            continue;
                        }
                        $dataArray[] = $filedata;
                    }
                    fclose($handle);
                    $model = new Contact();
                    if (!empty($dataArray)) {
                        foreach ($dataArray as $key => $vs) {
                            $time = date('Y-m-d H:i:s', time());
                            $checkLogin = $model->checkLogin($action='upload',$vs,$dbkey);
                            if (!empty($checkLogin)) {
                                continue;
                            }
                            $phql = "INSERT INTO contact (name, mobile, email, mobile_digest, email_digest, status, ctime) VALUES ('{$vs[0]}', AES_ENCRYPT('{$vs[1]}','{$dbkey}'), AES_ENCRYPT('{$vs[2]}','{$dbkey}'), md5('{$vs[1]}'), md5('{$vs[2]}'), 'Subscription', '{$time}')";
//                        echo $phql;die;
                            $result = $this->modelsManager->executeQuery($phql);
                            if ($result->success() == false) {
                                foreach ($result->getMessages() as $message) {
                                    echo $message->getMessage();
                                }
                            }
                            $insertId = $model->getWriteConnection()->lastInsertId($model->getSource());
                        }
                        if(isset($insertId)){
                            $groupHasContact = new GroupHasContact();
                            $groupHasContact->group_id = $group_id;
                            $groupHasContact->contact_id = $insertId;
                            $groupHasContact->save();
                            if(isset($groupHasContact->id)){
                                echo "成功导入";
                                die(); 
                            }
                        }else{
                            echo "Sorry,导入失败,请检查手机号码或邮箱是否重复";
                            die();
                        }
                    } else {
                        echo "Sorry,文件中的数据不合法3";
                        die();
                    }
                } else {
                    echo "Sorry,文件中的数据不合法3";
                    die();
                }
            }
        }

        $this->view->disable();
    }
    public function addAction(){
        
    }
    public function addHandleAction(){
        
        $dbkey = $this->config->database->key;
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
        
        $form = new ContactForm();
        $contact = new Contact();
        $datas = array();
        $datas['name'] = $this->request->getPost('name');
        $datas['email'] = $this->request->getPost('email');
        $datas['mobile'] = $this->request->getPost('mobile');
        $datas['description'] = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        $checkLogin = $contact->checkLogin($action='add',$datas,$dbkey);
        if (!empty($checkLogin)) {
            echo json_encode(array('status'=>false,'msg'=> '添加的手机号或邮件重复'));
            exit;
        }else{
            $phql = "INSERT INTO contact (name, mobile, email, mobile_digest, email_digest, description, status) VALUES ('{$datas['name']}', AES_ENCRYPT('{$datas['mobile']}','{$dbkey}'), AES_ENCRYPT('{$datas['email']}','{$dbkey}'), md5('{$datas['mobile']}'), md5('{$datas['email']}'), '{$datas['description']}', 'Subscription')";
            $result = $this->modelsManager->executeQuery($phql);
            if ($result->success() == false) {
                foreach ($result->getMessages() as $message) {
                    echo $message->getMessage();
                }
            }
            $insertId = $contact->getWriteConnection()->lastInsertId($contact->getSource());
            if (isset($insertId)) {
                echo json_encode(array('status'=>false,'msg'=> '添加联系人成功'));
                exit;
            }else{
                echo json_encode(array('status'=>false,'msg'=> '添加联系人失败'));
                exit;
            }
        }
        exit;
    }
    public function editAction($id){
        $dbkey = $this->config->database->key;
        $phql = "SELECT id,name,AES_DECRYPT(mobile,'{$dbkey}') as mobile,AES_DECRYPT(email,'{$dbkey}') as email,status,description FROM Contact where id = '{$id}'";
        $contact = $this->modelsManager->executeQuery($phql);
        $contact_arr = $this->objToArray->ohYeah($contact);
        $this->view->setVar('contact',$contact_arr);
    }
    public function editHandleAction(){
        $dbkey = $this->config->database->key;
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("index");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("index");
        }
         
        $contact = new Contact();
        $id = $this->request->getPost('id'); 
        $datas['name'] = $this->request->getPost('name');
        $datas['email'] = $this->request->getPost('email');
        $datas['mobile'] = $this->request->getPost('mobile');
        $datas['status'] = $this->request->getPost('status');
        $datas['description'] = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
        $datas['mobile_digest'] = md5($datas['mobile']);
        $datas['email_digest'] = md5($datas['email']);
        
        $phql = "SELECT id,name,AES_DECRYPT(mobile,'{$dbkey}') as mobile,AES_DECRYPT(email,'{$dbkey}') as email,status,description FROM Contact where id = '{$id}'";
        $contact_data = $this->modelsManager->executeQuery($phql);
        $contact_arr = $this->objToArray->ohYeah($contact_data);
        if($contact_arr[0]['name'] == $datas['name'] && $contact_arr[0]['description'] == $datas['description'] && $contact_arr[0]['mobile'] == $datas['mobile'] && $contact_arr[0]['email'] == $datas['email'] && $contact_arr[0]['status'] == $datas['status']){
            echo json_encode(array('status'=>false,'msg'=> '未做任何修改'));
            exit;
        }
        if(empty($contact_arr)){
            $this->flash->error("Contact does not exist");
            return $this->response->redirect("index");
        }
        $form = new ContactForm();
        
         if (!$form->isValid($_POST)) {
             
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('edit');
        }
        
        $phql = "UPDATE Contact SET name = '{$datas['name']}' , mobile = AES_ENCRYPT('{$datas['mobile']}','{$dbkey}') ,email = AES_ENCRYPT('{$datas['email']}','{$dbkey}'),"
        . "mobile_digest = '{$datas['mobile_digest']}',email_digest = '{$datas['email_digest']}',status = '{$datas['status']}',description = '{$datas['description']}' WHERE id = '{$id}'";


        $result = $this->modelsManager->executeQuery($phql);
        if ($result->success() == false) {
            echo json_encode(array('status'=>false,'msg'=> '修改联系人失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '修改联系人成功'));
            exit;
        }
        
        
        
        
        exit;
    }
}

