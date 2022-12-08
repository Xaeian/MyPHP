<?php

require_once(__DIR__ . "/../lib.php");
include_library("timer");

$timer = new TIMER("ms");
$n = 3;

disp("Start");
while($n) {
  $ms = $timer->Interval(1500);
  disp("Left", $n, "(" . $ms . "ms)");
  $n--;
}
disp("End");