<?php

namespace WebSocket\Controllers;

class Chat {

	/* @var \Swoole\Table $loggedInReverse */
	public $loggedInReverse;
	/* @var \Swoole\WebSocket\Server $server */
	public $server;

	public function message($sentByFd, $jsonRequest){
		foreach ($this->loggedInReverse as $fd=>$userIDData){
			$userID = $userIDData['userID'];
			if ($fd!=$sentByFd){
				$this->sendToFd($fd, ['message'=>$sentByFd.': '.$jsonRequest->message]);
			} else {
				$this->sendToFd($fd, ['message'=>'YOU: '.$jsonRequest->message]);
			}
		}
	}

	private function sendToFd($fd, $message, $encodeToJson=true){
		if ($encodeToJson){
			$output = json_encode($message, JSON_OBJECT_AS_ARRAY);
		} else {
			$output = $message;
		}
		if ($this->server->isEstablished($fd)) {
			$this->server->push($fd, $output, 1, true);
			return true;
		} else {
			return false;
		}
	}
}