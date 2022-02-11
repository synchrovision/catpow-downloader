<?php
if(php_sapi_name()==='cli'){
	chdir(dirname(__DIR__));
	passthru('php -S localhost:8080 '.basename(__DIR__).'/server.php & open http://localhost:8080/'.basename(__DIR__).'/');
	return;
}
return false;