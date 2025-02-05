<?php

require "secrets.php";

$database = "rocola";

$retries = 5;
$connected = false;
$conn = null;

while ($retries > 0 && !$connected) {
    $conn = @new \mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database);
    if ($conn->connect_error) {
        $retries--;
        sleep(1);
    } else {
        $connected = true;
    }
}
