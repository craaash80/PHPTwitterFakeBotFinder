<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
//$content = $connection->get('account/verify_credentials');

/* Get logged in user to help with tests. */
$user = $connection->get('account/verify_credentials');

$arr_results = array();
$arr_results_bot = array();
$arr_results_2 = array();
$arr_results_bot_2 = array();

$cursor = "-1";
//$getfield = "friends/ids.json?screen_name=noeffeb&count=1000";
$getfield = "friends/ids.json?screen_name=" . $user["screen_name"] . "&count=1000";


do
{
	$url_with_cursor = $getfield . "&cursor=" . $cursor;

	$arr_results_2 = $connection->get($url_with_cursor);
	//$arr_results_2 = json_decode($myresultfriend, true);
	$cursor = $arr_results_2["next_cursor_str"];
	$arr_results = array_merge_recursive($arr_results, $arr_results_2);

}
while (( $cursor != "0" )  && ($connection->http_code == "200"));
if($connection->http_code == "200")
{
	$getfield = "friends/ids.json?screen_name=nofakebot&count=1000";
	$cursor = "-1";

	do 
	{
		$url_with_cursor = $getfield . "&cursor=" . $cursor;      
		$arr_results_bot_2 = $connection->get($url_with_cursor);
		//$arr_results_bot_2 = json_decode($myresultbot, true);
		$cursor = $arr_results_bot_2["next_cursor_str"];
		$arr_results_bot = array_merge_recursive($arr_results_bot, $arr_results_bot_2);

	}
	while (( $cursor != "0" ) && ($connection->http_code == "200"));

	if($connection->http_code == "200")
	{
		
		$arr_inters = array_intersect($arr_results["ids"], $arr_results_bot["ids"]);
		$content = "";

		if(count($arr_inters) > 0)
		{
			$getfield = "friends/ids.json?screen_name=nofakebot&count=1000";
			$truncateAt = 100;
	
			//$parameters = array('user_id' =>  implode (",", $arr_inters));

			$parameters2 = $arr_inters;
//			$parameters2 = explode(',', $arr_inters);
			$content = $parameters2;
			if(count($parameters2) > $truncateAt)
				$parameters = implode ("," , array_slice($parameters2, 0, $truncateAt)); 
			else
				$parameters = implode ("," , $parameters2); 
			//$content = $parameters;

			$parameters_arr = array('user_id' =>  $parameters);
			$lookups = $connection->post('users/lookup', $parameters_arr);
			//$content = $lookups;	
			
			$content .= '<br><br>';
			$content .= '<p>Lista dei primi 100 #fakebot tra i contatti che segui (si consiglia il defollow immediato): </p><br/>';
			$content .= '<table border="1" cellpadding="2" cellspacing="0">';
			$content .= '<tr>';
			$content .= '<th>Utente</th>';
			$content .= '</tr>';

			foreach($lookups as $value)
			{
				$content .= '<tr><td><a href="#" onclick="javascript:showModalDialog(\'https://twitter.com/' . $value["screen_name"] . '\', null, \'dialogWidth:1024px; dialogHeight:768px; center:yes;\')" >@' . $value["screen_name"] . '</a></td></tr>' ;
			}
			$content .= '</table>';
			
		
		}
		else
		{
			$content = "<h3>Nessun #fakebot tra i tuoi following! Congratulazioni</h3>";
		}
	}
	else
	{
		$content = "<h3>Errore: L&apos;applicazione ha raggiunto i limiti di utilizzo consentiti da Twitter. Riprovare pi&ugrave; tardi." . "</h3></br><span> HTTP error " . $connection->http_code ."</span></br><span>" . $arr_results_bot_2 . "</span>";


	}

}
else
{
	$content = "<h3>Errore: L&apos;applicazione ha raggiunto i limiti di utilizzo consentiti da Twitter. Riprovare pi&ugrave; tardi. " . "</h3></br><span> HTTP error " . $connection->http_code ."</span></br><span>" . $arr_results_2 ."</span>";


}
//$content = $arr_results_2;
//$content = $user;

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham'));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992));
//$connection->post('friendships/destroy', array('id' => 9436992));

/* Include HTML to display on the page */
include('html.inc');
