<?php

require_once(__DIR__ . "/../lib.php");
include_library("timer");

$timer = new TIMER("ms");

$timer->Start();
sleep(1);
$time = $timer->Value();
disp("sleep(1):", $time . "ms");
sleep(2);
$time = $timer->Start();
disp("sleep(1+2):", $time . "ms");
sleep(2);
$time = $timer->Value();
disp("sleep(2):", $time . "ms");