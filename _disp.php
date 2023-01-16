<?php

function disp_serialize_string(string $str, int $limit = 128): string
{
  if(strlen($str) > $limit)
    $str = substr($str, 0, $limit - 3) . "...";
  return $str;
}

function disp_serialize_array(array $prints, bool $type = false): string
{
  $assoc = is_assoc($prints) ? true : false;
  $disp = "";
  foreach ($prints as $key => $print) {
    if($assoc) $disp .= $key . ":";
    if($type) $disp .= "(" .gettype($print) . ")";
    if(is_scalar($print) || $print === NULL) {
      if(is_string($print)) $disp .= disp_serialize_string($print);
      else $disp .= json_encode($print);
    }
    else {
      if(is_object($print)) {
        if(method_exists($print, "Disp")) {
          $disp .= "{";
          $disp .= $print->Disp();
          $disp .= "}";
        }
        else $print = (array)$print;
      }
      if(is_array($print)) {
        $disp .= "[";
        $disp .= disp_serialize_array($print, $type);
        $disp .= "]";
      }
    }
    $disp .= " ";
  }
  return rtrim($disp);
}

function disp(mixed ...$prints)
{
  print(disp_serialize_array($prints) . PHP_EOL);
}

function disp_type(mixed ...$prints)
{
  print(disp_serialize_array($prints, true) . PHP_EOL);
}

function var_dump_html($object)
{
  echo "<pre>";
  var_dump($object);
  echo "</pre>";
}