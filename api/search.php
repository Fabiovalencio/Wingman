<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Jcroll\FoursquareApiClient\Client\FoursquareClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silex\Application;

$search = $app['controllers_factory'];
//defining our necessary keys and url here
$oauth = array(
    'id'		=> 'U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW', //ENTER YOUR CLIENT ID HERE
    'secret'    => '3NLCBGUGK3TMTPWGSMWAHVMOBACK3UW2FE2DMK24XASUTFL2', //ENTER YOUR SECRET ID HERE
    'redirect' 	=> 'http://fabiovalencio.com/wingman/web/index.php/callback' //ENTER YOUR REDIRECT URI HERE
);

// search pubs through lat and lgn and foursquare oauth
$search->post('/pubs', function (Silex\Application $occurrence, Request $request) use ($app, $oauth) {
		// start foursquare	
		$fsq = FoursquareClient::factory(array(
		    'client_id'     => $oauth['id'],    // required
		    'client_secret' => $oauth['secret'] // required
		));
		// optionaly pass in for user specific requests
		$fsq->addToken($request->get('access_token')); 
		// requests with latitude and longitude
		if($request->get('ll')){
			$command = $fsq->getCommand('venues/search', array(
			    'll' => $request->get('ll'),
			    'query' => 'pub'
			));
		// requests without latitude or longitude
		} else {
			$command = $fsq->getCommand('venues/search', array(
			    'near' => 'London, Greater London',
			    'query' => 'pub'
			));
		}
		// returns an array of results		
		$pubs = $command->execute(); 
		$pubs = $pubs['response']['venues'];
		// Obtain a list of columns
		foreach ($pubs as $key => $row) {
		    $mid[$key]  = $row['stats']['checkinsCount'];
		}		
		// Sort the data with mid descending
		// Add $data as the last parameter, to sort by the common key
		array_multisort($mid, SORT_DESC, $pubs);
		//return results
		return $app->json($pubs, 200);
		
})->before(function (Request $request) {
	verifyData("number", $request->get('ll'), "ERROR: LL NOT FOUND");
	verifyData("text", $request->get('access_token'), "ERROR: ACCESS TOKEN NOT FOUND");
	$result = preg_grep("/ERROR.*/", $settings);
	if(count($result) > 0){
		return $app->json(array("Error"=> true, "Message" => $result), 404);
	}
});

$search->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
});

return $search;
