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
        $dbkey = $this->dbkey;
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
                            $checkLogin = $model->checkLogin($vs,$dbkey);
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
}

