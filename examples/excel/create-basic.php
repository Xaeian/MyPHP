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

$body = [[
    [new CELL("id", "s", "right", $style), new CELL("name", "s", "left", $style)],
    [new CELL(1, "n", "right"), new CELL("first-page", "s", "left")]
  ],[
    [new CELL("id2", "s", "right", $style), new CELL("name2", "s", "left", $style)],
    [new CELL(2, "n", "right"), new CELL("secound-page", "s", "left")]
  ]
];

$excel = new EXCEL($body, ["Sheet1", "Sheet2"], "example-create", __DIR__);
$excel->Run();