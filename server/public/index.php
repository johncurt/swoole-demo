<?php
if (PHP_SAPI == 'cli-server') {
	// To help the built-in PHP dev server, check if the request was actually for
	// something which should probably be served as a static file
	$url = parse_url($_SERVER['REQUEST_URI']);
	$file = __DIR__ . $url['path'];
	if (is_file($file)) {
		return false;
	}
}

require __DIR__ . '/../vendor/autoload.php';

//session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
//$app->run();

$bridgeManager = new \Pachico\SlimSwoole\BridgeManager($app);
$http = new \Swoole\WebSocket\Server('0.0.0.0', 8282);
/**
 * We register the on "start" event
 */
$http->on("start", function (\swoole_http_server $server) {
	echo sprintf('Swoole http server is started at http://%s:%s', $server->host, $server->port), PHP_EOL;
});

/**
 * We register the on "request event, which will use the BridgeManager to transform request, process it
 * as a Slim request and merge back the response
 *
 */
$http->on(
	"request",
	function (swoole_http_request $swooleRequest, swoole_http_response $swooleResponse) use ($bridgeManager) {
		$bridgeManager->process($swooleRequest, $swooleResponse)->end();
	}
);


//NOW WebSockets!!

$router = new \WebSocket\Router();

$router->server = &$http;
$loggedInReverse = new \Swoole\Table(8000);
$loggedInReverse->column('userID', \Swoole\Table::TYPE_INT);
$loggedInReverse->create();
$router->loggedInReverse = &$loggedInReverse;

$http->on('open', function ($http, $req) use (&$router) {
	try {
		$router->onOpen($http, $req);
	} catch (\Exception $e) {
		$router->onError($e, 'open', $http, $req, $req->fd, $req->data);
	}
});

$http->on('message', function ($http, \Swoole\WebSocket\Frame $frame) use (&$router) {
	try {
		$router->onMessage($http, $frame);
	} catch (\Exception $e) {
		$router->onError($e, 'message', $http, $frame, $frame->fd, $frame->data);
	}
});

$http->on('close', function ($http, $fd) use (&$router) {
	try {
		$router->onClose($http, $fd);
	} catch (\Exception $e) {
		$router->onError($e, 'close', $http, null, $fd, '');
	}
});

$http->start();
