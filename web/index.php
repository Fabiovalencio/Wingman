<?php

require_once __DIR__.'/../vendor/autoload.php';
use Guzzle\Http\Client;
use Jcroll\FoursquareApiClient\Client\FoursquareClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Silex\Application;

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app['twig']->addFunction(new \Twig_SimpleFunction('path', function($url) use ($app) {
    return $app['url_generator']->generate($url);
}));

$app['session'] = $app->share(function ($app) {
    return new Session();
});

//defining our necessary keys and url here
$oauth = array(
    'id'		=> 'U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW', //ENTER YOUR CLIENT ID HERE
    'secret'    => '3NLCBGUGK3TMTPWGSMWAHVMOBACK3UW2FE2DMK24XASUTFL2', //ENTER YOUR SECRET ID HERE
    'redirect' 	=> 'http://www.wingmanbeer.com/callback' //ENTER YOUR REDIRECT URI HERE
);

$app->get('/',function() use ($app, $oauth) {

    return $app['twig']->render('home.twig', array(
        'oauth' => $oauth,
        'pubs' => null,
        'poi' => null,
        'user' => $app['session']->get('user'),
        'title' => 'Wingman Home',
        'home'  => '../test',
        'page_title' => 'Wingman Beer',
        'slogan' => 'slogan',
        'name' => 'Wingman',
        'description' => 'description',
        'link_login' => 'https://foursquare.com/oauth2/authenticate?client_id=U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW&response_type=code&redirect_uri=http://www.wingmanbeer.com/callback',
        'name_button' => 'Login',
        'lat' => null,
        'lng' => null,
        'map' => true,
        'button_botton' => true
    ));
})->bind('/');

$app->get('/home/{ll}',function (Silex\Application $occurrence, Request $request) use ($app, $oauth, $ll) {
		$fsq = FoursquareClient::factory(array(
		    'client_id'     => $oauth['id'],    // required
		    'client_secret' => $oauth['secret'] // required
		));
		
		if($app['session']->get('token4Square')){
			$fsq->addToken($app['session']->get('token4Square')); // optionaly pass in for user specific requests
		}
		$possition = explode(",", $request->get('ll'));
        $lat = $possition[0];
        $lng = $possition[1];
		
		$command = $fsq->getCommand('venues/search', array(
			    'll' => $request->get('ll'),
			    'categoryId' => '4bf58dd8d48988d11b941735',
			    'radius' => '1000'
		));	
		$pubs = $command->execute(); // returns an array of results
		$pubs = $pubs['response']['venues'];
		// Obtain a list of columns
		foreach ($pubs as $key => $row) {
		    $mid[$key]  = $row['stats']['checkinsCount'];
			//echo $key;
		}		
		// Sort the data with mid descending
		// Add $data as the last parameter, to sort by the common key
		array_multisort($mid, SORT_DESC, $pubs);
		
		//Points of interest
		$command = $fsq->getCommand('venues/search', array(
		    'll' => $request->get('ll'),
		    'categoryId' => '4bf58dd8d48988d12d941735',
		    'radius' => '10000'
		));		
		$poi = $command->execute(); // returns an array of results
		$poi = $poi['response']['venues'];
		// Obtain a list of columns
		foreach ($poi as $key => $row) {
		    $mid[$key]  = $row['stats']['checkinsCount'];
			//echo $key;
		}		
		// Sort the data with mid descending
		// Add $data as the last parameter, to sort by the common key
		array_multisort($mid, SORT_DESC, $poi);

	    return $app['twig']->render('home.twig', array(
	        'oauth' => $oauth,
	        'pubs' => $pubs,
	        'poi' => $poi,
	        'user' => $app['session']->get('user'),
            'title' => 'Wingman Home',
            'home'  => '../',
            'page_title' => 'Wingman Beer',
            'slogan' => 'slogan',
            'name' => 'Wingman',
            'description' => 'description',
            'link_login' => 'https://foursquare.com/oauth2/authenticate?client_id=U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW&response_type=code&redirect_uri=http://www.wingmanbeer.com/callback',
            'name_button' => 'Login',
            'lat' => $lat,
            'lng' => $lng,
            'map' => true,
            'button_botton' => true
	   	));
		
})->bind('/home');

$app->get('/privacy',function(Silex\Application $occurrence, Request $request) use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->get('/venue/{id}',function(Silex\Application $occurrence, Request $request) use ($app, $oauth, $id) {
	if($app['session']->get('user')){
		//Venues
		$fsq = FoursquareClient::factory(array(
		    'client_id'     => $oauth['id'],    // required
		    'client_secret' => $oauth['secret'] // required
		));

		$fsq->addToken($app['session']->get('token4Square')); // optionaly pass in for user specific requests
		$id = $request->get('id');

		$command = $fsq->getCommand('venues', array(
			    'venue_id' => $id
		));
		$pub = $command->execute(); // returns an array of results
		$pub = $pub['response']['venue'];	
		//Photos
		$client = new Client('https://api.foursquare.com/{version}', array(
		    'version' => 'v2'
		));
		// $client->addSubscriber(new Guzzle\Plugin\Oauth\OauthPlugin(array(
		    // 'oauth_token'  => $app['session']->get('token4Square'),
		    // 'v' => date('Ymd')
		// )));		
		$photo = $client->get('venues/'.$id.'/photos?oauth_token='.$app['session']->get('token4Square').'&v='.date('Ymd'))->send()->getBody();
		$photo = json_decode($photo, true);
		$photo = $photo['response']['photos']['items'];
		
		return $app['twig']->render('pub.twig', array(
	      'oauth' => $oauth,
	      'user' => $app['session']->get('user'),
	      'pub' => $pub,
	      'photos' => $photo,
            'title' => 'Wingman',
            'home'  => '../',
            'page_title' => 'Wingman Beer',
            'slogan' => 'slogan',
            'name' => 'Wingman',
            'description' => 'description',
            'link_login' => 'https://foursquare.com/oauth2/authenticate?client_id=U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW&response_type=code&redirect_uri=http://www.wingmanbeer.com/callback',
            'name_button' => 'Login',
            'button_botton' => true
	   	));
		
	} else {
		return $app->redirect($app['url_generator']->generate('/'));
	}
    
})->bind('/venue');

$app->get('/points-of-interest',function() use ($app, $oauth) {
    return $app['twig']->render('attraction.twig', array(
	      'oauth' => $oauth
	   	));
})->bind('/points-of-interest');

$app->get('/callback', function(Silex\Application $occurrence, Request $request) use ($app, $oauth) {
	$code = $app['request']->get('code');

	if($code){
		//We need to hit up the authkey URL and get the key in JSON format
		$client = new Client("https://foursquare.com/oauth2/access_token?client_id=".$oauth['id']."&client_secret=".$oauth['secret']."&grant_type=authorization_code&redirect_uri=".$oauth['redirect']."&code=".$code);
		try {
		    $decoded_auth = json_decode($client->get()->send()->getBody(), true);
		} catch (Guzzle\Http\Exception\BadResponseException $e) {
		  	 return $app->redirect($app['url_generator']->generate('/'));
		}
		
		$access_token = $decoded_auth['access_token'];

		//we should save this token to a file on the server for future access.
		file_put_contents('accesstokens.txt', $access_token.PHP_EOL, FILE_APPEND);
		
		//we then look up whatever endpoint of the api we want
		$client = new Client("https://api.foursquare.com/v2/users/self?oauth_token=".$access_token."&v=".date('Ymd'));
		//echo "https://api.foursquare.com/v2/users/self?oauth_token=".$access_token."&v=".date('Y-m-d');
		try {
		    $userinfo = json_decode($client->get()->send()->getBody(), true);
		} catch (Guzzle\Http\Exception\BadResponseException $e) {
		   //return $app->redirect($app['url_generator']->generate('/'));
		}
		$app['session']->set('token4Square', $access_token);
		//echo "https://api.foursquare.com/v2/users/self?oauth_token=".$access_token."&v=".date('Ymd');
		//outputting some overall foursquare info just for fun.
		$user = $userinfo['response']['user'];
		$app['session']->set('user', $user);

		//header("Location: ".$app['url_generator']->generate('/'));
		//return $app->redirect();
		return $app->redirect($app['url_generator']->generate('/'));
	}
    
    //return $app->json($command, 200);
});	

$app->get('/login', function() use($app, $oauth){

    return $app['twig']->render('attraction.twig', array(
        'oauth' => $oauth,
        'user' => $user
    ));

})->bind('/login');

$app->get('/test', function() use($app, $oauth){

    return $app['twig']->render('home.twig', array(
        'oauth' => $oauth,
        'title' => 'Wingman Home',
        'home'  => '../test',
        'page_title' => 'Wingman Beer',
        'slogan' => 'slogan',
        'name' => 'Wingman',
        'description' => 'description',
        'link_login' => 'https://foursquare.com/oauth2/authenticate?client_id=U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW&response_type=code&redirect_uri=http://www.wingmanbeer.com/callback',
        'name_button' => 'Login',
        'map' => true,
        'button_botton' => true
    ));

})->bind('/test');

$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Headers', 'X-CSRF-Token, X-Requested-With, Accept, Accept-Version, Content-Length, Content-MD5, Content-Type, Date, X-Api-Version, Origin');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, PUT, POST, DELETE, OPTIONS');
});

function aasort ($array, $key, $key1) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}
$app->run();
