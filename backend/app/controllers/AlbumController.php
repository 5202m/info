<?php

class AlbumController extends ControllerBase
{
    public function initialize() {
        parent::initialize();
        $connection = new MongoClient( "mongodb://neo:chen@192.168.6.1/test" );
        $this->mongodb = $connection->selectDB('test');
    }

    public function indexAction()
    {
	
    }
    public function get($folder, $filename){
		
		//$filename ='test.jpg';
		$grid = $this->mongodb->getGridFS($folder);
		//echo $grid->storeFile($filename, array("date" => new MongoDate()));
                
                $image = $grid->findOne($filename);
		if ($image) {
			$image_file = sprintf("%s/static/image/%s", $this->basedir, $filename);
			$content = $image->getBytes();
			if(!is_dir(dirname($image_file))){
    			mkdir(dirname($image_file), 0755, TRUE);
    		}
			file_put_contents($image_file , $content);
//                        exit();
	    	$this->response->setHeader('Cache-Control', 'max-age=60');
			$this->response->setHeader('Content-type', mime_content_type($image_file));
			$this->response->setContent($content);
			echo $content;
			
		}else{
			$this->response->setStatusCode(404, 'Image Not Found');
			$this->response->setContent('Image Not Found');
		}
		return($this->response);
	}
    public function imageAction($folder, $filename, $ver = 0){
    
    	//$filename = intval($filename);
    	$ver = intval($ver);
    	
		
		$this->get($folder, $filename);
                $this->view->disable();
		
    }
    
    
    public function folderAction($page_num){
        $currentPage = (int)$page_num;
         $folder = Album::find();
        $paginator = new Phalcon\Paginator\Adapter\Model(
        array(
                "data" => $folder,
                "limit"=> 3,
                "page" => $currentPage
            )
        );
        $page = $paginator->getPaginate();
       
        $this->view->setVar('page',$page);
    }
    public function addAction(){
        
    }
    public function addHandleAction(){
        $setoff = $this->request->getPost('setoff');
        if($setoff == "setoff"){
            return $this->response->redirect("folder");
        }
        if (!$this->request->isPost()) {
            return $this->response->redirect("folder");
        }
        $form = new AlbumForm();
        
        $album = new Album();
        $album->name = $this->request->getPost('name');
        $album->folder = $this->request->getPost('folder');
        $album->description = $this->request->getPost('description') != '' ? $this->request->getPost('description') : null;
//        echo $album->name.'-'.$album->folder.'-'.$album->description.'-'.$form->isValid($_POST);die;
        if (!$form->isValid($_POST)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message);
            }
            return $this->response->redirect('add');
        }
        if ($album->save() == false) {
            echo json_encode(array('status'=>false,'msg'=> '添加文件夹失败'));
            exit;
        }else{
            echo json_encode(array('status'=>false,'msg'=> '添加文件夹成功'));
            exit;
        }
        exit;
    }
    /**
     * 展示图片
     */
    public function browseAction($folder,$skip){

        $grid = $this->mongodb->getGridFS($folder);
        $skip_num = 3*($skip-1);
        if($skip == ''){
            $skip_num = 0;
        }
        $image = $grid->find()->limit(3)->skip($skip_num);
        $count = $grid->count();

        $this->view->setVar('folder',$folder);
        $this->view->setVar('count',$count);
        $this->view->setVar('skip',$skip);
        $this->view->setVar('image',$image);
    }
    public function uploadAction(){
        $folder = Album::find();
        $this->view->setVar('folder',$folder);
    }
    public function uploadHandleAction(){
        $folder = $this->request->getPost('folder');
        $savePath = $this->imagesPath.$folder.'/';
    	if(php_uname('s')=='Windows NT'){//本地测试时使用
    		$savePath = dirname($_SERVER["DOCUMENT_ROOT"]).'/images/';
    	}
        if(!file_exists($savePath)){
                mkdir($savePath, 0777, true);
        }
        //定义允许上传的文件扩展名
        $extArr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
        //最大文件大小
        $maxSize = 1000000;
        

        $grid = $this->mongodb->getGridFS($folder);
        if ($this->request->hasFiles() == true) {
            foreach ($this->request->getUploadedFiles() as $file) {
                //获得文件扩展名
                $tempArr = explode(".", $file->getName());
                $fileExt = array_pop($tempArr);
                $fileExt = trim($fileExt);
                $fileExt = strtolower($fileExt);
                //检查扩展名
                if (in_array($fileExt, $extArr) === false) {
                    echo json_encode(array('status'=>false,'msg'=> "上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $extArr) . "格式。"));
                    return $this->response->redirect('upload');
                }
                if($file->getSize()>$maxSize){
                    echo json_encode(array('status'=>false,'msg'=> '上传文件大小超过限制。'));
                    return $this->response->redirect('upload');
                }else{
                    if($file->isUploadedFile()){
                        $fileUrl = $savePath.$file->getName();
                        if($file->moveTo($fileUrl)){
                            $result = $grid->find(array('md5'=>md5_file($fileUrl)));
                            foreach ($result as $doc) {
                                $doc_arr = $this->objToArray->ohYeah($doc);
                                $doc_arr['md5'] = $doc_arr['file']['md5'];
                            }
                            if(!$doc_arr['md5']){
                                $storedfile = $grid->storeFile($fileUrl, array('filename'=>$file->getName(),"date" => new MongoDate()));
                                return $this->response->redirect('folder');
                            }else{
                                echo json_encode(array('status'=>false,'msg'=> '不能重复上传图片。'));
                                return $this->response->redirect('upload');
                            }
                        }else{
                            echo json_encode(array('status'=>false,'msg'=> $file->getError()));
                            return $this->response->redirect('upload');
                        }
                    }
                    else{
                            echo json_encode(array('status'=>false,'msg'=> $file->getError()));
                            return $this->response->redirect('upload');
                    }
                }
                
                
            }
        }else{
                echo json_encode(array('status'=>false,'msg'=> $file->getError()));
                return $this->response->redirect('upload');
        }
        
    }
    
 
    
}

