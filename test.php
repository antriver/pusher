<?php

require __DIR__ . '/vendor/autoload.php';
$cfg = require __DIR__ . '/config.php';

/**
* Database
*/
$db = new \PDO("{$cfg['dbDriver']}:dbname={$cfg['dbName']};host={$cfg['dbHost']}", $cfg['dbUser'], $cfg['dbPassword']);

$pushManager = new \Tmd\Pusher\Pusher($db, [
	'urbanAirshipAppKey' => $cfg['urbanAirshipAppKey'],
	'urbanAirshipMasterSecret' => $cfg['urbanAirshipMasterSecret']
]);

$pushManager->importUrbanAirshipTokens();

//$pushManager->push(1, 'hello');
