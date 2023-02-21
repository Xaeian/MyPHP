<?php

require_once(__DIR__ . "/../__main.php");
include_library("var", "time");

//------------------------------------------------------------------------------------------------- FVAR

$fvar = new FVAR(__DIR__ . "/var/");

$fvar->Set("int", 12);
$fvar->Set("float", 7.83);
$fvar->Set("str", "text");
$fvar->Set("bool", True);
$fvar->Set("null", null);
$fvar->Set("array", [9, 5.79, "text2", False, null]);
$fvar->Set("object", (object)["int"=>7, "float"=>2.45, "str"=>"text3", "bool"=>True, "null"=>null]);

disp_type(
  $fvar->Get("int"),
  $fvar->Get("float"),
  $fvar->Get("str"),
  $fvar->Get("bool"),
  $fvar->Get("null"),
  $fvar->Get("array"),
  $fvar->Get("object")
);

$fvar->Save(["var1" => 1, "var2" => 2, "var3" => 3]);
disp($fvar->Load()); // $dbvar->Dump();
disp($fvar->Load(["var1", "var2", "var3"]));

//------------------------------------------------------------------------------------------------- DBVAR

use db\MYSQL;
use db\SQLITE;

$conn = new MYSQL("dbvar", "localhost", "root", "sqrt");
// $conn = new SQLITE(__DIR__); TODO Test
$dbvar = new DBVAR($conn, "var");
$dbvar->Build();

$dbvar->Set("int", 12);
$dbvar->Set("float", 7.83);
$dbvar->Set("str", "text");
$dbvar->Set("bool", True);
$dbvar->Set("null", null);
$dbvar->Set("array", [9, 5.79, "text2", False, null]);
$dbvar->Set("object", (object)["int"=>7, "float"=>2.45, "str"=>"text3", "bool"=>True, "null"=>null]);

disp_type(
  $dbvar->Get("int"),
  $dbvar->Get("float"),
  $dbvar->Get("str"),
  $dbvar->Get("bool"),
  $dbvar->Get("null"),
  $dbvar->Get("array"),
  $dbvar->Get("object")
);

$dbvar->Save(["var1" => 1, "var2" => 2, "var3" => 3]);
disp($dbvar->Load()); // $dbvar->Dump();
disp($dbvar->Load(["var1", "var2", "var3"]));

//------------------------------------------------------------------------------------------------- TIME

$time = new TIME();
$dbvar->Set("time", (string)$time);
$fvar->Set("time", (string)$time);
$time1 = new TIME($dbvar->Get("time"));
$time2 = new TIME($fvar->Get("time"));
disp_type($time1, $time2);