<?php

namespace WebSocket;

class Router {

	public $server;
	public $controllers = [];

	public $loggedIn = []; // $userID => [$fd]
	/* @var \Swoole\Table $loggedInReverse */
	public $loggedInReverse; // $fd => $userID

	public function onOpen(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $req) {
		$this->loggedInReverse->set($req->fd,['userID'=>'test']);
	}

	/**
	 * @param \Swoole\WebSocket\Server $server
	 * @param \Swoole\WebSocket\Frame  $frame
	 * @throws \Exception
	 */
	public function onMessage(\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) {
		$msgJSON = json_decode($frame->data);
		if ($msgJSON === null) {
			print "invalid JSON received: {$frame->data}\n\n";
			throw new \Exception('Invalid Message. Please send valid JSON');
		}
		echo "websocket: {$frame->fd}:{$msgJSON->action},opcode:{$frame->opcode},fin:{$frame->finish}\n";
		if (!empty($msgJSON->route)) {
			$controller = $this->getController($msgJSON->route);
		} else throw new \Exception('Route not found.');
		if (
			!empty($msgJSON->action)
			&& is_string($msgJSON->action)
			&& $msgJSON->action !== '__construct'
			&& method_exists($controller, $msgJSON->action)
		) {
			$reflection = new \ReflectionMethod($controller, $msgJSON->action);
			if ($reflection->isPublic()) {
				$controller->{$msgJSON->action}($frame->fd, $msgJSON);
			} else {
				print "Action '{$msgJSON->action}' not public\n";
				throw new \Exception('Disallowed!');
			}
		} else {
			print "Action '{$msgJSON->action}' not found\n";
			throw new \Exception('Invalid Operation');
		}
	}

	public function getController($className) {
		if (isset($this->controllers[ $className ])) return $this->controllers[ $className ];
		$finalName = '\\WebSocket\\Controllers\\' . $className;
		if (class_exists($finalName, true)) {
			$this->controllers[ $className ] = new $finalName();
			if (property_exists($this->controllers[ $className ], 'loggedInReverse')) {
				$this->controllers[ $className ]->loggedInReverse = &$this->loggedInReverse;
			}
			if (property_exists($this->controllers[ $className ], 'server')) {
				$this->controllers[ $className ]->server = &$this->server;
			}
			if (property_exists($this->controllers[ $className ], 'router')) {
				$this->controllers[ $className ]->router = &$this;
			}

			return $this->controllers[ $className ];
		}
		print "Route '{$className}' not found.\n";
		throw new \Exception('Route not found.');
	}

	public function onClose(\Swoole\WebSocket\Server $server, $fd) {
		if ($this->loggedInReverse->exist($fd)) {
			$this->loggedInReverse->del($fd);
		}
	}

	public function onError(\Exception $e, $type, \Swoole\WebSocket\Server $server, $parameter, $fd, $orig) {
		$message = [
			'message' => $e->getMessage(),
			'error'   => true,
			'errno'   => $e->getCode(),
			'orig'    => $orig,
		];
		$output = json_encode($message);
		$server->push($fd, $output, 1, true);
	}


}