<?php

return new \Phalcon\Config(array(
	'database' => array(
		'adapter'  => 'Mysql',
		'host'     => '192.168.6.1',
		'username' => 'inf',
		'password' => 'inf',
		'dbname'     => 'inf',

	),
	'application' => array(
		'controllersDir' => __DIR__ . '/../../app/controllers/',
		'modelsDir'      => __DIR__ . '/../../app/models/',
		'viewsDir'       => __DIR__ . '/../../app/views/',
		'pluginsDir'     => __DIR__ . '/../../app/plugins/',
		'libraryDir'     => __DIR__ . '/../../app/library/',
        'formsDir'       => __DIR__ . '/../../app/forms/',
		'imagesDir'		 => '/www/hx9999.com/inf.hx9999.com/images/',
		'imagesUri'		 => 'http://inf.hx9999.com/images/',
		'baseUri'        => '',
		'templateDir'=> include('template.php'),
		'hostNode'		=>array('127.0.0.1','192.168.4.1'),
	),
	'redis' => array(
		'host' => '192.168.6.1',
		'port' => '6379'
	),
	'models' => array(
		'metadata' => array(
			'adapter' => 'Memory'
		)
	),
	
));
