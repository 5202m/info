<?php
class SynchronousWorker extends Worker {

	// public function __construct(Logging $logger) {
	// $this->logger = $logger;
	// }

	protected $config;
	protected static $dbh_info_export,$dbh_news_export, $dbh_import;
	public function __construct($config) {
		$this->config = $config;
//                print_r($this->config);die;
	}
	public function run() {
		try {
			$dbhost = $this->config['hxpm']['import']['dbhost'];
			$dbport = $this->config['hxpm']['import']['dbport'];
			$dbuser = $this->config['hxpm']['import']['dbuser'];
			$dbpass = $this->config['hxpm']['import']['dbpass'];
			$dbname = $this->config['hxpm']['import']['dbname'];

			self::$dbh_import = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
					/*PDO::ATTR_PERSISTENT => true*/
			) );

			$dbhost1 = $this->config['hxpm']['info_export']['dbhost'];
			$dbport1 = $this->config['hxpm']['info_export']['dbport'];
			$dbuser1 = $this->config['hxpm']['info_export']['dbuser'];
			$dbpass1 = $this->config['hxpm']['info_export']['dbpass'];
			$dbname1 = $this->config['hxpm']['info_export']['dbname'];
			self::$dbh_info_export = new PDO ( "mysql:host=$dbhost1;port=$dbport1;dbname=$dbname1", $dbuser1, $dbpass1, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
			) );
                        
                        $dbhost2 = $this->config['hxpm']['news_export']['dbhost'];
			$dbport2 = $this->config['hxpm']['news_export']['dbport'];
			$dbuser2 = $this->config['hxpm']['news_export']['dbuser'];
			$dbpass2 = $this->config['hxpm']['news_export']['dbpass'];
			$dbname2 = $this->config['hxpm']['news_export']['dbname'];
			self::$dbh_news_export = new PDO ( "mysql:host=$dbhost2;port=$dbport2;dbname=$dbname2", $dbuser2, $dbpass2, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
			) );

		} catch ( PDOException $e ) {
                        $this->logger ( 'Exception info', $e->getMessage( ) );
                } catch ( Exception $e ) {
                        $this->logger ( 'Exception info', $e->getMessage( ) );
                }

	}
	protected function getInstance($io) {
		if ($io == 'info_export'){
			return self::$dbh_info_export;
                } elseif($io == 'news_export'){
                        return self::$dbh_news_export;
                }else {
			return self::$dbh_import;
		}
	}
	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/hxpm.%s.log", date ( 'Y-m-d' )), $log, FILE_APPEND );
	}
	public function savepoints($division_id, $category_id, $type, $position) {
		$db = $this->getInstance ( 'import' );
		//$sql = "REPLACE INTO `synchronous` (`division_id`, `category_id`, `type`, `position`) VALUES (:division_id, :category_id, :type, :position);";
		$sql = "Update `synchronous` set `position` = :position where `division_id` = :division_id and `category_id` = :category_id and `type` = :type";
		$sth = $db->prepare ( $sql );
		$sth->bindValue ( ':division_id', $division_id );
		$sth->bindValue ( ':category_id', $category_id );
		$sth->bindValue ( ':type', $type );
		$sth->bindValue ( ':position', $position );
		return $sth->execute ();
	}
	public function getpoints($division_id, $category_id, $type) {
		$db = $this->getInstance ( 'import' );
		$sql = "select position from `synchronous` where division_id=:division_id and category_id=:category_id and type=:type";
		$sth = $db->prepare ( $sql );
		$sth->bindValue ( ':division_id', $division_id );
		$sth->bindValue ( ':category_id', $category_id );
		$sth->bindValue ( ':type', $type );
		$sth->execute ();
		$result = $sth->fetch ( PDO::FETCH_OBJ );
		if ($result) {
			return $result->position;
		} else {
			return 0;
		}
	}
}

class InfoWork extends Stackable {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?$lang : 'zh';
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('real_news', sprintf("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId()));
		try {
			$db_export = $this->worker->getInstance ( 'info_export' );
			$db_import = $this->worker->getInstance ( 'import' );
			$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "SELECT newsinfo.id,newsinfo.title,newsinfo.content,newsinfo.keyword,newsinfo.create_time as ctime,newsinfo.language FROM newsinfo,newsinfo_index WHERE language = '" . $this->lang . "' AND newsinfo.news_index = newsinfo_index.id and  newsinfo_index.category ='" . $type . "' AND newsinfo.id > '" . $position . "' ORDER BY newsinfo.id asc";
				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`,  `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :visibility, :status, :ctime, :mtime)";
					$sth = $db_import->prepare ( $sql );
					
					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':content', $line->content );
					$sth->bindValue ( ':author', null );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', null );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':source', 'HXPM' );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', date('Y-m-d H:i:s',$line->ctime) );
					$sth->bindValue ( ':mtime', null );
					$status = $sth->execute ();
					if($status){
						$this->worker->logger ( 'info', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title ) );
						if ($line->id) {
							$position = $line->id;
						}
						$this->worker->savepoints ( $division_id, $category_id, $type, $position );
					}
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception info', $e->getMessage( ) );
		} catch ( PDOStatement $e ) {
			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );			
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception info', $e->getMessage( ) );
		}
	}
	public function export() {
	}
}

class NewsWork extends Stackable {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?$lang : 'zh';
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$db_import = $this->worker->getInstance ( 'import' );
			$db_export = $this->worker->getInstance ( 'news_export' );
			$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );

				$sql = "select ad.aid as id,ar.title as title,ad.body as content,ar.writer as author,ar.keywords as keyword,ar.description as description,ar.pubdate as ctime from news_hx9999.dede_addonarticle as ad left join news_hx9999.dede_archives as ar on ad.aid = ar.id WHERE ar.typeid='".$type."' AND id > '" . $position . "' ORDER BY id asc limit 1000";

				$query = $db_export->query ( $sql );
				//$this->worker->logger ( 'SQL', $query->queryString );

				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`, `attribute`, `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :attribute, :visibility, :status, :ctime, :mtime)";
					$sth = $db_import->prepare ( $sql );

					

					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':content', $line->content );
					$sth->bindValue ( ':author', $line->author );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', $line->description );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', 'cn' );
					$sth->bindValue ( ':source', 'HXPM' );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':attribute', null );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', date('Y-m-d H:i:s',$line->ctime) );
					//$sth->bindValue ( ':mtime', $line->mtime );
					$sth->bindValue ( ':mtime', null );
					$status = $sth->execute ();
					
					if($status){
						$this->worker->logger ( 'news', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, 'cn', $line->title ) );
						if ($line->id) {
							$position = $line->id;
						}
						$this->worker->savepoints ( $division_id, $category_id, $type, $position );
					}
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception news', $e->getMessage( ) );
		} catch ( PDOStatement $e ) {
			$this->worker->logger ( 'Exception news', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception news', $e->getMessage( ) );
		}
	}
	public function import() {
	}
	public function export() {
	}
}


//class VideoWork extends Stackable {
//	public $division_id;
//	public function __construct($division_id, $lang, $dbmaps) {
//		$this->dbmaps = $dbmaps;
//		$this->lang = $lang == 'cn'?'zh':$lang;
//		$this->division_id = $division_id;
//	}
//	public function run() {
//		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
//		try {
//			$db_import = $this->worker->getInstance ( 'import' );
//			$db_export = $this->worker->getInstance ( 'export' );
//			$db_export->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
//			$db_import->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
//			$position = 1;
//			foreach ( $this->dbmaps as $division_category_id => $type ) {
//				$division_id = $this->division_id;
//				$category_id = $division_category_id;
//
//				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );
//
//				$sql = "SELECT no as id, video, title, description, if(source = '2' , 'youku','JW Player') as player , if(display = 0 , 'Visible' ,'Hidden' ) as visibility,author, if(language='zh','cn',language) as language, smallimage as thumbnail, largeimage as image, updatetime as ctime, updatetime as mtime , mis , sort , equipment , expertsId , kind FROM video WHERE kind='".$type."' AND NO IS  NOT NULL AND video !='' AND display = 0 AND  LANGUAGE = '".$this->lang."' AND no > '" . $position . "' ORDER BY NO asc";
//				//AND  (equipment IS NULL OR  equipment!='mobile')
//				$query = $db_export->query ( $sql );
//				//$this->worker->logger ( 'SQL', $query->queryString );
//
//				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {
//
//					$sql = "insert into video (`division_id`, `category_id`,  `title`,  `description`, `thumbnail`,  `image`,  `video`,  `author`,  `language`,  `player`,  `visibility`, `ctime`, `mtime`) values(:division_id, :category_id, :title, :description, :thumbnail, :image, :video, :author, :language, :player, :visibility, :ctime, :mtime)";
//					$sth = $db_import->prepare ( $sql );
//
//					$sth->bindValue ( ':division_id', $this->division_id );
//					$sth->bindValue ( ':category_id', $division_category_id );
//					$sth->bindValue ( ':title', $line->title );
//					$sth->bindValue ( ':description', $line->description );
//					$sth->bindValue ( ':thumbnail', $line->thumbnail );
//					$sth->bindValue ( ':image', $line->image );
//					$sth->bindValue ( ':video', $line->video );
//					$sth->bindValue ( ':author', $line->author );
//					$sth->bindValue ( ':language', $line->language );
//					$sth->bindValue ( ':player', $line->player );
//					$sth->bindValue ( ':visibility', 'Visible' );
//					$sth->bindValue ( ':ctime', $line->ctime );
//					$sth->bindValue ( ':mtime', null );
//
//					$sth->execute ();
//
//					$this->worker->logger ( 'news', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title ) );
//					if ($line->id) {
//						$position = $line->id;
//					}
//					$this->worker->savepoints ( $division_id, $category_id, $type, $position );
//				}
//			}
//		} catch ( PDOException $e ) {
//			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
//		} catch ( Exception $e ) {
//			$this->worker->logger ( 'Exception real_news', $e->getMessage( ) );
//		}
//	}
//	public function import() {
//	}
//	public function export() {
//	}
//}

class Task {
	
	const MAXCONN 	= 32;
	
	protected $result = array();
	
	public function __construct($division_id, $config) {
		$this->division_id = $division_id;
		$this->config = $config;
	}
	public function main(){
		
		$dbhost = $this->config['hxpm']['import']['dbhost'];
		$dbport = $this->config['hxpm']['import']['dbport'];
		$dbuser = $this->config['hxpm']['import']['dbuser'];
		$dbpass = $this->config['hxpm']['import']['dbpass'];
		$dbname = $this->config['hxpm']['import']['dbname'];

		$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
			PDO::MYSQL_ATTR_COMPRESS => true
			/*PDO::ATTR_PERSISTENT => true*/
		) );		
		
		$sql = "select * from `synchronous` where division_id=:division_id";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':division_id', $this->division_id );
		$sth->execute ();
		
		$syncs = $sth->fetchAll ( PDO::FETCH_OBJ );
		
		$pool = new Pool ( self::MAXCONN , \SynchronousWorker::class, array($this->config) );
		
		foreach($syncs as $sync){

			if($sync->table == 'info'){
//				if(in_array($sync->lang, array('cn'))){
					$pool->submit ( new InfoWork ( $this->division_id, 'cn' , array( $sync->category_id => $sync->type)));
//				}
			}

			if($sync->table == 'news'){
				//if(in_array($sync->lang, array('cn'))){
					$pool->submit ( new NewsWork ( $this->division_id, 'cn' , array( $sync->category_id => "$sync->type")));
				//}
			}
			
//			if($sync->table == 'video'){
//				if(in_array($sync->lang, array('cn','tw'))){
//					$pool->submit ( new VideoWork ( $this->division_id, $sync->lang , array( $sync->category_id => "$sync->type")));
//				}
//			}
			
		}

		$pool->shutdown ();	
	}
	
}


class Synchronous {
	/* config */
	const LISTEN = "tcp://192.168.2.15:5555";
	const pidfile 	= __CLASS__;
	const uid		= 80;
	const gid		= 80;
	const sleep	= 60;

	protected $pool 	= NULL;
	protected $config	= array();

	public function __construct() {
		$this->pidfile = '/var/run/'.basename(__FILE__, '.php').'.pid';
		$this->config = include_once(__DIR__."/config.php");
//                print_r($this->config);die;
	}
	private function daemon(){
		if (file_exists($this->pidfile)) {
			echo "The file $this->pidfile exists.\n";
			exit();
		}

		$pid = pcntl_fork();
		if ($pid == -1) {
			 die('could not fork');
		} else if ($pid) {
			 // we are the parent
			 //pcntl_wait($status); //Protect against Zombie children
			exit($pid);
		} else {
			// we are the child
			file_put_contents($this->pidfile, getmypid());
			posix_setuid(self::uid);
			posix_setgid(self::gid);
			return(getmypid());
		}
	}
	private function start(){
		$pid = $this->daemon();
		for(;;){
			$task = new Task ($division_id = 1, ($this->config));
			$task->main();
			sleep(self::sleep);
		}
	}
	private function stop(){

		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			posix_kill($pid, 9);
			unlink($this->pidfile);
		}
	}
	private function status(){
		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			system(sprintf("ps ax | grep %s | grep -v grep", $pid));
		}
	}
	private function help($proc){
		printf("%s start | stop | restart | status | help \n", $proc);
	}
	public function main($argv){

		if(count($argv) < 2){
			$this->help($argv[0]);
			printf("please input help parameter\n");
			exit();
		}
		if($argv[1] === 'stop'){
			$this->stop();
		}else if($argv[1] === 'start'){
			$this->start();
                }else if($argv[1] === 'restart'){
			$this->stop();
                        $this->start();
		}else if($argv[1] === 'status'){
			$this->status();
		}else{
			$this->help($argv[0]);
		}
	}
}

$synchronous = new Synchronous();
$synchronous->main($argv);
?>

