<?php

require_once(__DIR__ . "/../lib.php");
include_library("mysql", "excel");

use excel\EXCEL;
use excel\CELL;

$style = [
  "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
  "borders" => ["bottom" => ["borderStyle" => "thin"]],
  "fill" => ["fillType" => "solid", "color" => ["rgb" => '101010']],
];

$id1 = new CELL(1);
$id1->Mode("int");
$id2 = new CELL(2);
$id2->Mode("int");

$body = [[
    [new CELL("id", "s", "right", $style), new CELL("name", "s", "left", $style)],
    [$id1, new CELL("first-page")]
  ],[
    [new CELL("id2", "s", "right", $style), new CELL("name2", "s", "left", $style)],
    [$id1, new CELL("secound-page")]
  ]
];

$excel = new EXCEL($body, ["Sheet1", "Sheet2"], "example-mode", __DIR__);
$excel->Run();