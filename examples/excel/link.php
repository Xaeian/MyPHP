<?php

require_once(__DIR__ . "/../../lib.php");
include_library("mysql", "excel");

use excel\EXCEL;
use excel\CELL;

$mode = ["int", "str"];
$head = [new CELL("ID"), new CELL("Link")];
$head[0]->Head($mode[0]);
$head[1]->Head($mode[1]);
$row = [new CELL(1), new CELL("Website")];
$row[0]->Mode($mode[0]);
$row[1]->Mode($mode[1]);
$row[1]->link = "https://www.google.com/";

$body = [[$head, $row]];
$excel = new EXCEL($body, ["Sheet"], "example-link", __DIR__);
$excel->Run();