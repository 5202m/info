<?php
class SenderWorker extends Worker {

	// public function __construct(Logging $logger) {
	// $this->logger = $logger;
	// }

	protected $config;
	protected static $dbh;
	public function __construct($config) {
		$this->config = $config;

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

		} catch ( PDOException $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		} catch ( Exception $e ) {
			$this->logger ( 'Exception worker', $e->getMessage( ) );
		}		
	}
	protected function getInstance() {

		if(!self::$dbh) $this->connect();
		return self::$dbh;

	}
	public function logger($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( sprintf(__DIR__."/../log/sender.%s.log", date ( 'Y-m-d' )), $log, FILE_APPEND );
	}

}

class QueueWork extends Stackable {

	private $status = false;
	private $task = null;
	
	public function __construct($task) {
		$this->task = $task;
	}
	public function run() {
		//$this->worker->logger('real_news', sprintf("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId()));
		
		$dbh = $this->worker->getInstance ();
		$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		$dbh->beginTransaction();
		try {

			if(empty($this->task->group_id)){
				$sql = "insert ignore into queue(task_id, contact_id) select ".$this->task->id.", id from contact";
			}else{
				$sql = "insert ignore into queue(task_id, contact_id) select ".$this->task->id.", contact.id from contact, group_has_contact where group_has_contact.contact_id = contact.id and group_has_contact.group_id = :group_id;";
			}
				
			$sth = $dbh->prepare ( $sql );
			if(!empty($this->task->group_id)){
				$sth->bindValue ( ':group_id', $this->task->group_id );
			}
			$status = $sth->execute();
			//echo $sth->queryString;
			if($status){
				
				$this->worker->logger ( sprintf("Queue %s", $this->task->name) , "last Insert Id ".$dbh->lastInsertId() );
				
				$sql = "update task set status = :status where status = 'New' and id = :id";
				$sth = $dbh->prepare ( $sql );
				$sth->bindValue ( ':id', $this->task->id );
				$sth->bindValue ( ':status', 'Processing' );
				$status = $sth->execute ();
				if($status){
					$this->worker->logger ( sprintf("Task %s", $this->task->name) , "last Insert Id ".$dbh->lastInsertId() );
					$this->status = true;
				}	
			}
			
			//$this->worker->logger ( 'SQL', $query->queryString );
			
			$dbh->commit();

		} catch ( PDOException $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			$dbh->rollBack();
		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
			$dbh->rollBack();
		}
	}
	private function getStatus(){
		return $this->status;
	}
}

class EmailWork extends Stackable {

	private $task = null;

	public function __construct($task) {
		$this->task = $task;
	}
	public function run() {
		//$this->worker->logger('Thread - news', "%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$dbh = $this->worker->getInstance ();
			$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			
			$sql = "update queue set status = :status where status = 'New' and id = :id";
			$sth = $dbh->prepare ( $sql );
			$sth->bindValue ( ':id', $this->task->id );
			$sth->bindValue ( ':status', 'Processing' );
			$status = $sth->execute ();
			
			if($status){
				$this->worker->logger ( 'Queue', sprintf ( " %s %s", $this->task->name, $this->contact->email ) );
			}

		} catch ( Exception $e ) {
			$this->worker->logger ( 'Exception queue', $e->getMessage( ) );
		}
	}
	public function send($name, $to, $subject, $msg) {
		
		$recipients = "toaddress@domain.com"; 
		$headers["From"] 	= sprintf("%s <%s>", $name, $to); 
		$headers["To"] 		= sprintf("%s <%s>", $name, $to); ; 
		$headers["Subject"] = $subject; 
		$headers["Reply-To"] = "reply@address.com"; 
		$headers["Content-Type"] = "text/plain; charset=ISO-2022-JP"; 
		$headers["Return-path"] = "returnpath@address.com"; 
		 
		$smtpinfo["host"] = "smtp.server.com"; 
		$smtpinfo["port"] = "25"; 
		$smtpinfo["auth"] = true; 
		$smtpinfo["username"] = "smtp_user"; 
		$smtpinfo["password"] = "smtp_password"; 

		$mail = Mail::factory("smtp", $smtpinfo); 

		$mail->send($recipients, $headers, $mailmsg); 
	}
}

class Task {
	
	const MAXCONN 	= 32;
	
	protected $dbh = array();
	
	public function __construct($config) {

		$this->config = $config;
		
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

	}
	
	private function newTask(){
		
		$sql = "select * from task where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'New' );
		$sth->execute ();
		
		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );
		
		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );
		
		foreach($tasks as $task){
			
			$pool->submit ( new QueueWork ( $task ));

		}

		$pool->shutdown();			

	}
	private function processingTask(){
		
		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );
		
		$sql = "select * from task where status = :status";
		$sth = $this->dbh->prepare ( $sql );
		$sth->bindValue ( ':status', 'Processing' );
		$sth->execute ();
		
		$tasks = $sth->fetchAll ( PDO::FETCH_OBJ );
		
		$pool = new Pool ( self::MAXCONN , \SenderWorker::class, array($this->config) );
		
		foreach($tasks as $task){
			
			$templateStatement = $this->dbh->prepare ( "select * from template where status = :status and id = :id" );
			$templateStatement->bindValue ( ':id', $task->template_id );
			$templateStatement->bindValue ( ':status', 'Enable' );
			$templateStatement->execute ();
			
			$template = $templateStatement->fetch( PDO::FETCH_OBJ );
			
			$messageStatement = $this->dbh->prepare ( "select * from message where status = :status and id = :id" );
			$messageStatement->bindValue ( ':id', $task->message_id );
			$messageStatement->bindValue ( ':status', 'New' );
			$messageStatement->execute ();
			
			$message = $messageStatement->fetch( PDO::FETCH_OBJ );
			
			if(empty($task->group_id)){
				$contactStatement = $this->dbh->prepare ( "select mobile, email from contact where status = 'Subscription'" );
			}else{
				$contactStatement = $this->dbh->prepare ( "select mobile, email from contact, group_has_contact where contact.status = 'Subscription' and group_has_contact.contact_id = contact.id and group_has_contact.group_id = :group_id;" );
				$contactStatement->bindValue ( ':id', $task->group_id );
			}
			$contactStatement->execute ();
			
			$contacts = $contactStatement->fetchAll( PDO::FETCH_OBJ );
			
			foreach($contacts as $contact){
				
				$arrFrom = array("{{title}}","{{content}}","{{date}}"); 
				$arrTo = array($message->title, $message->content, $message->ctime); 
				
				$msg = str_replace($arrFrom, $arrTo, $template->content);
				
				if($task->type == 'Email'){
					$pool->submit ( new EmailWork ( $task, $contact, $msg ));
				}
				if($task->type == 'SMS'){
					$pool->submit ( new SMSWork ( $task, $contact, $msg ));
				}				
			}

		}

		$pool->shutdown ();		
		
	}
	private function completedTask(){
		
	}
	public function main(){
		
		$this->newTask();
		$this->processingTask();	
		
	}
	
}

class Sender {
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
		$this->config = parse_ini_file('sender.ini', true); //include_once(__DIR__."/config.php");

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
	private function run(){
		for(;;){
			$task = new Task ($this->config);
			$task->main();
			sleep(self::sleep);
		}
	}
	private function foreground(){
		$this->run();
	}
	private function start(){
		$pid = $this->daemon();
		$this->run();
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
		}else{
			$this->help($argv[0]);
		}
	}
}

$sender = new Sender();
$sender->main($argv);
?>

