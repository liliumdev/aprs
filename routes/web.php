<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {

});

//$router->get('/positions/{lat_from}/{lat_to}/{long_from}/{long_to}/{filter}/{zoom}/{minutes?}', 'PacketController@get_positions');
$router->post('/positions', 'PacketController@get_positions');
$router->get('/zones/{zoom}/{minutes}', 'PacketController@get_zones');
$router->post('/store', 'PacketController@store');
$router->get('/last-seen/{callsign}', 'PacketController@get_last_seen');

$router->post('/ping', 'PingController@ping');

$router->get('/trackers', 'TrackerController@all');