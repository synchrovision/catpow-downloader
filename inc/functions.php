<?php
if($_SERVER['HTTP_HOST']==='localhost'){
	ini_set("error_log","php://stdout");
}
ini_set('display_errors',0);
define('APP_HOST',$_SERVER['HTTP_HOST'].':'.$_SERVER['SERVER_PORT']);
define('BASE_URL',(!empty($_SERVER['HTTPS'])?'https://':'http://').APP_HOST);
define('ABSPATH',dirname(__DIR__,2));
define('APP_PATH',dirname(__DIR__));
define('APP_NAME',basename(__DIR__));
define('APP_URL',BASE_URL.'/'.APP_NAME);
define('INC_PATH',__DIR__);
chdir(ABSPATH);

require_once INC_PATH.'/vendor/autoload.php';
spl_autoload_register(function($class){
	if(file_exists($f=INC_PATH.'/classes/'.str_replace('\\','/',$class).'.php')){include($f);}
});