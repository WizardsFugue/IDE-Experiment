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
$silexApp->get('/ide/file', 'Cotya\\IDE\\Frontend\\Controller\\Ide::loadFile');
$silexApp->post('/ide/file', 'Cotya\\IDE\\Frontend\\Controller\\Ide::saveFile');

$application = new \Cotya\IDE\Frontend\Application(
    $silexApp,
    $silexApp['dirs.htdocs'],
    $silexApp['dirs.workspace']
);


$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);

$http->on('request', function ($request, $response) use ($application) {
    $application->onRequest($request, $response);
});

$port = 8083;

$socket->listen($port);
echo "start server on 127.0.0.1:$port";
$loop->run();
