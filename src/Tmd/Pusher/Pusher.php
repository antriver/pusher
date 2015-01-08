<?php

namespace Tmd\Pusher;

use PDO;

use Sly\NotificationPusher\PushManager,
    Sly\NotificationPusher\Adapter\Apns as ApnsAdapter,
    Sly\NotificationPusher\Collection\DeviceCollection,
    Sly\NotificationPusher\Model\Device,
    Sly\NotificationPusher\Model\Message,
    Sly\NotificationPusher\Model\Push
;

use UrbanAirship\Airship;

class Pusher
{
	// Config
	private $tableName = 'push_users';

	// For iOS push
	private $apnsCertificatePath = null;

	// For migration
	private $urbanAirshipAppKey = null;
	private $urbanAirshipMasterSecret = null;



	private $db = null;
	private $PushManager = null;

	public function __construct(PDO $db, $config = array())
	{
		$this->db = $db;

        foreach ($config as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
	}

	private function getPushManager()
	{
		if (!is_null($this->PushManager)) {
			return $this->PushManager;
		}

		$this->PushManager = new PushManager();
		return $this->PushManager;
	}

	public function addUserToken($userID, $token)
	{
		$s = $this->db->prepare("INSERT INTO {$this->tableName} (userID, token) VALUES(?, ?)");
		return $s->execute([$userID, $token]);
	}

	public function deleteToken($token)
	{
		$s = $this->db->prepare("DELETE FROM {$this->tableName} WHERE token = ?");
		return $s->execute([$token]);
	}

	public function deleteUserToken($userID, $token)
	{
		$s = $this->db->prepare("DELETE FROM {$this->tableName} WHERE userID = ? AND token = ?");
		return $s->execute([$userID]);
	}

	public function deleteUserTokens($userID)
	{
		$s = $this->db->prepare("DELETE FROM {$this->tableName} WHERE userID = ?");
		return $s->execute([$userID]);
	}

	public function getUserTokens($userID)
	{
		var_dump($userID);
		$s = $this->db->prepare("SELECT token FROM {$this->tableName} WHERE userID = ?");
		$s->execute([$userID]);
		return $s->fetchAll(PDO::FETCH_OBJ);
	}

	// TODO: Finish implementing this
	public function push($userID, $message)
	{
		$devices = [];
		foreach ($this->getUserTokens($userID) as $token) {
			$devices[] = new Device($token);
		}

		$pushManager = new PushManager(PushManager::ENVIRONMENT_DEV);

		$adapter = new ApnsAdapter();
		$message = new Message($message);

		$pushManager = $this->getPushManager();
		var_dump($pushManager);
	}

	public function importUrbanAirshipTokens()
	{
		$airship = new Airship($this->urbanAirshipAppKey, $this->urbanAirshipMasterSecret);

		$tokens = $airship->listDeviceTokens();

		foreach ($tokens as $token) {
			//print_r($token);
			$this->addUserToken($token->alias, $token->device_token);
		}
	}

}
