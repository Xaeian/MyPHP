<?php

require_once(__DIR__ . "/../lib.php");
include_library("tcp");

$servis = function($req) {
  echo($req . PHP_EOL);
  return ">> " . $req;
};

$server = new TCP_Server($server = "127.0.0.1", $port = 7000, $timeout = 0);

while(1) {
  $server->Loop($servis);
}