<?php

namespace Waynik\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Waynik\Repository\DependencyInjectionInterface;
use Zend\Diactoros\Response\JsonResponse;
use Waynik\Views\Json as JsonView;
use Waynik\Repository\EventService;
use CountryDefiniteArticle\CountryFormatter;

class Receiver implements ControllerInterface
{
    private $dependencyInjectionContainer;

    public function __construct(DependencyInjectionInterface $dependencyInjector)
    {
        $this->dependencyInjectionContainer = $dependencyInjector;
    }

    public function handle(ServerRequestInterface $request)
    {
        $postData = $request->getParsedBody();
        $queryData = $request->getQueryParams();
        $requestData = array_merge($postData, $queryData);

        $user = $request->getAttribute('user');
				$requestData['user_id'] = $user['id'];
				
				// must be done before this checkin is created.
				$this->sendEmergencyEventIfNeeded($requestData, $user);
				
				$requestData['country'] = $this->getCountry($requestData);
		
        /** @var \Waynik\Models\CheckinModel $checkinModel */
        $checkinModel = $this->dependencyInjectionContainer->make('CheckinModel');
        $checkinId = $checkinModel->create($requestData);

        $message = ["message" => "data received successfully", "data" => $requestData, "id" => $checkinId];

        $this->sendFirstCheckinNotificationsIfNeeded($user);
        $this->sendCountryChangedNotificationsIfNeeded($user);
        
        $response = new JsonResponse($message);
        $view = new JsonView($response);
        $view->render();
    }
    
    private function sendFirstCheckinNotificationsIfNeeded(array $user)
    {
    	if ($user['hasCheckins']) {
    		return;
    	}

    	$this->sendFirstCheckinEmail($user);
		$this->sendFirstCheckinEvent($user);
    	
    }
    
    private function sendFirstCheckinEmail(array $user)
    {
    	// send the first checkin email
    	$mail = $this->dependencyInjectionContainer->make('PHPMailer');
    	$mail->addAddress($user['email']);
    	$mail->addReplyTo('development@waynik.com', 'Waynik');
    	 
    	$mail->isHTML(true);
    	 
    	$mail->Subject = 'Waynik received your first check-in!';
    	 
    	$htmlBody = "<p>Congratulations! We have received your first Waynik location check-in and your set-up is complete. Time to get back out there and start exploring...</p>
					<p><strong>
					Quick Tips:</strong></p>
					<ol><li><strong>How Waynik Works</strong><br />
					Waynik provides a 24/7 self-monitoring location service done discretely in the background of your mobile device and only requires extremely limited amounts of cellular connectivity to stay up to date.
					</li><li>
					<strong>Keep the Waynik App Running</strong><br />
					For best results, keep the Waynik application running in the background of your phone (don't kill the application).
					</li><li>
					<strong>Activate Application in an Emergency</strong><br />
					In an emergency, activate the alert button within app (screenshot below) or by calling the Waynik emergency phone line +1 (202) 643-1047
    	
					<br /><img width='400' src='https://www.waynik.com/admin/images/first-checkin-email-1.png' />
					</li><li>
    	
					<strong>Emergency Response</strong><br />
					In an emergency, Waynik responders execute a pre-defined escalation process based on your unique registration details and seek to communicate your real-time location to the appropriate emergency responders anywhere in the world.
					</li></ol>";
    	 
    	$textBody = "Congratulations! We have received your first Waynik location check-in and your set-up is complete. Time to get back out there and start exploring...
    	
Quick Tips:
1. How Waynik Works
Waynik provides a 24/7 self-monitoring location service done discretely in the background of your mobile device and only requires extremely limited amounts of cellular connectivity to stay up to date.
		  
2. Keep the Waynik App Running
For best results, keep the Waynik application running in the background of your phone (don't kill the application).
		  
3. Activate Application in an Emergency
In an emergency, activate the alert button within app (screenshot below) or by calling the Waynik emergency phone line +1 (202) 643-1047
		  
Screenshot: https://www.waynik.com/admin/images/first-checkin-email-1.png
		  
4. Emergency Response
In an emergency, Waynik responders execute a pre-defined escalation process based on your unique registration details and seek to communicate your real-time location to the appropriate emergency responders anywhere in the world.";
    	 
    	$mail->Body    = $htmlBody;
    	$mail->AltBody = $textBody;
    	 
    	$mail->send();
    }
    
    private function sendFirstCheckinEvent(array $user)
    {
    	$eventService = $this->dependencyInjectionContainer->make('EventService');
    	$eventService->publish(EventService::TOPIC_FIRST_CHECKIN, $user['id'], "Hooray, we got your first update!");
    }
    
    private function sendCountryChangedNotificationsIfNeeded(array $user)
    {
    	// check country changed since last checkin? if first checkin, send as well.
    	/** @var \Waynik\Models\CheckinModel $checkinModel */
    	$checkinModel = $this->dependencyInjectionContainer->make('CheckinModel');
    	
    	$mostRecentCheckin = $checkinModel->getMostRecentForUser($user['id']);
    	$precedingCheckin = $checkinModel->getSecondMostRecentForUser($user['id']);
    	
    	// Check that there are two countries present, that we didn't botch the country lookup.
    	if (is_array($mostRecentCheckin) && !$mostRecentCheckin['country']) {
    		return;
    	}
    	if (is_array($precedingCheckin) && !$precedingCheckin['country']) {
    		return;
    	}
    	
    	/**
    	 * if this is the first checkin (there was no preceding checkin) 
    	 * or the countries are different
    	 */
    	if (
    			!$precedingCheckin 
    			|| $mostRecentCheckin['country'] !== $precedingCheckin['country']
    		) {
    		$this->sendCountryChangedEvent($user, $mostRecentCheckin);
    		$this->sendCountryChangedEmailToMichael($user, $mostRecentCheckin);
    	}    	
    }
    
    private function sendCountryChangedEvent(array $user, array $mostRecentCheckin)
    {
    	$country = $mostRecentCheckin['country'];
		$country = CountryFormatter::format($country);
    	$message = "Welcome to $country! Keep an eye out for an email from us with some helpful information.";

    	$eventService = $this->dependencyInjectionContainer->make('EventService');
    	$eventService->publish(EventService::TOPIC_ENTERING_NEW_COUNTRY, $user['id'], $message);
    }
    
    private function sendCountryChangedEmailToMichael(array $user, array $mostRecentCheckin)
    {
    	// send email to michael to send an email to the user with this info!
    	// send the first checkin email
    	$mail = $this->dependencyInjectionContainer->make('PHPMailer');
    	$mail->addAddress("mbell@waynik.com");
    	$mail->addAddress("dan.degreef@gmail.com");
    	$mail->addReplyTo('development@waynik.com', 'Waynik');
    	
    	$mail->isHTML(true);
    	
    	$mail->Subject = 'Waynik user ' . $user['name'] . ' has entered a new country';
    	
    	$htmlBody = "Hey! " . $user['name'] . " has entered " . $mostRecentCheckin['country']
    				. ". Please send them information about this country. Their email is " . $user['email'];
    	
    	$textBody = $htmlBody;
    	
    	$mail->Body    = $htmlBody;
    	$mail->AltBody = $textBody;
    	
    	$mail->send();
    }
    
    private function sendEmergencyEventIfNeeded($thisCheckin, $user)
    {
    	$checkinModel = $this->dependencyInjectionContainer->make('CheckinModel');
    	$lastCheckin = $checkinModel->getMostRecentForUser($user['id']);

    	if (!$lastCheckin['emergency'] && $this->convertToInt($thisCheckin['emergency'])) {
    		$eventService = $this->dependencyInjectionContainer->make('EventService');
    		$eventService->publish(EventService::TOPIC_EMERGENCY, $user['id'], "We received your emergency request and we are working on it!");
    	}
    	
    }
	
	private function getCountry($checkin)
	{
		$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng="
				. $checkin['latitude']
				. ","
				. $checkin['longitude']
				. "&key=AIzaSyB2mi7rSEn4zhhPs21oacNp7WN4FB5AG2Y";

		$ch = curl_init();
		$timeout = 10;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$locationJson = curl_exec($ch);
		curl_close($ch);

		$locations = json_decode($locationJson);
		$addressComponents = $locations->results[0]->address_components;
		foreach ($addressComponents as $component) {
			if ($component->types[0] === "country") {
				return $component->long_name;
			}
		}
		return null;
				
	}
    
    private function convertToInt($value): int 
    {
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
}