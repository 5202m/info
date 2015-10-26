<?php
/*
 * PHP Daemon sample.
 * Home: http://netkiller.github.io
 * Author: netkiller<netkiller@msn.com>
 * 
*/
class Logger {
	
	public function __construct($logfile /*Logging $logger*/) {
		$this->logfile = $logfile;
	}

	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/%s.%s.log", $this->logfile, date ( 'Y-m-d' )), $log, FILE_APPEND );
	}
	
}

final class Signal{	
    public static $signo = 0;
	protected static $ini = null;
	public static function set($signo){
		self::$signo = $signo;
	}
	public static function get(){
		return(self::$signo);
	}
	public static function reset(){
		self::$signo = 0;
	}
}			

final class Counter{
	public static $succeed 	= 0;
	public static $ignore	= 0;
	public static $failed 	= 0;
	public static $completed 	= 0;
	public static function succeed($mutex){
		if($mutex) {
			
			if(Mutex::lock($mutex))	{
				self::$succeed++;
				Mutex::unlock($mutex);
			}else{
				printf("Mutex lock is failed.");
			}
		}else{
			printf("Mutex lock is inviled.");
		}
	}
	
	public static function ignore($mutex){
		if($mutex) {
			if(Mutex::lock($mutex))	{
				self::$ignore++;
				Mutex::unlock($mutex);
				//printf("Mutex staus is locked.\n");
			}else{
				printf("Mutex lock is failed.");
			}
		}else{
			printf("Mutex lock is inviled.");
		}
		echo self::$ignore.PHP_EOL;
	}
	
	public static function reset($mutex){
		self::$succeed = 0;
		self::$ignore = 0;
		self::$failed = 0;
		Mutex::destroy($mutex);
	}
}

/*
class Test extends Logger {
	//public static $signal = null;
	
	public function __construct() {
		//self::$signal == null;
	}
	public function run(){
		while(true){
			pcntl_signal_dispatch();
			printf(".");
			sleep(1);
			if(Signal::get() == SIGHUP){
				Signal::reset();
				break;
			}
		}
		printf("\n");
	}
}
*/


class ImportWorker extends Worker {

	protected $config;
	protected static $dbh;
	protected static $amqp;
	
	public function __construct($config, $mutex) {
		$this->config = $config;
		$this->logger = new Logger(__CLASS__);
		
		$this->mutex = $mutex;
	}
	public function run() {

	}
	private function connect(){
		try {
			$dbhost = $this->config['database']['host'];
			$dbport = $this->config['database']['port'];
			$dbuser = $this->config['database']['user'];
			$dbpass = $this->config['database']['password'];
			$dbname = $this->config['database']['dbname'];

			self::$dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
					PDO::MYSQL_ATTR_COMPRESS => true
					/*PDO::ATTR_PERSISTENT => true*/
			) );
			self::$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

		} catch ( PDOException $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		}
	}
	protected function getInstance() {

		if(!self::$dbh) {
			$this->connect();
			//$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
		}else{
			//$this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
		}
		
		if(self::$dbh){
			return self::$dbh;
		}else{
			$this->logger ( 'Database', sprintf("Connect database is error %s, %s", $this->config['database']['dbname'], $this->getThreadId ()) );
			$this->logger ( 'Error', sprintf("Worker is shutdown %s", $this->getThreadId ()) );
			$this->shutdown();
		}
	}
	
	public function logger($type, $message) {
		$this->logger->logger($type, $message);
	}

}

class Import extends Stackable {

	private $status = false;
	private $task = null;
	protected $complete;
	
	public function __construct($task, $row) {
		
		$this->task = $task;
		$this->row = $row;

	}
	
	public function isComplete() { 
		return $this->complete; 
    }
	
	public function run() {

		//$dbh = $this->worker->getInstance();	
		//$dbh->beginTransaction();
		try {
			
			if($this->row[0]){
				$this->row[0] = mb_convert_encoding($this->row[0], 'UTF-8',"GB2312,GBK,GB18030,BIG5");
			}
			
			if($this->row[2]){
				if(!filter_var($this->row[2], FILTER_VALIDATE_EMAIL)){
					$this->updateFailed($this->task);
					$this->worker->logger ( 'Contact', sprintf("Failed %s", implode(',',$this->row)) );
					$this->complete = true;
					return;
				}
			}
		
			$contact = $this->selectContact();

			if($contact){
				$this->updateIgnore($this->task);
				//Counter::$ignore++;
				//Counter::ignore($this->worker->mutex);
				
				$this->worker->logger ( 'Contact', sprintf("Ignore %s", implode(',',$this->row) ));
				$contact_id = $contact->id;
			}else{
				$contact_id = $this->insertContact();
				if($contact_id){
					$this->updateSucceed($this->task);
					//Counter::succeed($this->mutex);

					$this->worker->logger ( 'Contact', sprintf("Succeed %s", implode(',',$this->row) ));
				}else{
					$this->worker->logger ( 'Contact', sprintf("Failed %s", implode(',',$this->row)) );
					$this->updateFailed($this->task);
				}
			}
			
			$group_has_contact = $this->selectGroupHasContact($contact_id);
			if(count($group_has_contact) > 0){
				$this->worker->logger ( 'Group', sprintf("Ignore %s", implode(',',$this->row) ));
			}else{
				$this->insertGroupHasContact($contact_id);
				$this->worker->logger ( 'Group', sprintf("Succeed %s", implode(',',$this->row) ));
			}
			
			//$dbh->commit();

		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			//$dbh->rollBack();
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			//$dbh->rollBack();
		}
		
		$this->complete = true;
	}
	
	private function selectContact(){
		
		$dbh = $this->worker->getInstance();
		$sql = "select * from contact where email_digest = :email_digest or mobile_digest = :mobile_digest";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':mobile_digest', md5($this->row[1]) );
		$sth->bindValue ( ':email_digest', 	md5($this->row[2]) );
		$status = $sth->execute ();
		$contact = null;
		if($status){
			$contact = $sth->fetch(PDO::FETCH_OBJ);
		}
		return($contact);
	}
	private function insertContact(){
		
		$dbh = $this->worker->getInstance();
		$key = $this->worker->config['database']['key'];
		$sql = "INSERT INTO contact (name, mobile, email, mobile_digest, email_digest, status) VALUES (:name, AES_ENCRYPT(:mobile, '$key'), AES_ENCRYPT(:email, '$key'), :mobile_digest, :email_digest, :status);";
		$sth = $dbh->prepare ( $sql );
		
		$sth->bindValue ( ':name'	, $this->row[0] );
		$sth->bindValue ( ':mobile'	, $this->row[1] );
		$sth->bindValue ( ':email'	, $this->row[2] );
		$sth->bindValue ( ':mobile_digest', md5($this->row[1]) );
		$sth->bindValue ( ':email_digest', 	md5($this->row[2]) );
		$sth->bindValue ( ':status', 'Subscription' );
		$status = $sth->execute();
		if($status){
			return($dbh->lastInsertId());
		}else{
			return(null);
		}
	}
	
	private function selectGroupHasContact($contact_id){
		
		$dbh = $this->worker->getInstance();
		$sql = "select * from group_has_contact where group_id = :group_id and contact_id = :contact_id";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':group_id', $this->task->group_id );
		$sth->bindValue ( ':contact_id', $contact_id );
		$status = $sth->execute ();
		$group_has_contact = null;
		if($status){
			$group_has_contact = $sth->fetchAll(PDO::FETCH_OBJ);
		}
		return $group_has_contact;
	}
	private function insertGroupHasContact($contact_id){
		if(empty($contact_id)){
			return(null);
		}
		$dbh = $this->worker->getInstance();
		$sql = "INSERT ignore INTO group_has_contact (group_id, contact_id) VALUES (:group_id, :contact_id);";
		$sth = $dbh->prepare ( $sql );
		$sth->bindValue ( ':group_id'	, $this->task->group_id );
		$sth->bindValue ( ':contact_id'	, $contact_id );
		$status = $sth->execute();
		if($status){
			return($dbh->lastInsertId());
		}else{
			return(null);
		}
	}

	private function updateSucceed($task){
		
		$dbh = $this->worker->getInstance();
		$dbh->beginTransaction();
		$sql = "update import set succeed = succeed+1 where status = :status and id = :id";
		$sth = $dbh->prepare ( $sql );
		//$sth->bindValue ( ':succeed', Counter::$succeed );
		$sth->bindValue ( ':id', $task->id );
		$sth->bindValue ( ':status', 'Processing' );
		$status = $sth->execute ();
		$dbh->commit();
		return $status;
	}

	private function updateIgnore($task){
		
		$dbh = $this->worker->getInstance();
		$dbh->beginTransaction();
		$sql = "update import set `ignore` = `ignore`+1 where status = :status and id = :id";
		$sth = $dbh->prepare ( $sql );
		//$sth->bindValue ( ':ignore', Counter::$ignore );
		$sth->bindValue ( ':id', $task->id );
		$sth->bindValue ( ':status', 'Processing' );
		$status = $sth->execute ();
		$dbh->commit();
		return $status;
	}	
	
	private function updateFailed($task){
		
		$dbh = $this->worker->getInstance();
		$dbh->beginTransaction();
		$sql = "update import set failed = failed+1 where status = :status and id = :id";
		$sth = $dbh->prepare ( $sql );
		//$sth->bindValue ( ':failed', Counter::$failed );
		$sth->bindValue ( ':id', $task->id );
		$sth->bindValue ( ':status', 'Processing' );
		$status = $sth->execute ();
		$dbh->commit();
		return $status;
	}	
	
}

final class ImportTask extends Logger{
	
	const MAXCONN 	= 5;
	
	protected $dbh = null;
	
	public function __construct($config) {

		parent::__construct(__CLASS__);
		$this->config = $config;

	}
	
	protected function getInstance() {

		if(!$this->dbh) {
			
			$dbhost = $this->config['database']['host'];
			$dbport = $this->config['database']['port'];
			$dbuser = $this->config['database']['user'];
			$dbpass = $this->config['database']['password'];
			$dbname = $this->config['database']['dbname'];

			$this->dbh = new PDO ( "mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass, array (
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
				PDO::MYSQL_ATTR_COMPRESS => true
				/*PDO::ATTR_PERSISTENT => true*/
			));
			
			$this->dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			
			//$this->logger ( 'Database', sprintf("Connect database %s, %s", $this->config['database']['dbname'], '') );
		}else{
			//$this->logger ( 'Database', sprintf("Get instance database %s, %s", $this->config['database']['dbname'], '') );
		}
		return($this->dbh);
	}
	
    private function newTask(){
        $dbh = $this->getInstance();
        $sql = "update import set status = :status where status = 'New'";
        $sth = $dbh->prepare ( $sql );
        $sth->bindValue ( ':status', 'Processing');
        $status = $sth->execute ();
		//$this->logger ( __CLASS__, sprintf("Task %s", 'New') );
        return $status;
    }
	
	private function processingTask(){

		$sql = "select * from import where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'Processing' );
		$sth->execute ();
		
		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );
		
		$mutex = Mutex::create();
		$pool = new Pool ( self::MAXCONN , \ImportWorker::class, array($this->config, $mutex) );
		
		$pool->collect(function($work){
				return $work->isComplete();
			});
		
		foreach($tasks as $task){
			$this->logger ( __CLASS__, sprintf("Task %s %s", $task->file, 'Processing') );

			pcntl_signal_dispatch();
			
			if(Signal::get() == SIGHUP){
				Signal::reset();
				break;
			}
			
			if(file_exists ($task->file)){

				$handle = fopen($task->file, 'r');
				$i = 0;
				while (($row = fgetcsv($handle, 100000, ',')) !== false) {
					$work[$i] =  new Import ( $task, $row );
					$pool->submit ( $work[$i] );
					$i++;
					//$pool->submit ( new Import ( $task, $row ));

				}
		
				fclose($handle);
				/*
				$this->updateSucceed($task);
				$this->updateIgnore($task);
				$this->updateFailed($task);
				*/
				
				$waiting = true;
				while($waiting){
					
					for($i=0;$i<count($work);$i++){
						
						if($work[$i]->isComplete()){
							Counter::$completed++;
						}
						//printf("work %s:%s \n", count($work), Counter::$completed);
						if(Counter::$completed == count($work)){
							$waiting = false;
							break;
						}
					}
					sleep(1);
				}

				$this->completedTask($task);
			}else{
				$this->failedTask($task);
			}
			//printf("Ignore: %s\n", Counter::$ignore ) ;
		}
		
		$pool->shutdown ();

		//Mutex::unlock($mutex);
		Mutex::destroy($mutex);
		
	}
	private function completedTask($task){
		$dbh = $this->getInstance();       
        $sql = "update import set status = :status where status = 'Processing' and id = :id";
        $sth = $dbh->prepare ( $sql );
        $sth->bindValue ( ':status', 'Completed');
		$sth->bindValue ( ':id', $task->id);
        $status = $sth->execute ();
		$this->logger ( __CLASS__, sprintf("Task %s %s", $task->file, 'Completed') );
        return $status;
	}
	private function failedTask($task){
		$dbh = $this->getInstance();       
        $sql = "update import set status = :status where status = 'Processing' and id = :id";
        $sth = $dbh->prepare ( $sql );
        $sth->bindValue ( ':status', 'Failed');
		$sth->bindValue ( ':id', $task->id);
        $status = $sth->execute ();
		$this->logger ( __CLASS__, sprintf("Task %s %s", $task->file, 'Failed') );
        return $status;
	}
	
	public function run(){
		
		$this->newTask();
		$this->processingTask();

	}
	
}

class Daemon extends Logger {

	const LISTEN = "tcp://192.168.2.15:5555";
	const pidfile 	= __CLASS__;
	const uid		= 80;
	const gid		= 80;
	const sleep	= 5;

	protected $pool 	= NULL;
	protected $config	= array();

	public function __construct($uid, $gid, $class) {
		parent::__construct(__CLASS__);
		$this->pidfile = '/var/run/'.basename(get_class($class), '.php').'.pid';
		//$this->config = parse_ini_file('sender.ini', true); //include_once(__DIR__."/config.php");
		$this->uid = $uid;
		$this->gid = $gid;
		$this->class = $class;
		$this->classname = get_class($class);
		
		$this->signal();
	}
	public function signal(){

		pcntl_signal(SIGHUP,  function($signo) /*use ()*/{
			//echo "\n This signal is called. [$signo] \n";
			printf("The process has been reload.\n");
			Signal::set($signo);
		});

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
			file_put_contents($this->pidfile, getmypid());
			posix_setuid(self::uid);
			posix_setgid(self::gid);
			return(getmypid());
		}
	}
	private function run(){

		//while(true){
			
			//printf("The process begin.\n");
			$this->class->run();
			//printf("The process end.\n");
			
		//}
	}
	private function foreground(){
		$this->run();
	}
	private function start(){
		$pid = $this->daemon();
		for(;;){
			$this->run();
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
	private function reload(){
		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			//posix_kill(posix_getpid(), SIGHUP);
			posix_kill($pid, SIGHUP);
		}
	}	
	private function status(){
		if (file_exists($this->pidfile)) {
			$pid = file_get_contents($this->pidfile);
			system(sprintf("ps ax | grep %s | grep -v grep", $pid));
		}
	}
	private function help($proc){
		printf("%s start | stop | restart | status | foreground | help \n", $proc);
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
		}else if($argv[1] === 'foreground'){
			$this->foreground();
		}else if($argv[1] === 'reload'){
			$this->reload();
		}else{
			$this->help($argv[0]);
		}
	}
}

$daemon = new Daemon(80,80, new ImportTask(parse_ini_file('sender.ini', true)));
$daemon->main($argv);
?>

