<?php

require __DIR__ . '/vendor/autoload.php';
$cfg = require __DIR__ . '/config.php';

/**
* Database
*/
$db = new \PDO("{$cfg['dbDriver']}:dbname={$cfg['dbName']};host={$cfg['dbHost']}", $cfg['dbUser'], $cfg['dbPassword']);

$pusher = new \Tmd\Pusher\Pusher($db, [
	'urbanAirshipAppKey' => $cfg['urbanAirshipAppKey'],
	'urbanAirshipMasterSecret' => $cfg['urbanAirshipMasterSecret']
]);

$pusher->importUrbanAirshipTokens();

//$pusher->push(1, 'hello');
