<?php

class ReviewersWork extends Worker {
    protected  static $client;

	public function __construct() {
        
	}
    
	public function run(){
        require(ROOTDIR.'/config/webservice.config.php');
        $soap['location'] = 'http://api.hx9999.com/backend/Reviewers_mq';
        self::$client = new SoapClient(null, $soap);
	}
    
    protected function getApi(){
        return self::$client;
    }
}

class Reviewers_work extends Stackable {
	
	public $client;
	public $logging;
	public $membersId = '';
	public $login = '';
	
	public function __construct($info) {
		$this->membersId = $info['members_id'];
		$this->login = $info['login'];
	}
	
	public function logger($type, $message) {
		$log = sprintf("%s\t%s\t%s\n", date('Y-m-d H:i:s'), $type, $message);
		file_put_contents(REVIEWERS_TASK_LOG, $log, FILE_APPEND);
	}
	
	public function run(){
		$this->matchReviewers();
	}
	
	/**
	 * 匹配监察表数据
	 */
	public function matchReviewers(){
		try{
			$return = array();
			///print_r($this->login);
			///print_r($this->membersId);
			///echo 'login=',$this->login,';memberid=',$this->membersId;
			if(is_array($this->membersId)){
				$this->membersId = $this->membersId['members_id'];
			}
			///print_r($this->membersId);
			if(!empty($this->login)){
				$client = $this->worker->getApi();
				$where['status'] = 'Enabled';
				$appendix['page'] = 0;
				$appendix['pageSize'] = 0;
				$appendix['order'] = array('sort', 'asc');
				$reviewersItems = $client->getReviewersItems(null, $where, $appendix);
				unset($where);
				unset($appendix);
				$reviewersNoticeList = array();
				$memberInfo = array();
				$reviewersNoticeList = $client->getReviewersNotice();
				$memberInfo = $client->getReviewersMembersInfo($this->login);
				//print_r($memberInfo);
				$frozenStatus = array('Manual','Black');
				$mailTitle = "监察名单客户开户{action}({$this->login})";
				$chinese_name = isset($memberInfo['chinese_name'])?$memberInfo['chinese_name']:'';
				/*$mailBodyTemp = "<ul><li>新开户账号:<strong color='blue'>{$this->login}</strong></li>
						 <li>新开户名字:<strong color='blue'>{$chinese_name}</strong></li>
						 <li>对应监察名单账号:<strong color='blue'>{reviewersListLogin}</strong></li>
						 <li>对应监察名单姓名:<strong color='blue'>{reviewersListName}</strong></li>
						 <li>对应监察名单检测到的异常资料:<strong color='red'>{reviewersItemsName}</strong>:<strong color='red'>{reviewersItem}</strong></li></ul>";*/
				$smsMessage = '您的邮箱收到一条新的监察预警提示：{mailTitle}，请及时处理；';
				unset($memberInfo['chinese_name']);
				if(isset($reviewersItems['data']['list']) && $reviewersItems['data']['list']){
					foreach ($reviewersItems['data']['list'] as $key => $val){
						if($memberInfo[$val['method']]!=''){
							$where = array();
							$mailBody = '';
							$select = 'R.*,M.mobile,M.email,M.id_number,M.chinese_name,T.members_id,T.platform,T.status AS tstatus,GROUP_CONCAT(concat(P.platform,"_",P.login,"_",P.status)) AS platforms';
							//$where[] = " and begin_time <= '".date('Y-m-d H:i:s')."' and end_time is null and status!='Stop'";
							$where[] = " and R.begin_time <= '".date('Y-m-d H:i:s')."' and R.status!='Stop'";
							/*if(in_array($val['method'], array('id_number','mobile','email'))){
								$where[$val['method']] = md5($memberInfo[$val['method']]);
							}
							else{*/
							$where[$val['method']] = $memberInfo[$val['method']];
							//}
							///print_r($where);
							$appendix['page'] = 0;
							$appendix['pageSize'] = 0;
							$appendix['group'] = "R.id";
							$appendix['order'] = "ORDER BY R.id DESC";
							$appendix['safenet_decrypt'] = true;
							$result = $client->getReviewers($select, $where, $appendix);
							///print_r($result);
							//if(isset($result['list']) && $result['list']){
							if($result){
								$status = 'Email';
								$count = count($result);//count($result['list']);
								//if($count == 1){
								/*if($result[0]['status'] != 'Email'){
									$status = $result[0]['status'];
								}*/
								//echo 'listcount=',$count,';';
								//foreach ($result['list'] as $k => $v){
								foreach ($result as $k => $v){
									//print_r($v);
									if($v){
										/*$content = str_replace('{reviewersListLogin}', $v['login'], $mailBodyTemp);
										$content = str_replace('{reviewersListName}', $v['chinese_name'], $content);
										$content = str_replace('{reviewersItemsName}', $val['name'], $content);
										$content = str_replace('{reviewersItem}', $v[$val['method']], $content);
										$mailBody .= $content;*/
										if($v['status'] == 'Email'){
											$status = $v['status'];
											break;
										}
										else{
											$status = $v['status'];
										}
									}
								}
								//if($count > 1){
								if($status == 'Email'){
									$mailTitle = str_replace('{action}', '通知', $mailTitle);
									$smsMessage = str_replace('{mailTitle}', $mailTitle, $smsMessage);
								}
								else{
									$mailTitle = str_replace('{action}', '手工审批', $mailTitle);
									$smsMessage = str_replace('{mailTitle}', $mailTitle, $smsMessage);
								}
								$logInputs = array();
								$logMessage = array();
								if(in_array($status, $frozenStatus)){//改状态
									//echo $mailBody;
									$platform['status'] = 'Frozen';
									$platform['platfrom_status'] = 'ACC_STATE_FROZEN';
									$platform['memberId'] = $this->membersId;
									$platfromWhere['login'] = $this->login;
									$membersInput['user_status'] = 3;
									$membersWhere['username'] = $this->login;
									$return['platform'] = $client->modifyTradesPlatform($platform, $platfromWhere);
									$return['members'] = $client->modifyMembers($membersInput, $membersWhere);
									//$mailBody .= '<div>审批后, 请电邮通知客服部执行。</div>';
								}
								# 发送邮件或手机短信 开始
								if(isset($reviewersNoticeList['data']['list']['Email']) && $reviewersNoticeList['data']['list']['Email']){
									//$mailStr = str_replace('；', ';', $reviewersNoticeList['data']['list']['Email']['to']);
									//$toMail = explode(';', str_replace(array(chr(10),chr(13)), array(null,null), $mailStr));
									$toMail = str_replace(array(';',chr(10)), array(chr(13),''), $reviewersNoticeList['data']['list']['Email']['to']);
									if(strpos($toMail, chr(13))>-1){
										$toMail = explode(chr(13), $toMail);
									}
									$toMail = array_filter(array_unique($toMail));
									///print_r($toMail);
									//if($return['mail'] = $client->sendMail($toMail, $mailTitle, $mailBody)){
									if($return['mail'] = $client->sendMail($toMail, $mailTitle, $this->login, $chinese_name, $status, $result, $val['name'], $val['method'])){
									//if($return['mail'] = $this->sendMail($toMail, $mailTitle, $mailBody)){
										$logMessage['email'] = '已发送';
										$logMessage['type'] = $mailTitle;
									}
									else{
										$logMessage['email'] = '发送失败';
										$logMessage['type'] = $mailTitle;
									}
								}
								else{
									$logMessage['email'] = '接收邮箱地址未设置';
									$logMessage['type'] = $mailTitle;
								}
								if(isset($reviewersNoticeList['data']['list']['Mobile']) && $reviewersNoticeList['data']['list']['Mobile']){
									if($reviewersNoticeList['data']['list']['Mobile']['send_status']=='Y'){
										//$mobileStr = str_replace('；', ';', $reviewersNoticeList['data']['list']['Mobile']['to']);
										//$toMobile = explode(';', str_replace(array(chr(10),chr(13)), array(null,null), $mobileStr));
										$toMobile = str_replace(array(';',chr(10)), array(chr(13),''), $reviewersNoticeList['data']['list']['Mobile']['to']);
										if(strpos($toMobile, chr(13))>-1){
											$toMobile = explode(chr(13), $toMobile);
										}
										$toMobile = array_filter(array_unique($toMobile));
										///print_r($toMobile);
										$smsInputs['mobile'] = $toMobile;
										$smsInputs['content'] = $smsMessage;
										$smsInputs['operator'] = 'mq';
										$res = $client->addSms($smsInputs);
										$return['sms'] = $res;
										if(isset($res['data']) && $res['data']){
											$logMessage['mobile'] = '已发送';
										}
										else{
											$logMessage['mobile'] = '发送失败';
										}
									}
									else{
										$logMessage['mobile'] = '短信通知暂中';
									}
								}
								else{
									$logMessage['mobile'] = '接收手机号码未设置';
								}
								# 发送邮件或手机短信 结束
								# 写日志 开始
								$logInputs['tag'] = 'mq';
								$logInputs['facility'] = 'reviewers';
								$logInputs['priority'] = 'info';
								$logInputs['message'] = json_encode($logMessage);
								$return['log'] = $client->writeLogging($logInputs);
								# 写日志 结束
								///print_r($return);
								break;
							}
						}
					}
				}
			}
			return $return;
		}
		catch (Exception $e){
			$this->logger('MATCH REVIEWS', $e->getMessage());
			echo $e->getMessage();
		}
	}
}

class Reviewers {
	/* config */
	const LISTEN = "tcp://192.168.2.15:5555";
	const pidfile 	= __CLASS__;
	const uid		= 80;
	const gid		= 80;
	const sleep	= 60;

	protected $pool 	= NULL;
	protected $config	= array();

	public function __construct() {
		$this->pidfile = '/var/run/'.self::pidfile.'.pid';
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
			$task = new Task();
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

class Task {

	const MAXCONN 	= 32;

	protected $result = array();
	protected $queue;
	protected $channel;
	protected $exchange;
	protected $pool;
	protected $config = array();

	public function __construct() {
		$this->config = include_once 'config.php';
	}

	public function logger($type, $message) {
		$log = sprintf("%s\t%s\t%s\n", date('Y-m-d H:i:s'), $type, $message);
		file_put_contents(REVIEWERS_TASK_LOG, $log, FILE_APPEND);
	}
	
	public function main(){
		$this->pool = new Pool(self::MAXCONN , \ReviewersWork::class, []);
		$connection = new AMQPConnection($this->config);
		try{
	
			$exchangeName = 'reviewers_member'; //交换机名
			$queueName = 'members_id'; //队列名
			$routeKey = 'reviewers_member'; //路由key
	
			//创建连接和channel
	
			if (!$connection->connect()) {
				die("Cannot connect to the broker!\n");
			}
			$this->channel = new AMQPChannel($connection);
			$this->exchange = new AMQPExchange($this->channel);
			$this->exchange->setName($exchangeName);
			$this->exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
			$this->exchange->setFlags(AMQP_DURABLE); //持久化
			$this->exchange->declareExchange();
			//echo "Exchange Status:".$this->exchange->declare()."\n";
	
			//创建队列
			$this->queue = new AMQPQueue($this->channel);
			$this->queue->setName($queueName);
			$this->queue->setFlags(AMQP_DURABLE); //持久化
			$this->queue->declareQueue();
			//echo "Message Total:".$this->queue->declare()."\n";
	
			//绑定交换机与队列，并指定路由键
			$bind = $this->queue->bind($exchangeName, $routeKey);
			//echo 'Queue Bind: '.$bind."\n";
	
			//阻塞模式接收消息
			while(true){
				//$this->queue->consume('processMessage', AMQP_AUTOACK); //自动ACK应答
				$this->queue->consume(function($envelope, $queue)/* use (&$pool)*/ {
					try{
						$jsonStr = $envelope->getBody();
						$msg = json_decode($jsonStr, true);
						if($msg){
							$this->pool->submit(new Reviewers_work($msg));
						}
						$queue->ack($envelope->getDeliveryTag()); //手动发送ACK应答
					}
					catch (Exception $e){
						$this->logger('AMQP CONSUME', $e->getMessage());
					}
					//$this->logging->info('('.'+'.')'.$msg);
					//$this->logging->debug("Message Total:".$this->queue->declare());
				});
				$this->channel->qos(0,1);
				//echo "Message Total:".$this->queue->declare()."\n";
			}
			$connection->disconnect();
			$this->pool->shutdown();
		}
		catch (Exception $e){
			$connection->disconnect();
			$this->pool->shutdown();
			$this->logger('TASK MAIN', $e->getMessage());
			echo $e->getMessage();
		}
	}
	
}

?>
