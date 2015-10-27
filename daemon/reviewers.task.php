<?php
date_default_timezone_set('PRC');
//项目根目录
define('ROOTDIR',realpath(dirname(dirname(__FILE__))));
//数据库配置文件
define('DBCONFIG',ROOTDIR.'/config/db.config.php');
// 定义可以同时执行的进程数量
define('MAX_CONCURRENCY_JOB', 10);
define('REVIEWERS_TASK_LOG','/www/hx9999.com/log.hx9999.com/reviewers.' . date("Y-m-d") . '.log');

 
//include_once ROOTDIR.'/reviewers/lib/PHPMailer_v5.2.8/class.phpmailer.php';
include_once(ROOTDIR.'/reviewers/lib/reviewers.php');

try {
    $reviewers = new Reviewers();
    $reviewers->main($argv);
} catch (Exception $e){
    echo $e->getMessage();
}
?>
