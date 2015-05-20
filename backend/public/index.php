<?php
error_reporting(E_ALL);
use Phalcon\Mvc\Dispatcher;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger;
try {
    

	/**
	 * Read the configuration
	 */
	$config = include(__DIR__."/../app/config/config.php");

	$loader = new \Phalcon\Loader();

	/**
	 * We're a registering a set of directories taken from the configuration file
	 */
	$loader->registerDirs(
		array(
			$config->application->controllersDir,
			$config->application->modelsDir,
            $config->application->formsDir,
            $config->application->imagesDir,
		)
	)->register();

	/**
	 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
	 */
	$di = new \Phalcon\DI\FactoryDefault();
        
        $di->set('dispatcher', function() use ($di) {

                $eventsManager = new EventsManager;

                

                $dispatcher = new Dispatcher;
                $dispatcher->setEventsManager($eventsManager);

                return $dispatcher;
        });

	/**
	 * The URL component is used to generate all kind of urls in the application
	 */
	$di->set('url', function() use ($config) {
		$url = new \Phalcon\Mvc\Url();
		$url->setBaseUri($config->application->baseUri);
		return $url;
	});

	/**
	 * Setting up the view component
	 */
	$di->set('view', function() use ($config) {
		$view = new \Phalcon\Mvc\View();
		$view->setViewsDir($config->application->viewsDir);
		return $view;
	});

	/**
	 * 直接返回保存图片的目录
	 */
	$di->set('imagesPath', function() use ($config) {
		return $config->application->imagesDir;
	});
	
	/**
	 * Database connection is created based in the parameters defined in the configuration file
	 */
    
	$di->set('db', function() use ($config) {
                $eventsManager = new EventsManager();

                $logger = new Phalcon\Logger\Adapter\File("../app/logs/debug.log");

                //Listen all the database events
                $eventsManager->attach('db', function($event, $connection) use ($logger) {
                    if ($event->getType() == 'beforeQuery') {
                        $logger->log($connection->getSQLStatement(), Logger::INFO);
                    }
                });
                $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
                    "host" => $config->database->host,
                    "username" => $config->database->username,
                    "password" => $config->database->password,
                    "dbname" => $config->database->dbname
                ));

                //Assign the eventsManager to the db adapter instance
                $connection->setEventsManager($eventsManager);

                return $connection;
//		return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
//			"host" => $config->database->host,
//			"username" => $config->database->username,
//			"password" => $config->database->password,
//			"dbname" => $config->database->dbname
//		));
	});

	/**
	 * If the configuration specify the use of metadata adapter use it or use memory otherwise
	 */
	$di->set('modelsMetadata', function() use ($config) {
		if (isset($config->models->metadata)) {
			$metadataAdapter = 'Phalcon\Mvc\Model\Metadata\\'.$config->models->metadata->adapter;
			return new $metadataAdapter();
		} else {
			return new \Phalcon\Mvc\Model\Metadata\Memory();
		}
	});
	
	/**
	 * If the configuration specify the use of metadata adapter use it or use memory otherwise
	 */
	$di->set('modelsManager', function() {
          return new Phalcon\Mvc\Model\Manager();
     });
	
	
	/**
	 * Start the session the first time some component request the session service
	 */
	$di->set('session', function() {
		$session = new \Phalcon\Session\Adapter\Files();
		$session->start();
		return $session;
	});
//objToArray
    $di->set('objToArray', function() {
        $l = DIRECTORY_SEPARATOR;
        if (!class_exists('objToArray')) {
            include dirname(dirname(__FILE__)) . "{$l}app{$l}libs{$l}objToArray.php";
        }
        $obj = new objToArray();
        return $obj;
    });
    
    //arrayToObj
    $di->set('arrayToObj', function() {
        $l = DIRECTORY_SEPARATOR;
        if (!class_exists('arrayToObj')) {
            include dirname(dirname(__FILE__)) . "{$l}app{$l}libs{$l}arrayToObj.php";
        }
        $obj = new arrayToObj();
        return $obj;
    });
    
    
    $di->set('tree', function() {
        $l = DIRECTORY_SEPARATOR;
        if (!class_exists('tree')) {
            include dirname(dirname(__FILE__)) . "{$l}app{$l}libs{$l}tree.php";
        }
        $obj = new Tree();
        return $obj;
    });
   
	/**
	 * Handle the request
	 */
	$application = new \Phalcon\Mvc\Application();
	$application->setDI($di);
	echo $application->handle()->getContent();

} catch (Phalcon\Exception $e) {
	echo $e->getMessage();
} catch (PDOException $e){
	echo $e->getMessage();
}