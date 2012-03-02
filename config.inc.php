<?php

// $Id: //

/**
 * @file config.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('Europe/London');

$config['db_user']		= 'root';
$config['db_passwd']	= '';
$config['db_name']		= 'gbif';

// Proxy settings for connecting to the web---------------------------------------------------------

// Set these if you access the web through a proxy server
$config['proxy_name'] 	= '';
$config['proxy_port'] 	= '';

$config['proxy_name'] 	= 'wwwcache.gla.ac.uk';
$config['proxy_port'] 	= '8080';

// Keys---------------------------------------------------------------------------------------------
$config['bing_appid'] 		= 'F83F42E3B9C6AF8DD04675AC16C481DB744AAD0A';
$config['uBio_key'] 		='b751aac2219cf30bcf3190d607d7c9494d87b77c'; 


?>