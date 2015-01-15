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

	private $production = false;

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

	public function getToken($token)
	{
		$s = $this->db->prepare("SELECT * FROM {$this->tableName} WHERE token = ?");
		$s->execute([$token]);
		return $s->fetch(PDO::FETCH_OBJ);
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
		$s = $this->db->prepare("SELECT DISTINCT token FROM {$this->tableName} WHERE userID = ?");
		$s->execute([$userID]);
		return $s->fetchAll(PDO::FETCH_OBJ);
	}

	public function push($userID, $message)
	{
        // Devices to send to
        $devices = [];
        foreach ($this->getUserTokens($userID) as $token) {
            $devices[] = new Device(strtolower($token->token));
        }

        if (empty($devices)) {
            return false;
        }

		$pushManager = new PushManager($this->production ? PushManager::ENVIRONMENT_PROD : PushManager::ENVIRONMENT_DEV);

        // Adapter to send with
		$adapter = new ApnsAdapter(array(
            'certificate' => $this->apnsCertificatePath,
        ));

        // Message to send
        // See: https://github.com/Ph3nol/NotificationPusher/blob/master/doc/apns-adapter.md
		$message = new Message($message['text'], $message);


        $push = new Push($adapter, $devices, $message);
        $pushManager->add($push);
        return $pushManager->push(); // Returns a collection of notified devices
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
