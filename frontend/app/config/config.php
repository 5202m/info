<?php

return new \Phalcon\Config(array(
	'database' => array(
		'adapter'  => 'Mysql',
		'host'     => '192.168.6.1',
		'port'     => 3306,
		'username' => 'inf',
		'password' => 'inf',
		'dbname'     => 'inf',

	),
        'mongodb' => array(
        'host'     => 'mongodb://neo:chen@192.168.6.1/test',
		'port'     => '',
		'username' => '',
		'password' => '',
		'dbname'   => 'test',
        		
        'fileinfo' =>array(
        				'maxSize'=>1000000,
        				'type'=>'image',//array('gif', 'jpg', 'jpeg', 'png', 'bmp')
        				'savePath'=> php_uname('s')=='Windows NT' ? 
        					(dirname($_SERVER["DOCUMENT_ROOT"]).'/images/') //测试环境的
        					:'/www/hx9999.com/inf.hx9999.com/images/',//线上
        		),
        		
        ),
        
      'gwapi' =>array(
	       'base_url'      => 'https://gwapi.24k.hk/GwAPI',
	       'oauthKey'      =>'ade06a7ff83a0ce5',  //平台类型名称
	       'platTypeKey'   =>'hengxin',  //平台类型名称
	       'platAccount'   =>'hx',  //加密盐
	       'lang'          =>'zh', 
       ),

	'application' => array(
		'controllersDir' => __DIR__ . '/../../app/controllers/',
		'modelsDir'      => __DIR__ . '/../../app/models/',
		'viewsDir'       => __DIR__ . '/../../app/views/',
		'pluginsDir'     => __DIR__ . '/../../app/plugins/',
		'libraryDir'     => __DIR__ . '/../../app/library/',
        'formsDir'       => __DIR__ . '/../../app/forms/',
		'imagesDir'		 => '/www/hx9999.com/inf.hx9999.com/images/',
		'imagesUri'		 => 'http://inf.hx9999.com/',
		'baseUri'        => '',
		'templateDir'=> include('template.php'),

	),
	'redis' => array(
		'host' => '192.168.4.1',
		'port' => '6379'
	),
	'models' => array(
		'metadata' => array(
			'adapter' => 'Memory'
		)
	),
	
));
