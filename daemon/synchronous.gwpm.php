<?php
class SynchronousWorker extends Worker {

	// public function __construct(Logging $logger) {
	// $this->logger = $logger;
	// }

	protected $config;
	protected static $dbh_gwpm_export, $dbh_gwpm_import;
	public function __construct($config) {
		$this->config = $config;
	}
	public function run() {
		try {
			$dbhost = $this->config['import']['dbhost'];
			$dbport = $this->config['import']['dbport'];
			$dbuser = $this->config['import']['dbuser'];
			$dbpass = $this->config['import']['dbpass'];
			$dbname = $this->config['import']['dbname'];

			self::$dbh_gwpm_import = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
					/*PDO::ATTR_PERSISTENT => true*/
			));

			$dbhost1 = $this->config['gwpm']['export']['dbhost'];
			$dbport1 = $this->config['gwpm']['export']['dbport'];
			$dbuser1 = $this->config['gwpm']['export']['dbuser'];
			$dbpass1 = $this->config['gwpm']['export']['dbpass'];
			$dbname1 = $this->config['gwpm']['export']['dbname'];
			self::$dbh_gwpm_export = new PDO("mysql:host=$dbhost1;port=$dbport1;dbname=$dbname1", $dbuser1, $dbpass1, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
			));

		} catch (PDOException $e) {
			$this->logger('Exception t_hotpoint', $e->getMessage());
		} catch (Exception $e) {
			$this->logger('Exception t_hotpoint', $e->getMessage());
		}

	}
	protected function getInstance($io) {
		if ($io == 't_hotpoint_export'){
			return self::$dbh_gwpm_export;
		} else {
			return self::$dbh_gwpm_import;
		}
	}
	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date('Y-m-d H:i:s'), $this->getThreadId(), $type, $message);
		file_put_contents (sprintf(__DIR__."/../log/gwpm.%s.log", date('Y-m-d')), $log, FILE_APPEND);
	}
	public function savepoints($division_id, $category_id, $type, $position) {
		$db = $this->getInstance('import');
		//$sql = "REPLACE INTO `synchronous` (`division_id`, `category_id`, `type`, `position`) VALUES (:division_id, :category_id, :type, :position);";
		$sql = "Update `synchronous` set `position` = :position where `division_id` = :division_id and `category_id` = :category_id and `type` = :type";
		$sth = $db->prepare ($sql);
		$sth->bindValue(':division_id', $division_id);
		$sth->bindValue(':category_id', $category_id);
		$sth->bindValue(':type', $type);
		$sth->bindValue(':position', $position);
		return $sth->execute();
	}
	public function getpoints($division_id, $category_id, $type) {
		$db = $this->getInstance('import');
		$sql = "select position from `synchronous` where division_id=:division_id and category_id=:category_id and type=:type";
		$sth = $db->prepare($sql);
		$sth->bindValue(':division_id', $division_id);
		$sth->bindValue(':category_id', $category_id);
		$sth->bindValue(':type', $type);
		$sth->execute();
		$result = $sth->fetch(PDO::FETCH_OBJ);
		if ($result) {
			return $result->position;
		} else {
			return 0;
		}
	}
}

class T_HotpointWork extends Stackable {
	public $division_id;
	public function __construct($division_id, $lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang == 'cn'?'zh':$lang;
		$this->division_id = $division_id;
	}
	public function run() {
		//$this->worker->logger('real_news', sprintf("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId()));
		try {
			$db_import = $this->worker->getInstance('import');
			$db_export = $this->worker->getInstance('t_hotpoint_export');
			$db_import->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$db_export->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$position = 1;
			foreach ($this->dbmaps as $division_category_id => $type) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;

				$position = $this->worker->getpoints($division_id, $category_id, $type, $position);

				$sql = "SELECT FHOTPOINT_ID as id, TITLE as title, CONTENT as content, if(LANGUAGE='zh','cn',LANGUAGE) as language, REGTIME as ctime, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description FROM t_hotpoint WHERE LANGUAGE = '" . $this->lang . "' AND TYPE='" . $type . "' AND FHOTPOINT_ID > '" . $position . "' ORDER BY FHOTPOINT_ID asc";
				$query = $db_export->query($sql);
				//$this->worker->logger ( 'SQL', $query->queryString);

				while ($line = $query->fetch(PDO::FETCH_OBJ)) {

					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`,  `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :visibility, :status, :ctime, :mtime)";
					$sth = $db_import->prepare($sql);
					$sth->bindValue(':division_id', $this->division_id);
					$sth->bindValue(':division_category_id', $division_category_id);
					$sth->bindValue(':title', $line->title);
					$sth->bindValue(':content', $line->content);
					$sth->bindValue(':author', null);
					$sth->bindValue(':keyword', $line->keyword);
					$sth->bindValue(':description', $line->description);
					$sth->bindValue(':image', null);
					$sth->bindValue(':language', $line->language);
					$sth->bindValue(':source', 'GWPM');
					$sth->bindValue(':share', 'N');
					$sth->bindValue(':visibility', 'Visible');
					$sth->bindValue(':status', 'Enabled');
					$sth->bindValue(':ctime', $line->ctime);
					$sth->bindValue(':mtime', null);
					$sth->execute();

					$this->worker->logger('t_hotpoint', sprintf("%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title));
					if ($line->id) {
						$position = $line->id;
					}
					$this->worker->savepoints($division_id, $category_id, $type, $position);
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logger('Exception t_hotpoint', $e->getMessage());
		} catch ( Exception $e ) {
			$this->worker->logger('Exception t_hotpoint', $e->getMessage());
		}
	}
	public function export() {
	}
}

class Task {
	
	const MAXCONN 	= 32;
	
	protected $result = array();
	
	public function __construct($division_id, $config) {
		$this->division_id = $division_id;
		$this->config = $config;
	}
	public function main(){
		
		$dbhost = $this->config['import']['dbhost'];
		$dbport = $this->config['import']['dbport'];
		$dbuser = $this->config['import']['dbuser'];
		$dbpass = $this->config['import']['dbpass'];
		$dbname = $this->config['import']['dbname'];

		$dbh = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
			PDO::MYSQL_ATTR_COMPRESS => true
			/*PDO::ATTR_PERSISTENT => true*/
		));		
		
		$sql = "select * from `synchronous` where division_id=:division_id";
		$sth = $dbh->prepare($sql);
		$sth->bindValue(':division_id', $this->division_id);
		$sth->execute();
		
		$syncs = $sth->fetchAll(PDO::FETCH_OBJ);
		
		$pool = new Pool(self::MAXCONN , \SynchronousWorker::class, array($this->config));
		
		foreach($syncs as $sync){
			if($sync->table == 't_hotpoint'){
				if(in_array($sync->lang, array('cn','tw'))){
					$pool->submit(new T_HotpointWork($this->division_id, $sync->lang , array($sync->category_id => $sync->type)));
				}
			}
		}

		$pool->shutdown();	
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
			$task = new Task(2, ($this->config));
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

