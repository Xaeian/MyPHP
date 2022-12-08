<?php

/**
 * Converts the `$argv` vector to an object
 * Strings starting with `-` will be treated as a parameter
 * A string that follow will be their value
 * For several strings, inside the object  will be created array
 * A synonym for parameters should be placed in an associative `$map` table
 */
function arg_load(array $argv, array $map = []): object
{
  $output = new stdClass();
  foreach ($map as $i => $arg) {
    $param = preg_replace('/^\-+/', "", $arg);
    $output->{$param} = false;
  }
  $param = "";
  $output->{$param} = false;

  foreach ($argv as $i => $arg) {
    if(!$i) continue;
    if (substr($arg, 0, 1) == "-") {
      if ($map) {
        foreach ($map as $search => $replace) {
          if ($arg == $search) {
            $arg = $replace;
            break;
          }
        }
      }
      $param = preg_replace('/^\-+/', "", $arg);
      $output->{$param} = true;
    } else {
      if (is_bool($output->{$param})) $output->{$param} = $arg;
      else if (!is_array($output->{$param})) $output->{$param} = [$output->{$param}, $arg];
      else array_push($output->{$param}, $arg);
    }
  }
  return $output;
}

/**
 * Sets default values on an object `$arg` if not entered
 * The default values should be put as values in the associative array xxx.
 * The keys of this array point to the parameters of the arguments 
 */
function arg_default(object &$arg, array $map = [])
{
  foreach($map as $property => $default) {
    if (property_exists($arg, $property) && $arg->{$property});
    else $arg->{$property} = $default;
  }
}