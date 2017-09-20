<?php

#session start
session_start();

#dependencies included via composer autoload
require '../vendor/autoload.php';

#load config
$conf = \Noodlehaus\Config::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'settings.php');

#encoding
require 'encoding.php';

#timezone
require 'timezone.php';

if(!file_exists('../propel/.disabled')) {

	require "install.php";
	
}
else
{
	#Database Configuration
	require '../propel/generated-conf/config.php';
	
	#SLIM instantiate
	require 'app.php';
	
	//All routes
	require dirname(__DIR__).DIRECTORY_SEPARATOR.'routes/routes.php';

}
