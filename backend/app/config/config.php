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
	),
	'redis' => array(
<<<<<<< Updated upstream
		'host' => '192.168.2.1',
=======
		'host' => '127.0.0.1',
>>>>>>> Stashed changes
		'port' => '6379'
	),
	'models' => array(
		'metadata' => array(
			'adapter' => 'Memory'
		)
	),
	
));
