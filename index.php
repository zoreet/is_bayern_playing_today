<?php

include('tokens.php');
$TEAM_ID = 5;

// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// GET THE DATA
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"X-Auth-Token: " . $DATA_TOKEN
  )
);

$context = stream_context_create($opts);

$file = file_get_contents('http://api.football-data.org/v2/teams/' . $TEAM_ID . '/matches?status=SCHEDULED', false, $context);
$data = json_decode($file, true);

if(!$data['matches'] || count($data['matches']) == 0 ) {
  pushNotification('We have a problem', $file);
  die();
}

$today = new DateTime();
$matchDate = new DateTime($data['matches'][0]['utcDate']);
$diff = $today->diff($matchDate)->days;

// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// FIGURE IT OUT
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
if( $diff == 0 || $diff == 1 ) {
  $title = $data['matches'][0]['homeTeam']['name'] . ' vs ' . $data['matches'][0]['awayTeam']['name'];
  $matchDate->setTimezone(new DateTimeZone('Europe/Amsterdam'));
  $message .= $diff ? 'Tomorrow' : 'Today';
  $message .= ' at ';
  $message .= $matchDate->format('G:i');
  $message .= ' in ';
  $message .= $data['matches'][0]['competition']['name'];
  pushNotification($title, $message);
}

// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
// SEND THE NOTIFICATION
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
function pushNotification($title, $message) {
  global $PUSHOVER_TOKEN, $PUSHOVER_USER;
	// create curl resource 
	$ch = curl_init(); 

	curl_setopt($ch, CURLOPT_URL, "https://api.pushover.net/1/messages.json");
	curl_setopt($ch, CURLOPT_HEADER, false);
	/*
	if possible, set CURLOPT_SSL_VERIFYPEER to true..
	- http://www.tehuber.com/phps/cabundlegen.phps
	*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array(
	    // (required) - your application's API token
	    "token" => $PUSHOVER_TOKEN
	    // (required) - the user/group key (not e-mail address) of your user (or you), viewable when logged into our dashboard (often referred to as USER_KEY in our documentation and code examples)
	    , "user" => $PUSHOVER_USER
	    // (required) - your message
	    , "message" => $message
	    
	    // Some optional parameters may be included:
	    // your user's device name to send the message directly to that device, rather than all of the user's devices (multiple devices may be separated by a comma)
	    // , "device" => ""
	    // your message's title, otherwise your app's name is used
	    , "title" => $title
	    // a supplementary URL to show with your message
	    // , "url" => ""
	    // does the text contain HTML?
	    // , "html" => "1"
	    // a title for your supplementary URL, otherwise just the URL is shown
	    // , "url_title" => ""
	    // send as -2 to generate no notification/alert, -1 to always send as a quiet notification, 1 to display as high-priority and bypass the user's quiet hours, or 2 to also require confirmation from the user
	    // , "priority" => ""
	    // a Unix timestamp of your message's date and time to display to the user, rather than the time your message is received by our API
	    // , "timestamp" => ""
	    //  - the name of one of the sounds supported by device clients to override the user's default sound choice
	    // "sound" => ""
	));

	$response = curl_exec($ch);

	curl_close($ch);

	echo "Push notification: " . $message;
}
?>