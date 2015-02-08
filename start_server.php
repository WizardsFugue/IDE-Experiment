<?php
/**
 * 
 * 
 * 
 * 
 */

require __DIR__ . '/vendor/autoload.php';


$app = new \Cotya\IDE\Frontend\Application(
    __DIR__.'/pub',
    __DIR__.'/sandbox/workspace'
);


$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);

$http->on('request', function ($request, $response) use ($app){
    $app->onRequest($request, $response);
});

$socket->listen(8083);
$loop->run();