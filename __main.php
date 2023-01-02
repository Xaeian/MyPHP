<?php

//---------------------------------------------------------------------------------------------------------------------

const LIB_PATH = __DIR__ . "/";
defined('ROOT_PATH') or define('ROOT_PATH', LIB_PATH);
$_INI = parse_ini_file(LIB_PATH . "settings.ini", true);
define('COMPOSER_PATH', $_INI["composer"]);

/**
 * Load system variables for libraries `$lib`
 */
function ini_load(string $lib)
{
  global $_INI;
  return $_INI[$lib];
}

/**
 * Include other libraries from names `$libs`
 */
function include_library(string ...$libs)
{
  foreach ($libs as $lib)
    require_once(LIB_PATH . certain_suffix($lib, ".php"));
}

require_once(LIB_PATH . "_arg.php");
require_once(LIB_PATH . "_csv.php");
require_once(LIB_PATH . "_disp.php");
require_once(LIB_PATH . "_rand.php");

//---------------------------------------------------------------------------------------------------------------------

function path_pretty(string $path, bool $file = true): string
{
  $in = explode_path($path);
  if($in[0] != "" && !str_contains($in[0], ":")) {
    $relative = true;
  } else $relative = false;

  $out = [];
  foreach($in as $x) {
    if($x == ".");
    else if($x == "..") array_pop($out);
    else if($x != "") array_push($out, $x);
  }
  $path = "";
  foreach($out as $x) $path .= $x . "/";
  if($file) $path = rtrim($path, "/");

  if($relative) $path = "./" . $path;
  else if(!str_contains($in[0], ":")) $path = "/" . $path;
  return $path;
}

function string_to_data(string $str): array
{
  $data = [];
  for($i = 0; $i < strlen($str); $i++)
    array_push($data, ord($str[$i]));
  return $data;
}

function data_to_string(array $data): string
{
  $str = "";
  foreach ($data as $char) $str .= chr((int)$char);
  return $str;
}

function required_fields(object $object, array $fields): ?string
{
  foreach($fields as $field) {
    if(!property_exists($object, $field)) return $field;
  }
  return null;  
}

//---------------------------------------------------------------------------------------------------------------------

/**
 * Checks whether the given `$value` can be treated as a number 
 */
function is_number(mixed $value): bool
{
  if (preg_match("/^\-?[0-9]*\.?[0-9]+$/", $value)) {
    if (!preg_match("/^\-?0[0-9]+\.?[0-9]+$/", $value)) return true;
    return false;
  }
  return false;
}

/**
 * Checks if `$subject` is a one-dimensional array 
 */
function is_vector(mixed &$subject)
{
  if (!is_array($subject)) return false;
  foreach ($subject as $i => $sub) {
    if (is_array($sub)) return false;
  }
  return true;
}

function is_assoc(array $array)
{
  if(array() === $array) return false;
  return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Zamienia `$subject` jest tablicą jednowymiarową
 * ...
 */
function to_vector(mixed &$subject, $length = 1)
{
  if (!is_array($subject)) {
    $value = $subject;
    if (is_numeric($value)) $subject = vector_init_inc($length, $value);
    else $subject = vector_init($length, $value);
    return true;
  }
  return false;
}

/**
 * Zwraca konwersję `$subject` na tablicą jednowymiarową
 * ...
 */
function make_vector($subject, $length = 1)
{
  to_vector($subject);
  return $subject;
}

function objectarray_to_assocarray(array $object, string $key = "key", string $value = "value")
{
  $assoc = [];
  foreach ($object as $cell) $assoc[$cell->{$key}] = $cell->{$value};
  return $assoc;
}

function to_array2d(&$subject, $flat = false)
{
  to_vector($subject);
  if ($flat) {
    if (is_vector($subject)) $subject = [$subject];
  } else {
    foreach ($subject as $i => $sub) to_vector($subject[$i]);
  }
}

function like_names($string)
{
  return str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($string))));
}

// TODO: drop
function same_names(string $name1, string $name2): bool
{
  $name1 = strtolower_utf8(preg_replace('/[^0-9A-Za-zżźćńółęąśŻŹĆĄŚĘŁÓŃ]+/', "", $name1));
  $name2 = strtolower_utf8(preg_replace('/[^0-9A-Za-zżźćńółęąśŻŹĆĄŚĘŁÓŃ]+/', "", $name2));
  if ($name1 == $name2) return true;
  return false;
}

function strtolower_utf8($string): string
{
  return mb_strtolower($string, "UTF-8");
}

function str_pad_utf8($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT): string
{
  return str_pad($input, strlen($input) - mb_strlen($input, "UTF-8") + $pad_length, $pad_string, $pad_type);
}

function utf8_with_bom($string): string
{
  return chr(239) . chr(187) . chr(191) . $string;
}

//--------------------------------------------------------------------------------------------------------------------- HTML

function linktype_form_htmltag(string $tag): string
{
  if(preg_match("/^\s*<link.+href.+\/>\s*$/", $tag)) return "css";
  if(preg_match("/^\s*<script.+src.+><\/script>\s*$/", $tag)) return "js";
  return "";
}

function link_form_htmltag(string $tag): string
{
  if(preg_match("/(href|src)=(\".*[^\\\\]\")|('.*[^\\\\]')/", $tag, $matchs)) {
    $link = trim($matchs[2], "\"'");
    if(!preg_match("/^(http:|https:)/", $link)) $link = "https:" . $link;
    return $link;
  }
  return "";
}

//--------------------------------------------------------------------------------------------------------------------- Color

function hex2rgb(string $color)
{
	if(empty($color)) return [0, 0, 0]; 
  if($color[0] == '#' ) $color = substr( $color, 1 );
  if(strlen($color) == 6) $hex = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
  else if(strlen($color) == 3 ) $hex = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
  else return [0, 0, 0];
  return array_map("hexdec", $hex); 
}

//--------------------------------------------------------------------------------------------------------------------- Filse

function dir_make($location)
{
  if(is_dir($location)) return;
  $folders = explode_path($location);
  $url = "";
  foreach($folders as $folder) {          
    $url = $url . $folder . "/";
    if(!is_dir($url)) mkdir($url, 0777);
  }
}

function file_load($location)
{
  $limit = ini_load("fileRepeat");
  while($limit--) {
    $content = @file_get_contents($location);
    if($content === false) continue;
    return $content;
  }
  return NULL;
}

function file_load_lines($location)
{
  if($file = file_load($location)) {
    $file = preg_replace("/[\r\n]+/", "\n", $file);
    return explode("\n", $file);
  }
  return [];
}

function file_save($location, $content): bool
{
  $limit = ini_load("fileRepeat");
  if(!file_exists($location)) dir_make(dirname($location));
  while($limit--) {
    if(file_put_contents($location, $content)) return true;
    @unlink($location);
  }
  return false;
}

function file_delete($location)
{
  $limit = ini_load("fileRepeat");
  while($limit--) {
    if(@unlink($location)) return true;
  }
  return false;
}

function csv_load(string $location, bool $head = true, string $sep = ",", string $enc = "\"", string $esc = "\\"): ?array
{
  $location = certain_suffix($location, ".csv");
  $content = file_load($location);
  if($content === NULL) return NULL;
  return csv_decode($content, $head, $sep, $enc, $esc);
}

function csv_save(string $location, array $array, string $sep = ",", string $enc = "\""): bool
{
  $location = certain_suffix($location, ".csv");
  return file_save($location, csv_encode($array, $sep, $enc));
}

function json_load(string $location): mixed
{
  $location = certain_suffix($location, ".json");
  $content = file_load($location);
  if($content === NULL) return NULL;
  return json_decode($content);
}

function json_save(string $location, mixed $object)
{
  $location = certain_suffix($location, ".json");
  file_save($location, json_encode($object));
}

function json_delete($location)
{
  $location = certain_suffix($location, ".json");
  file_delete($location);
}

function json_encode_pretty(object|array $object)
{
  $json = json_encode($object, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  $json = str_replace("\u0022", '\"', $json);

  $json = preg_replace('/^(  +?)\\1(?=[^ ])/m', "$1", $json);
  if (preg_match_all('/\[\n\ *(.|\,\n){1,64}\n\ *\]/', $json, $matches)) {
    $matches = array_unique($matches[0]);
    foreach ($matches as $matche) {
      $replacement = preg_replace(['/\,\n\ */', '/\n\ */'], [", ", ""], $matche);
      $json = str_replace($matche, $replacement, $json);
    }
  }
  return $json;
}

function json_save_pretty(string $location, object|array $object)
{
  $location = certain_suffix($location, ".json");
  file_save($location, json_encode_pretty($object));
}

function sql_load($location): array|null|bool
{
  $location = certain_suffix($location, ".sql");
  if (file_exists($location)) return split_sql(file_get_contents($location));
  else return false;
}

function sql_save(string $location, array|string $sqls)
{
  $location = certain_suffix($location, ".sql");
  $string = "";
  foreach ($sqls as $sql) $string .= $sql . ";" . PHP_EOL;
  $string = rtrim($string);
  file_save($location, json_encode($string));
}

function data_load(string $location)
{
  if (file_exists($location)) $string = file_get_contents($location);
  else return false;
  return string_to_data($string);
}

function data_save(string $location, array $data)
{
  file_save($location, data_to_string($data));
}

function file_list($location = "./", $extension = [], $regex_and = [], $regex_or = [], $sort = 0)
{
  $location = preg_replace("/[\\/]+$/", "", $location) . "/";
  to_vector($extension);
  to_vector($regex_and);
  to_vector($regex_or);

  foreach ($extension as $ext) {
    $ext = preg_replace("/[^a-zA-Z0-9]+/", "", $ext);
    array_push($regex_or, "/\." . $ext . "$/");
  }

  $list = scandir($location, $sort);
  $output = [];

  foreach ($list as $name) {
    if (is_file($location . $name)) {
      if (count($regex_or)) {
        $ok = false;
        foreach ($regex_or as $ro)
          if (preg_match($ro, $name)) {
            $ok = true;
            break;
          }
      } else $ok = true;

      if ($ok && count($regex_and)) {
        foreach ($regex_and as $ra)
          if (!preg_match($ra, $name)) {
            $ok = false;
            break;
          }
      }
      if ($ok) array_push($output, $name);
    }
  }
  return $output;
}

function file_remover($location = "./", $extension = [], $regex_and = [], $regex_or = [])
{
  $file_list = file_list($location, $extension, $regex_and, $regex_or, 0);
  foreach ($file_list as $name) unlink($location . $name);
}

function file_loader($location = "./", $mode = "file", $extension = [], $regex_and = [], $regex_or = [], $sort = 0)
{
  $location = preg_replace("/[\\/]+$/", "", $location) . "/";
  $list = file_list($location, $extension, $regex_and, $regex_or, $sort);
  $output = [];
  foreach ($list as $name) {
    $path = $location . $name;
    switch ($mode) {
      case "file":
        $output[$name] = file_load($path);
        break;
      case "data":
        $output[$name] = data_load($path);
        break;
      case "json":
        $output[$name] = json_load($path);
        break;
      case "csv":
        $output[$name] = csv_load($path);
        break;
    }
  }
  return $output;
}

function file_saver($location = "./", $list = [], $mode = "file")
{
  $location = preg_replace("/[\\/]+$/", "", $location) . "/";

  foreach ($list as $name => $content) {
    if (!file_exists($location)) mkdir($location, 0777, true);
    $path = $location . $name;

    switch ($mode) {
      case "file":
        file_save($path, $content);
        break;
      case "data":
        data_save($path, $content);
        break;
      case "json":
        json_save($path, $content);
        break;
      case "csv":
        csv_save($path, $content);
        break;
    }
  }
}

function folder_delete($location, bool $alsoThisOne = true)
{
  if (file_exists($location)) {
    $it = new RecursiveDirectoryIterator($location, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }
    if($alsoThisOne) rmdir($location);
  }
}

function folder_copy($source, $dest)
{
  if (!is_dir($source)) {
    copy($source, $dest);
    return;
  }

  $dir = opendir($source);
  @mkdir($dest);
  while (false !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($source . '/' . $file)) folder_copy($source . '/' . $file, $dest . '/' . $file);
      else copy($source . '/' . $file, $dest . '/' . $file);
    }
  }
  closedir($dir);
}

function require_once_folder($location)
{
  $location = preg_replace("/[\\/]+$/", "", $location) . "/";
  $files = file_list($location);

  foreach ($files as $file)
    require_once($location . $file);
}

function msleep($msec)
{
  if ($msec >= 0) {
    usleep(1000 * intval($msec));
  }
}

function remove_prefix(string $string, string $prefix) : string
{
  $pos = strpos($string, $prefix);
  if($pos === 0) $string = substr($string, strlen($prefix));
  return $string;
}

function remove_suffix(string $string, string $suffix) : string
{
  $pos = strrpos($string, $suffix);
  $length = strlen($string) - strlen($suffix);
  if($pos === $length) $string = substr($string, 0, $length);
  return $string;
}

function certain_prefix(string $string, string $prefix) : string
{
  return $prefix . remove_prefix($string, $prefix);
}

function certain_suffix(string $string, string $suffix) : string
{
  return remove_suffix($string, $suffix) . $suffix;
}

function str_replace_right(string $search, string $replace, string $subject) : string
{
  $pos = strrpos($subject, $search);
  if ($pos !== false)
    $subject = substr_replace($subject, $replace, $pos, strlen($search));
  return $subject;
}

/**
 * Convert keys to values in the array `$map` in the string `$subject`.
 * Key is `$search` and the value is `$replace`.
 */
function str_replace_assoc(array $map, string $subject, string $prefix = "", string $suffix = "") : string
{
  foreach($map as $search => $replace) {
    $subject = str_replace($prefix . $search. $suffix, $replace, $subject);
  }
  return $subject;
}

function explode_enter(string $string, int $limit = PHP_INT_MAX): array
{
  $string = trim(preg_replace("/[\r\n]+/", "\n", $string));
  return explode("\n", $string, $limit);
}

function explode_spaces(string $string, int $limit = PHP_INT_MAX): array
{
  $string = trim(preg_replace("/[\s]+/", " ", $string));
  return explode(" ", $string, $limit);
}

function explode_path(string $string, int $limit = PHP_INT_MAX): array
{
  $string = str_replace("\\", "/", $string);
  $string = preg_replace('/[\/]+/', "/", $string);
  return explode("/", $string, $limit);
}

function explode_noempty(string $separator, string $string, int $limit = PHP_INT_MAX): array
{
  $array = [];
  $explode = explode($separator, $string, $limit);
  foreach($explode as $value) {
    if($value !== "") array_push($array, $value);
  }
  return $array;
}

function vector_init_inc(int $length, float $start = 1.0, float|null $stop = NULL)
{
  if ($stop === NULL) $stop = $start;

  $vector = [];
  if (($length - 1)) $step = ($stop - $start) / ($length - 1);
  else $step = 0;

  for ($i = 0; $i < $length; $i++) {
    $vector[$i] = $start;
    $start += $step;
  }
  return $vector;
}

function vector_init(int $length, mixed $string)
{
  $vector = [];
  for ($i = 0; $i < $length; $i++) $vector[$i] = $string;
  return $vector;
}

function vector_set($vector, $set)
{
  foreach ($vector as $i => $value) $vector[$i] = $set;
  return $vector;
}

function vector_sum($vector)
{
  $sum = 0;
  foreach ($vector as $i => $value) $sum += $vector[$i];
  return $sum;
}

function vector_abs($vector)
{
  foreach ($vector as $i => $value) $vector[$i] = abs($vector[$i]);
  return $vector;
}

function vector_pow($vector)
{
  foreach ($vector as $i => $value) $vector[$i] = $vector[$i] * $vector[$i];
  return $vector;
}

function vector_div($vector, $div)
{
  if ($div) {
    foreach ($vector as $i => $value) $vector[$i] /= $div;
  }
  return $vector;
}

function vector_multi($vector, $multi)
{
  foreach ($vector as $i => $value) $vector[$i] *= $multi;
  return $vector;
}

function vector_increase($vector)
{
  $sum = 0;
  foreach ($vector as $i => $value) {
    $sum += $vector[$i];
    $vector[$i] = $sum;
  }
  return $vector;
}

//--------------------------------------------------------------------------------------------------------------------- <--- Deep

/**
 *  The function gets from `$subject` the `$property`.
 *  When the array is multidimensional, the function returns the property array one dimension smaller. 
 */
function deep_property(array|object $subject, string $property) : mixed
{
  if (is_array($subject)) {
    $return = [];
    foreach ($subject as $i => $sub)
      $return[$i] = deep_property($sub, $property);
    return $return;
  } else return $subject->$property;
}

function array_drop_nulls(array $array): array
{
  return array_filter($array, fn($value) => !is_null($value) && $value !== "");
}

function array_min(array $array): float|null
{
  $min = INF;
  foreach($array as $value) {
    if(is_number($value) && $value < $min) $min = $value;
  }
  if($min != INF) return $min;
  return NULL;
}

function array_max(array $array): float|null
{
  $max = -INF;
  foreach($array as $value) {
    if(is_number($value) && $value > $max) $max = $value;
  }
  if($max != -INF) return $max;
  return NULL;
}

function array_avg(array $array): float|null
{
  $count = 0;
  $sum = 0;
  foreach($array as $value) {
    if(is_number($value)) {
      $sum += $value;
      $count++;
    }
  }
  if($count) return $sum / $count;
  return NULL;
}

function array_median(array $array): float|null
{
  $array = array_drop_nulls($array);
  sort($array);
  $count = count($array);
  $mid = floor(($count - 1) / 2);
  if($count % 2) return $array[$mid];
  else if($count) return ($array[$mid] + $array[$mid + 1]) / 2;
  else return NULL;
}

function array_sum_null(array $array): float|null
{
  $return = false;
  $sum = 0;
  foreach($array as $value) {
    if(is_number($value)) {
      $sum += $value;
      $return = true;
    }
  }
  if($return) return $sum;
  return NULL;
}

function array_bool_changes(array $array): int|null
{
  $return = false;
  $changes = 0;
  foreach($array as $cell) {
    if(is_number($cell)) {
      if(isset($value) && $value != $cell) $changes++;
      $value = $cell;
      $return = true;
    }
  }
  if($return) return $changes;
  return NULL;
}

/**
 * Allows you to call another function `$callback` on nested arrays in `$array` to return `returns` array's 
 */
function array_call(array $array, string $callback = "max")
{
  if(is_array($array)) {
    if(is_vector($array)) return call_user_func($callback, $array);
    $return = [];
    foreach($array as $i => $sub)
      $return[$i] = array_call($sub, $callback);
    return $return;
  }
  return $array;
}

function matrix_transpose($array)
{
  if(is_vector($array)) {
    $out = [];
    foreach ($array as $key => $value) {
      $out[$key] = [];
      $out[$key][0] = $value;
    }
    return $out;
  }

  $out = [];
  foreach ($array as $key => $sub_array) {
    foreach ($sub_array as $sub_key => $value)
      $out[$sub_key][$key] = $value;
  }
  return $out;
}

//--------------------------------------------------------------------------------------------------------------------- <--- Split

/**
  * Splits the `$str` string into string arrays
  * with the option of using the `$split` character inside the `$stringChar` tags
  * and including the $escapeChar escape character.
  * Behaves like in **bash** for default parameters.  
 */
function split(string $str, string $split = " ", string $stringChar = '"', string $escapeChar = "\\"): array
{
  $array = [];
  $k = 0;
  $array[$k] = "";
  $mute = false;
  $muteStart = false;
  $escape = false;
  $inc = 0;

  $str = (array)str_split($str);
  $split = (array)str_split($split);
  $count = count($split);

  for ($i = 0; $i < count($str); $i++) {
    if ($str[$i] === $stringChar) {
      if (!$mute) {
        $muteStart = true;
        $mute = true;
      }
    }
    if (!$mute) {
      for ($j = 0; $j < $count; $j++) {
        if ($str[$i + $j] == $split[$j]) {
          if ($j + 1 == $count) {
            if (!$inc) $array[$k] = str_replace($stringChar, "", $array[$k]);
            $inc = 0;
            $k++;
            $array[$k] = "";
            $i += $j;
            continue 2;
          }
        } else break;
      }
      $inc++;
    }
    $array[$k] .= $str[$i];
    if ($mute && !$muteStart) {
      if ($str[$i] == $escapeChar) $escape = true;
      else if (!$escape && $str[$i] == $stringChar) $mute = false;
      else $escape = false;
    }
    $muteStart = false;
    if ($i + 1 == count($str)) if (!$inc) $array[$k] = str_replace($stringChar, "", $array[$k]);
  }
  return $array;
}

function split_sql(string $sqls): array
{
  $sqls = split($sqls, ";", "'");
  foreach ($sqls as $i => $sql) {
    $sql = preg_replace("/[\n\r]+/", "", $sql);
    $sql = preg_replace("/[\ ]+/", " ", $sql);
    $sql = preg_replace("/\ ?\(\ ?/", "(", $sql);
    $sql = preg_replace("/\ ?\)\ ?/", ")", $sql);
    $sql = preg_replace("/\ ?\,\ ?/", ",", $sql);
    $sql = preg_replace("/\ ?\=\ ?/", "=", $sql);
    $sqls[$i] = $sql;
  }
  $output = [];
  $i = 0;
  foreach ($sqls as $sql) {
    if ($sql && $sql != " ") {
      $output[$i] = $sql;
      $i++;
    }
  }
  return $output;
}

//--------------------------------------------------------------------------------------------------------------------- <--- END