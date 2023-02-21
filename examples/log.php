<?php

require_once(__DIR__ . "/../__main.php");
include_library("log");

$log = new LOG("example.log", __DIR__ . "/log/", 10^4, false, "s");

$log->Error("This is an example error");
$log->Warning("This is an example warning");
$log->Info("This is an example information");
$log->Debug("This is an example debug message");

$log->Push("Not this way");
$log->Reset();
$log->Push("This way", "you can pass");
$log->Push("the message");
$log->Push("in parts");
$log->Send();
$head = "mytxt";
$log->Note("mytxt", 'Default header, $head:', "'$head'", '$myint', 12);
# No add space after ':' or '=' char

$complex = [
  "code" => 12,
  "message" => "Objects and arrays are saved in separate files with references",
  "error" => false
];
$log->Error("Some kind of problem, check this", $complex);
$log->Debug("Not recommended but possible", PHP_EOL, "Multiline message");

$log->Warning("External text-file", LOG::File("External text-file"));
