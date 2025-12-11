<?php

require "config.php";

$retries = 5;
$connected = false;
$conn = null;

while ($retries > 0 && !$connected) {
    $conn = @new \mysqli(
        $db_config['host'],
        $db_config['user'],
        $db_config['password'],
        $db_config['database']
    );
    if ($conn->connect_error) {
        $retries--;
        sleep(1);
    } else {
        $connected = true;
    }
}
