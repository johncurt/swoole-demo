<?php

$mysqli = new mysqli('127.0.0.1','root','root','test',33060);

for ($i=0;$i<5;$i++) {
	$result = $mysqli->query("select '{$i}' as data, sleep(2)");
	print $result->fetch_assoc()['data'];
}
print "\n";

//will take 10 seconds to complete!