<?php

require_once(__DIR__ . "/../lib.php");
include_library("tcp");

$client = new TCP_Client($server = "127.0.0.1", $port = 7000, $timeout = 2000);
$res = $client->Run("Hello");
echo($res);