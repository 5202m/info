<?php
class SynchronousWorker extends Worker {
	
	// public function __construct(Logging $logger) {
	// $this->logger = $logger;
	// }
	
	// protected $logger;
	protected static $dbh_in, $dbh_out;
	public function __construct() {
	}
	public function run() {
		$dbhost = '192.168.6.1'; // 数据库服务器
		$dbuser = 'inf'; // 数据库用户名
		$dbpass = 'inf'; // 数据库密码
		$dbname = 'inf'; // 数据库名
		
		self::$dbh_out = new PDO ( "mysql:host=$dbhost;port=3306;dbname=$dbname", $dbuser, $dbpass, array (
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
				PDO::MYSQL_ATTR_COMPRESS => true,
				PDO::ATTR_PERSISTENT => true 
		) );
		
		$dbhost = 'localhost'; // 数据库服务器
		$dbuser = 'gwfx'; // 数据库用户名
		$dbpass = 'gwfx'; // 数据库密码
		$dbname = 'whdata';
		self::$dbh_in = new PDO ( "mysql:host=$dbhost;port=3307;dbname=$dbname", $dbuser, $dbpass, array (
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
				PDO::MYSQL_ATTR_COMPRESS => true 
		) );
	}
	protected function getInstance($io) {
		if ($io == 'in') {
			return self::$dbh_in;
		} else {
			return self::$dbh_out;
		}
	}
	public function logging($type, $message) {
		$log = sprintf ( "%s\t%s\t%s\t%s\n", date ( 'Y-m-d H:i:s' ), $this->getThreadId (), $type, $message );
		file_put_contents ( "debug.log", $log, FILE_APPEND );
	}
	public function savepoints($division_id, $category_id, $type, $position) {
		$dbo = $this->getInstance ( 'out' );
		$sql = "REPLACE INTO `synchronous` (`division_id`, `category_id`, `type`, `position`) VALUES (:division_id, :category_id, :type, :position);";
		$sth = $dbo->prepare ( $sql );
		$sth->bindValue ( ':division_id', $division_id );
		$sth->bindValue ( ':category_id', $category_id );
		$sth->bindValue ( ':type', $type );
		$sth->bindValue ( ':position', $position );
		return $sth->execute ();
	}
	public function getpoints($division_id, $category_id, $type) {
		$dbo = $this->getInstance ( 'out' );
		$sql = "select position from `synchronous` where division_id=:division_id and category_id=:category_id and type=:type";
		$sth = $dbo->prepare ( $sql );
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
		// print_r($result);
	}
}

/* the collectable class implements machinery for Pool::collect */
class Work extends Stackable {
	public $division_id = 5;
	public function __construct($lang, $dbmaps) {
		$this->dbmaps = $dbmaps;
		$this->lang = $lang;
	}
	public function run() {
		// $this->worker->logger->log("%s executing in Thread #%lu", __CLASS__, $this->worker->getThreadId() );
		try {
			$dbi = $this->worker->getInstance ( 'in' );
			$dbo = $this->worker->getInstance ( 'out' );
			$dbi->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$dbo->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$position = 1;
			foreach ( $this->dbmaps as $division_category_id => $type ) {
				$division_id = $this->division_id;
				$category_id = $division_category_id;
				
				$position = $this->worker->getpoints ( $division_id, $category_id, $type, $position );
				
				$sql = "SELECT no as id, name as title, content, if(language='zh','cn',language) as language, newstime as ctime, SEO_KEYWORDS as keyword, SEO_DESCRIPTION as description FROM real_news WHERE LANGUAGE = '" . $this->lang . "' AND TYPE='" . $type . "' AND no > '" . $position . "' ORDER BY no asc";
				$query = $dbi->query ( $sql );
				$this->worker->logging ( 'SQL', $query->queryString );
				
				while ( $line = $query->fetch ( PDO::FETCH_OBJ ) ) {
					
					$sql = "insert into article (`division_id`, `division_category_id`,  `title`,  `content`, `author`,  `keyword`,  `description`,  `image`,  `language`,  `source`,  `share`,  `visibility`,  `status`,  `ctime`,  `mtime`) values(:division_id, :division_category_id, :title, :content, :author, :keyword, :description, :image,  :language, :source, :share, :visibility, :status, :ctime, :mtime)";
					$sth = $dbo->prepare ( $sql );
					$sth->bindValue ( ':division_id', $this->division_id );
					$sth->bindValue ( ':division_category_id', $division_category_id );
					$sth->bindValue ( ':title', $line->title );
					$sth->bindValue ( ':content', $line->content );
					$sth->bindValue ( ':author', null );
					$sth->bindValue ( ':keyword', $line->keyword );
					$sth->bindValue ( ':description', $line->description );
					$sth->bindValue ( ':image', null );
					$sth->bindValue ( ':language', $line->language );
					$sth->bindValue ( ':source', 'GWFX' );
					$sth->bindValue ( ':share', 'N' );
					$sth->bindValue ( ':visibility', 'Visible' );
					$sth->bindValue ( ':status', 'Enabled' );
					$sth->bindValue ( ':ctime', $line->ctime );
					$sth->bindValue ( ':mtime', null );
					$sth->execute ();
					
					$this->worker->logging ( 'real_news', sprintf ( "%s=>%s %s, %s, %s, %s", $division_category_id, $type, $line->ctime, $line->id, $line->language, $line->title ) );
					if ($line->id) {
						$position = $line->id;
					}
					$this->worker->savepoints ( $division_id, $category_id, $type, $position );
				}
			}
		} catch ( PDOException $e ) {
			$this->worker->logging ( 'Exception real_news', $e );
		} catch ( Exception $e ) {
			$this->worker->logging ( 'Exception real_news', $e );
		}
	}
	public function import() {
	}
	public function export() {
	}
}

$pool = new Pool ( 16, \SynchronousWorker::class, [ ] );

// foreach (range(0, 100) as $number) {
// $pool->submit(new Work($number));
// }

// print_r($dbh);

// $order = $account['order'];
// printf("%s\n",$order);
// print_r($members);
$dbmaps = array (
		'1' => '1',
		'2' => '2',
		'3' => '3',
		'4' => '4',
		'5' => '5' 
);

$pool->submit ( new Work ( 'zh', $dbmaps ) );
$pool->submit ( new Work ( 'tw', array (
		'20' => '1',
		'21' => '2',
		'22' => '3',
		'23' => '4',
		'24' => '5' 
) ) );
// unset($account['order']);

$pool->shutdown ();

?>

