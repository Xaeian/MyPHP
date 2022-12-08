<?php

require_once(__DIR__ . "/../lib.php");
include_library("log");

$log = new LOG("example.log", $limit = 0, $precision = "s", $utc = false);

$log->Error("This is an example error");
$log->Warning("This is an example warning");
$log->Info("This is an example information");
$log->Debug("This is an example debug message");

$log->Push("Not this way");
$log->Reset();
$log->Push("This way");
$log->Push("you can pass");
$log->Push("the message");
$log->Push("in parts");
$log->Send("info");

$msg = [
  "code" => 12,
  "message" => "This is a multiline, unknown-type message",
  "error" => false
];
$log->RecordUnknown($msg, "debug");
$msg["message"] = "This is a single-line, unknown-type message";
$log->RecordUnknown($msg, "debug", $multiline = false);