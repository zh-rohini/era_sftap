<?php
include(__DIR__.'/vendor/autoload.php');

use phpseclib\Net\SFTP;

$config 		= require_once(__DIR__."/config/config.php");
$sftp 			= new SFTP($config['hostname'],$config['port']);

if (!$sftp->login($config['username'], $config['password'])) {
	throw new \Exception('Login failed');
}

$files = $sftp->nlist($config['path']);
echo "<pre>"; print_r($files); die;

if (!empty($files)) { 
	foreach ($files as $file) {
		$file_exist = strpos($file, '.835.');
		if($file_exist !== false){
			$remote_file = $config['path']. '/' .$file;  
			$local_file_path = "downloads/$file";
			$sftp->get($remote_file, $local_file_path);
		}
	}	
}
		

die("Completed");