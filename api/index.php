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

require_once __DIR__.'/../vendor/autoload.php';
require 'functions.php';

$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['mysql'] = $app->share(function() {
    try {
        $pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        return new \PDO('mysql:host=localhost;dbname=wingman', 'bdadmin', 'TIwingman2014', $pdo_options);
    } catch (\PDOException $e) {
        var_dump($e->getMessage());
    }
});

$oauth = array(
    'id'		=> 'U0X013XFKEPZONZABFVTGUIX3SLXFMFHGWT3BQI4NV25ZJGW', //ENTER YOUR CLIENT ID HERE
    'secret'    => '3NLCBGUGK3TMTPWGSMWAHVMOBACK3UW2FE2DMK24XASUTFL2', //ENTER YOUR SECRET ID HERE
    'redirect' 	=> 'http://www.wingmanbeer.com/callback' //ENTER YOUR REDIRECT URI HERE
);

$app->mount("/search", include 'search.php');

$app->get('/', function (Silex\Application $app) {
	return $app->json(array("Erro"=>"PAGE NOT FOUND"), 201);
});

$app->get('/googlell', function() use($app){

    $sql = "SELECT * from pubs WHERE lat = ''";
    $result = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach($result as $row) {
        $address = $row['address'];
        $id = $row['id'];

        $response = sendData("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=false&key=AIzaSyABg3_lAn7VYyoxp8SaM98ZarA7S-2LQkQ", null);
        $response_a = json_decode($response);

        $arrQuery =
            array(
                "lat" => $response_a->results[0]->geometry->location->lat,
                "lng" => $response_a->results[0]->geometry->location->lng
            );

        if($arrQuery['lat']){
            $sql = atualizar('pubs', $arrQuery, "id = ".$id);
            $req = $app['mysql']->prepare($sql);
            $req->execute();
        }
    }

    $sql = "SELECT * from pubs ";
    $results = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    return $app->json($results, 200);

})->bind('googlell');

$app->get('/foursquarell', function() use($app, $oauth){
    $sql = "SELECT * from pubs WHERE IdFoursquare = ''";
    $result = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach($result as $row) {

        $id = $row['id'];
        $end = $row['address'];
        $name = $row['name'];
        $ll = $row['lat'].",".$row['lng'];
        try{
            //$response = sendData("https://api.foursquare.com/v2/venues/explore", "?client_id=".$oauth['id']."&client_secret=".$oauth['secret']."&query=".$name."&ll=".$ll, "get");
            $response = sendData("https://api.foursquare.com/v2/venues/explore", "?client_id=".$oauth['id']."&client_secret=".$oauth['secret']."&categoryId=4bf58dd8d48988d11b941735&v=20140228&ll=".$ll, "get");
        } catch(Exception $e) {
            continue;
        };

        $pubs_a = json_decode($response);
        $pubs = $pubs_a->response->groups[0]->items;

        for($x=0; $x<count($pubs); $x++){

            $venue = get_object_vars($pubs[$x]);
            $pub = $venue['venue'];
            $pub = get_object_vars($pub);
            $location = get_object_vars($pub['location']);

            $arrayPub =
                array(
                    "nameFoursquare"=> $pub["name"], //strpos($pub["name"], "'") ? mysql_real_escape_string($pub["name"]) : $pub["name"],
                    "idFoursquare"  => $pub['id'],
                    "address"       => $location["address"], //strpos($location["address"], "'") ? mysql_real_escape_string($location["address"]) : $location["address"],
                    "lat"           => $location['lat'],
                    "lng"           => $location['lng']
                );

            similar_text($end, $location['address'], $perct);
            if ($perct > 50) {
                similar_text($name, $pub['name'], $percent);
                if($percent > 55){
                    try{
                        $sql = atualizar('pubs', $arrayPub, "id = ".$id);
                        $req = $app['mysql']->prepare($sql);
                        $req->execute();
                    } catch(Exception $e) {
                        echo $sql;
                        echo '</br>';
                        echo $e;
                        echo '</br>';
                        continue;
                    };

                }
            }
        }

    }

    try{
        $sql = "SELECT * from pubs WHERE IdFoursquare != ''";
        $results = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $app->json($results, 200);
    } catch(Exception $e) {
        return $app->json($e, 202);
    }

})->bind('foursquarell');

$app->get('/instagramll', function() use($app, $oauth){
    $sql = "SELECT * from pubs WHERE idInstagram = ''";
    $result = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach($result as $row) {

        $id = $row['id'];
        $end = $row['address'];
        $name = $row['name'];
        $ll = $row['lat'].",".$row['lng'];
        try{
            $response = sendData("https://api.instagram.com/v1/locations/search", "?access_token=1855841.14b66f9.bf53263717174f1eb53171939a7562a6&lat=".$row['lat']."&lng=".$row['lng'], "get");
        } catch(Exception $e) {
            continue;
        };
        echo "https://api.instagram.com/v1/locations/search?access_token=1855841.14b66f9.bf53263717174f1eb53171939a7562a6&lat=".$row['lat']."&lng=".$row['lng'];
        $pubs_a = json_decode($response);
        print_r($pubs_a);
        die();
        $pubs = $pubs_a->response->groups[0]->items;

        for($x=0; $x<count($pubs); $x++){

            $venue = get_object_vars($pubs[$x]);
            $pub = $venue['venue'];
            $pub = get_object_vars($pub);
            $location = get_object_vars($pub['location']);

            $arrayPub =
                array(
                    "nameFoursquare"=> $pub["name"], //strpos($pub["name"], "'") ? mysql_real_escape_string($pub["name"]) : $pub["name"],
                    "idFoursquare"  => $pub['id'],
                    "address"       => $location["address"], //strpos($location["address"], "'") ? mysql_real_escape_string($location["address"]) : $location["address"],
                    "lat"           => $location['lat'],
                    "lng"           => $location['lng']
                );

            similar_text($end, $location['address'], $perct);
            if ($perct > 50) {
                similar_text($name, $pub['name'], $percent);
                if($percent > 55){
                    try{
                        $sql = atualizar('pubs', $arrayPub, "id = ".$id);
                        $req = $app['mysql']->prepare($sql);
                        $req->execute();
                    } catch(Exception $e) {
                        echo $sql;
                        echo '</br>';
                        echo $e;
                        echo '</br>';
                        continue;
                    };

                }
            }
        }

    }

    try{
        $sql = "SELECT * from pubs WHERE IdFoursquare != ''";
        $results = $app['mysql']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $app->json($results, 200);
    } catch(Exception $e) {
        return $app->json($e, 202);
    }

})->bind('instagramll');

$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
});

$app->run();
