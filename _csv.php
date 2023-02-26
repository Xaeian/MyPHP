<?php

function csv_decode(string $string, bool $head = true, string $sep = ",", string $enc = "\"", string $esc = "\\"): array
{
  $array = [];
  $rows = explode_enter($string);
  if($head) $head = str_getcsv(array_shift($rows), $sep, $enc, $esc);
  foreach($rows as $i => $row) {
    $row = str_getcsv($row, $sep, $enc, $esc);
    foreach($row as $j => $value) {
      if($value === "") $row[$j] = NULL;
    }
    if($head) $row = array_combine($head, $row);
    $array[$i] = $row;
  }
  return $array;
}

function csv_encode(array $array, string $sep = ",", string $enc = "\""): string
{
  foreach($array as $i => $row) {
    if(is_object($row)) $array[$i] = (array)$row; // Conversion from object to array
  }
  $head = is_assoc($array[0]) ? array_keys($array[0]) : false;
  $string = "";
  if($head) {
    foreach($head as $value) $string .= $value . $sep;
    $string = substr($string, 0, -strlen($sep)) . PHP_EOL;
  }
  foreach($array as $row) {
    foreach($row as $value) {
      $str = ($value === NULL ? "" : ($value === false ? "False" : ($value === true ? "True" :
        (is_string($value) ? str_replace($enc, $enc . $enc, trim($value)): $value))));
      $string .= (str_contains($str, " ") ? $enc . $str . $enc : $str) . $sep;
    }
    $string = substr($string, 0, -strlen($sep)) . PHP_EOL;
  }
  return $string;
}