<?php

return new \Phalcon\Config(array(
	'database' => array(
		'adapter'  => 'Mysql',
		'host'     => 'localhost',
		'port'     => 3306,
		'username' => 'root',
		'password' => '',
		'dbname'     => 'test',
	),
	'application' => array(
		'controllersDir' => __DIR__ . '/../../app/controllers/',
		'modelsDir'      => __DIR__ . '/../../app/models/',
		'viewsDir'       => __DIR__ . '/../../app/views/',
		'pluginsDir'     => __DIR__ . '/../../app/plugins/',
		'libraryDir'     => __DIR__ . '/../../app/library/',
		'baseUri'        => '/mvc/single-factory-default/',
	),
	'redis' => array(
		'host' => '192.168.2.1',
		'port' => 6379,
		'auth' => ''
	),
		
	'models' => array(
		'metadata' => array(
			'adapter' => 'Memory'
		)
	)
));
