<?php

$chan = new \Co\Channel(5);

go(function() use ($chan) {
	for ($i=0;$i<5;$i++){
		go(function() use ($i, $chan){
			$mysql = new Swoole\Coroutine\MySQL();
			$mysql->connect([
				'host' => '127.0.0.1',
				'user' => 'root',
				'password' => 'root',
				'database' => 'test',
				'port' => 33060
			]);

			$data = $mysql->query("select '{$i}' as data, sleep(2)");
			try {
			$chan->push($data[0]);
		} catch(\Exception $e){
			var_dump($e);
		}
		});
	}
	for ($i=0;$i<5;$i++){
		$data = $chan->pop(5);
		print ($data['data']);
	}
	print "\n";
});
