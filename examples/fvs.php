<?php

require_once(__DIR__ . "/../lib.php");
include_library("fvs");

$fvs = new FVS(__DIR__ . "/var/");

$fvs->Save("int", 12);
$fvs->Save("float", 7.83);
$fvs->Save("str", "text");
$fvs->Save("bool", True);
$fvs->Save("null", null);
$fvs->Save("array", [9, 5.79, "text2", False, null]);
$fvs->Save("object", (object)["int"=>7, "float"=>2.45, "str"=>"text3", "bool"=>True, "null"=>null]);

disp(
  $fvs->Load("int"),
  $fvs->Load("float"),
  $fvs->Load("str"),
  $fvs->Load("bool"),
  $fvs->Load("null"),
  $fvs->Load("array"),
  $fvs->Load("object"),
  $fvs
);