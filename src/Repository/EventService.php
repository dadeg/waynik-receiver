<?php

namespace Waynik\Repository;

use Aws\Sns\SnsClient;

class EventService
{

	const TOPIC_EMERGENCY = "arn:emergency";
	const TOPIC_FIRST_CHECKIN = "arn::first_checkin";
	const TOPIC_ENTERING_NEW_COUNTRY = "arn:entering_new_country";

	const WAYNIK_EVENTS_SECRET_KEY = "secret";
	const AWS_SNS_KEY = "Q";
	const AWS_SNS_SECRET = "J";
	const AWS_REGION_CODE = "us-west-2";

	public function publish(string $topic, int $userId, string $message = null, array $data = null)
	{
		$sns = SnsClient::factory(array(
				'credentials' => [
						'key'    => self::AWS_SNS_KEY,
						'secret' => self::AWS_SNS_SECRET
				],
				'region' => self::AWS_REGION_CODE,
				'version' => '2010-03-31'
		));

		$payload = [
				"userId" => $userId,
				"apiKey" => self::WAYNIK_EVENTS_SECRET_KEY
		];

		if ($message) {
			$payload["message"] = $message;
		}

		if ($data) {
			$payload["data"] = $data;
		}

		$sns->publish(array(
				'Message' => json_encode($payload),
				'TargetArn' => $topic
		));
	}
}
