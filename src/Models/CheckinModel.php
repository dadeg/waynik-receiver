<?php

namespace Waynik\Models;

use Waynik\Repository\DataConnectionInterface;

class CheckinModel
{
    private $storageHelper;

    private $table = "checkins";

    const USER_ID = 'user_id';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const MESSAGE = 'message';
    const BATTERY = 'battery';
    const SPEED = 'speed';
    const BEARING = 'bearing';
    const ALTITUDE = 'altitude';
    const EMERGENCY = 'emergency';
    const COUNTRY = 'country';

    private $fields = [
        self::USER_ID,
        self::LATITUDE,
        self::LONGITUDE,
        self::MESSAGE,
        self::BATTERY,
        self::SPEED,
        self::BEARING,
        self::ALTITUDE,
        self::EMERGENCY,
    	self::COUNTRY
    ];

    public function __construct(DataConnectionInterface $dataConnection)
    {
        $this->storageHelper = $dataConnection;
    }

    public function create(array $providedFields)
    {
        $this->validate($providedFields);
        $providedFields = $this->filter($providedFields);

        $params = [];
        $fieldsString = "";
        $questionMarks = "";
        $comma = "";

        foreach ($this->fields as $field) {
            if (array_key_exists($field, $providedFields)) {
                $params[] = $providedFields[$field];
                $fieldsString .= $comma . "`" . $field . "`";
                $questionMarks .= $comma . "?";
                $comma = ",";
            }
        }

        $sql = "INSERT INTO `" . $this->table . "` (" . $fieldsString . ") VALUES (" . $questionMarks . ")";

        return $this->storageHelper->create($sql, $params);
    }

    private function validate(array $fields)
    {
        if (!array_key_exists(self::USER_ID, $fields) || !$fields[self::USER_ID]) {
            throw new \Exception(self::USER_ID . " is required.");
        }
    }

    private function filter(array $fields)
    {
        if (array_key_exists(self::LONGITUDE, $fields)) {
            $fields[self::LONGITUDE] = (float) $fields[self::LONGITUDE];
        }
        if (array_key_exists(self::LATITUDE, $fields)) {
            $fields[self::LATITUDE] = (float) $fields[self::LATITUDE];
        }
        if (array_key_exists(self::EMERGENCY, $fields)) {
            $fields[self::EMERGENCY] = $this->convertToInt($fields[self::EMERGENCY]);
        }

        return $fields;
    }
    
    private function convertToInt($value): int {
    	$lowerCaseValue = strtolower($value);
    	if (
    		$lowerCaseValue === "false"
    		|| $lowerCaseValue === "off"
    		|| $lowerCaseValue === "no"
    	) {
    		return 0;
    	}
    	return ((bool) $value) ? 1: 0;
    }
    
    public function getMostRecentForUser(int $userId)
    {
    	$sql = "select * from checkins where user_id = ? order by created_at desc limit 1;";
    	$params = [$userId];
    
    	$results = $this->storageHelper->query($sql, $params);
    	 
    	$checkin = array_shift($results);
    	return $checkin;
    }
    
    public function getSecondMostRecentForUser(int $userId)
    {
    	$sql = "select * from checkins where user_id = ? order by created_at desc limit 2;";
    	$params = [$userId];
    
    	$results = $this->storageHelper->query($sql, $params);
    	 
    	$mostRecentCheckin = array_shift($results);
    	$secondMostRecentCheckin = array_shift($results);
    	return $secondMostRecentCheckin;
    }

}