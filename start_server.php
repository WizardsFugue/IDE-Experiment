<?php
/**
 * 
 * 
 * 
 * 
 */

require __DIR__ . '/vendor/autoload.php';

$silexApp = new Silex\Application();

$silexApp['debug'] = true;

$silexApp['dirs.htdocs'] = __DIR__.'/pub';
$silexApp['dirs.workspace'] = __DIR__.'/sandbox/workspace';

$silexApp->get('/ide/filetree', 'Cotya\\IDE\\Frontend\\Controller\\Ide::filetree');

$application = new \Cotya\IDE\Frontend\Application(
    $silexApp,
    $silexApp['dirs.htdocs'],
    $silexApp['dirs.workspace']
);


$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);

$http->on('request', function ($request, $response) use ($application){
    $application->onRequest($request, $response);
});

$socket->listen(8083);
$loop->run();
