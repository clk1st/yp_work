<?php
 ini_set('display_errors',1);
 ini_set('error_reporting',E_ALL ^ E_NOTICE);
/**
 * FastCGI模式PHP FPM提供了一个名为fastcgi_finish_request的方法，提前响应客户端，php程序可继续执行
 */
if (!function_exists("fastcgi_finish_request")) {
	function fastcgi_finish_request()  {
	}
}

date_default_timezone_set('PRC');

define('APPLICATION_PATH', dirname(__FILE__)."/../");
$application = new Yaf_Application( APPLICATION_PATH . "/env/application.ini");
$application->bootstrap()->run();
