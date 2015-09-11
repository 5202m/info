<?php

class ImageController extends ControllerBase
{

    public function indexAction()
    {
	
    }
    public function get($folder, $filename){
		
		$connection = new MongoClient( "mongodb://neo:chen@192.168.6.1/test" );
		$db = $connection->selectDB('test');
		//$filename ='test.jpg';
		$grid = $db->getGridFS($folder);
		//echo $grid->storeFile($filename, array("date" => new MongoDate()));

		$image = $grid->findOne($filename);
		
		if ($image) {
			
			$image_file = sprintf("%s/static/image/%s", $this->basedir, $filename);
			$content = $image->getBytes();
			//echo $image_file;
			if(!is_dir(dirname($image_file))){
    			mkdir(dirname($image_file), 0755, TRUE);
    		}
			file_put_contents($image_file , $content);

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
    public function htmlAction($folder, $filename, $ver = 0){
    
    	//$filename = intval($filename);
    	$ver = intval($ver);
    	
		$this->view->disable();
		$this->get($folder, $filename);
		
    }
}
