<?php

namespace lib;

include_library("excel", "pdf");

use excel\CELL;

//------------------------------------------------------------------------------------------------

class SARY
{
  public $head;
  public $body;
  public $rowCount;
  public $columnCount;

  //function to_vector(&$subject, $length = 1)
  //{
  //if(!is_array($subject))
  //{
  //$value = $subject;
  //$subject = vector_init($length, $value);
  //}
  //}

  //function to_array2d(&$subject)


  function setBody($body)
  {
    $new = [];
    $i = 0;
    $j = 0;
    foreach($body as $row) {
      $new[$i] = [];
      $j = 0;
      foreach($row as $cell) {
        $cell = trim($cell); // if str
        $new[$i][$j] = (is_number($cell)) ? floatval($cell) : $cell;
        $j++;
      }
      $i++;
    }

    $this->rowCount = $i;
    $this->columnCount = $j;

    $this->body = $new;
    return $new;
  }

  function setHead($head)
  {
    $new = [];
    $i = 0;
    foreach($head as $cell) {
      $new[$i] = trim($cell);
      $i++;
    }
    $this->head = $new;
    return $new;
  }

  private function ListReverse($list)
  {
    $reverse = [];
    foreach($this->head as $i => $head) {
      $add = true;
      foreach($list as $column) {
        if($i == $column) {
          $add = false;
          break;
        }
      }
      if($add) array_push($reverse, $i);
    }
    return $reverse;
  }

  function __construct($body = [[]], $head = [])
  {
    to_vector($head);
    to_array2d($body);
    $this->setHead($head);
    $this->setBody($body);
  }

  static function VectorToSary($bodyArray, $headValue)
  {
    $body = [];
    $head = [$headValue];
    foreach($bodyArray as $i => $value) {
      $body[$i] = [];
      $body[$i][0] = $value;
    }
    return new SARY($body, $head);
  }

  function Contact($sary, $split = NULL)
  {
    if($this->columnCount != $sary->columnCount) return;
    if($split === NULL || $split > $this->rowCount) $split = $this->rowCount;
    $new_body = [];
    $j = 0;
    for($i = 0; $i < $split; $i++) {
      $new_body[$j] = $this->body[$i];
      $j++;
    }
    for($i = 0; $i < $sary->rowCount; $i++) {
      $new_body[$j] = $sary->body[$i];
      $j++;
    }
    for($i = $split; $i < $this->rowCount; $i++) {
      $new_body[$j] = $this->body[$i];
      $j++;
    }
    return new SARY($new_body, $this->head);
  }

  function ContactBefore($sary)
  {
    $this->Contact($sary, 0);
  }

  function Cut($sary, $split = NULL)
  {
  }

  function Merge($sary, $split = NULL)
  {
    if($split === NULL || $split > $this->columnCount) $split = $this->columnCount;
    if($sary->rowCount > $this->rowCount) $this->rowCount = $sary->rowCount;
    $new_body = [];
    for($i = 0; $i < $this->rowCount; $i++) {
      $k = 0;
      for($j = 0; $j < $split; $j++) {
        if(isset($this->body[$i][$j])) $new_body[$i][$k] = $this->body[$i][$j];
        else $new_body[$i][$k] = NULL;
        $k++;
      }
      for($j = 0; $j < $sary->columnCount; $j++) {
        if(isset($sary->body[$i][$j])) $new_body[$i][$k] = $sary->body[$i][$j];
        else $new_body[$i][$k] = NULL;
        $k++;
      }
      for($j = $split; $j < $this->columnCount; $j++) {
        if(isset($this->body[$i][$j])) $new_body[$i][$k] = $this->body[$i][$j];
        else $new_body[$i][$k] = NULL;
        $k++;
      }
    }
    $new_head = [];
    $k = 0;
    for($j = 0; $j < $split; $j++) {
      $new_head[$k] = $this->head[$j];
      $k++;
    }
    for($j = 0; $j < $sary->columnCount; $j++) {
      $new_head[$k] = $sary->head[$j];
      $k++;
    }
    for($j = $split; $j < $this->columnCount; $j++) {
      $new_head[$k] = $this->head[$j];
      $k++;
    }
    $this->head = $new_head;
    return new SARY($new_body, $new_head);
  }

  function MergeBefore($sary)
  {
    return $this->Merge($sary, 0);
  }

  function Select(array|string $list, bool $blacklist = false): SARY
  {
    to_vector($list);
    $list = $this->NamesToIndexes($list);
    if($blacklist) $list = $this->ListReverse($list);
    $new_body = [];
    foreach($this->body as $i => $row) {
      $new_body[$i] = [];
      $j = 0;
      foreach($row as $k => $cell) {
        foreach($list as $column) {
          if($k == $column) {
            $new_body[$i][$j] = $cell;
            $j++;
            break;
          }
        }
      }
    }
    $new_head = [];
    $j = 0;
    foreach($this->head as $k => $head) {
      foreach($list as $column) {
        if($k == $column) {
          $new_head[$j] = $head;
          $j++;
          break;
        }
      }
    }
    return new SARY($new_body, $new_head);
  }

  function Drop(array|string $list): SARY
  {
    to_vector($list);
    return $this->Select($list, true);
  }

  //-------------------------------------------------------------------------------------------------------------------

  function Max(int|string $column)
  {
    $array = $this->Column($column);
    return array_max($array);
  }

  function Min(int|string $column)
  {
    $array = $this->Column($column);
    return array_min($array);
  }

  function Avg(int|string $column)
  {
    $array = $this->Column($column);
    return array_avg($array);
  }

  function Median(int|string $column)
  {
    $array = $this->Column($column);
    return array_median($array);
  }

  function Trues(int|string $column): int
  {
    $array = $this->Column($column);
    $count = 0;
    foreach($array as $value)
      if($value) $count++;
    return $count;
  }

  function Falses(int|string $column): int
  {
    $array = $this->Column($column);
    $count = 0;
    foreach($array as $value)
      if($value) $count++;
    return $count;
  }

  //-------------------------------------------------------------------------------------------------------------------

  private function StringSizeColumn()
  {
    $size = [];
    foreach($this->head as $i => $cell) $size[$i] = strlen($cell);
    foreach($this->body as $row) {
      foreach($row as $i => $cell) {
        $len = strlen($cell);
        if(!isset($size[$i]) || $len > $size[$i]) $size[$i] = $len;
      }
    }
    return $size;
  }

  function Print()
  {
    $size = $this->StringSizeColumn();
    echo PHP_EOL;
    if($this->head) {
      foreach($this->head as $i => $cell) echo str_pad_utf8($cell, $size[$i] + 1);
      echo PHP_EOL;
      foreach($this->head as $i => $cell) echo str_repeat("-", $size[$i]) . " ";
      echo PHP_EOL;
    }
    foreach($this->body as $row) {
      foreach($row as $i => $cell) echo str_pad_utf8($cell, $size[$i] + 1);
      echo PHP_EOL;
    }
    echo PHP_EOL;
  }

  //-------------------------------------------------------------------------------------------------------------------

  private function NameToIndex(int|string $column): int
  {
    if(is_int($column)) return $column;
    foreach($this->head as $j => $head) {
      if(strtolower_utf8($head) == strtolower_utf8($column))
        return $j;
    }
    return $column;
  }

  private function NamesToIndexes(array $list)
  {
    foreach($list as $i => $name)
      $list[$i] = $this->NameToIndex($name);
    return $list;
  }

  private function AssocToIndexes($list)
  {
    $newList = [];
    foreach($list as $name => $value) {
      $index = $this->NameToIndex($name);
      $newList[$index] = $value;
    }
    return $newList;
  }

  function OrderBy($list)
  {
    $column = $this->AssocToIndexes($list);
    if(!count($column)) return $this;
    foreach($column as $col => $order) {
      $colarr[$col] = [];
      foreach($this->body as $k => $row) {
        $colarr[$col]["_" . $k] = strtolower($row[$col]);
      }
    }
    $eval = "array_multisort(";
    foreach($column as $col => $order) $eval .= "\$colarr[" . $col . "]," . $order . ",";
    $eval = substr($eval, 0, -1) . ");";
    eval($eval);
    $new_body = [];
    foreach($colarr as $col => $arr) {
      foreach($arr as $k => $v) {
        $k = substr($k, 1);
        if(!isset($new_body[$k])) $new_body[$k] = $this->body[$k];
        $new_body[$k][$col] = $this->body[$k][$col];
      }
    }
    return new SARY($new_body, $this->head);
  }

  function ScaleOffset($list, $scale = 1, $offset = 0)
  {
    $new_body = $this->body;
    $list = $this->NamesToIndexes($list);
    foreach($new_body as $i => $row) {
      foreach($list as $column)
        $new_body[$i][$column] = $scale * floatval($new_body[$i][$column]) + $offset;
    }
    return new SARY($new_body, $this->head);
  }

  function Juxta($uniq = [], $nbr = [], $txt = [], $sep = "; ")
  {
    $uniq = $this->NamesToIndexes($uniq);
    $nbr = $this->NamesToIndexes($nbr);
    $txt = $this->NamesToIndexes($txt);
    $data_new = [];
    $active = 0;
    $k = 0;
    for($i = 0; $i < sizeof($this->body); $i++) {
      $new = true;
      for($j = 0; $j < sizeof($data_new); $j++) {
        $thisame = true;
        if($uniq) {
          foreach($uniq as $u) {
            if(strtolower_utf8(trim($this->body[$i][$u])) != strtolower_utf8(trim($data_new[$j][$u]))) {
              $thisame = false;
              break;
            }
          }
        } else $thisame = false;
        if($thisame) {
          $active = $j;
          $new = false;
          break;
        }
      }
      if($new) {
        for($j = 0; $j < sizeof($this->head); $j++) $data_new[$k][$j] = trim($this->body[$i][$j]);
        $k++;
      } else {
        if($nbr) foreach($nbr as $n) if(is_numeric($this->body[$i][$n])) $data_new[$active][$n] += $this->body[$i][$n];
        if($txt) foreach($txt as $t) if($this->body[$i][$t] !== '') $data_new[$active][$t] .= $sep . $this->body[$i][$t];
      }
    }
    $sary = $this;
    $sary->body = $data_new;
    return $sary;
  }

  public function Rename(string $old, string $new)
  {
    foreach($this->head as $i => $head) {
      if($head == $old) {
        $this->head[$i] = $new;
        return;
      }
    }
  }

  function Row(int $index): array
  {
    return array_combine($this->head, $this->body[$index]);
  }

  function Length(): int
  {
    return count($this->body);
  }

  private $i;

  function Foreach(): ?array
  {
    if(!isset($this->i)) $this->i = 0;
    else $this->i++;
    if($this->i >= $this->Length()) {
      unset($this->i);
      return NULL;
    }
    return $this->Row($this->i);
  }

  function StrReplace(array|string $search, array|string $replace, int|string $name)
  {
    $column = $this->Column($name);
    foreach($column as $i => $value)
      $column[$i] = str_replace($search, $replace, $value);
    $this->setColumn($name, $column);
  }

  function Column(string $name): array
  {
    $index = $this->NameToIndex($name);
    $column = [];
    foreach($this->body as $i => $row)
      $column[$i] = $row[$index];
    return $column;
  }

  function setColumn(string $name, array $column)
  {
    $index = $this->NameToIndex($name);
    foreach($this->body as $i => $_) {
      if(isset($column[$i])) {
        $this->body[$i][$index] = $column[$i];
      }
    }
  }

  function TwoColumns(string $keys, string $values): array
  {
    return array_combine($this->Column($keys), $this->Column($values));
  }

  function setTwoColumns(string $keys, string $values, array $array)
  {
    $this->setColumn($keys, array_keys($array));
    $this->setColumn($values, array_values($array));
  }

  public function Assoc(): array
  {
    $array = [];
    foreach($this->body as $i => $row) {
      $array[$i] = [];
      foreach($row as $j => $cell)
        $array[$i][$this->head[$j]] = $cell;
    }
    return $array;
  }

  public static function LoadAssoc(array $array): SARY
  {
    $body = [];
    $head = array_keys($array[0]);
    foreach($array as $i => $row) {
      $body[$i] = [];
      foreach($row as $value) array_push($body[$i], $value);
    }
    return new SARY($body, $head);
  }

  public static function LoadDatabaseSQL(&$conn, string $sql): ?SARY
  {
    $array = $conn->getArrayAssoc($sql);
    if(!$array) return NULL;
    return SARY::LoadAssoc($array);
  }

  function toCSV(string $sep = ";"): string
  {
    $array = $this->Assoc();
    return csv_encode($array, $sep);
  }

  public static function fromCSV(string $string, string $sep = ";"): SARY
  {
    $csv = csv_decode($string, true, $sep);
    return new SARY($csv, array_keys($csv[0]));
  }

  function Sheet($mode)
  {
    $sheet = [];
    foreach($this->head as $j => $head) {
      $sheet[0][$j] = new CELL($head);
      $sheet[0][$j]->Head($mode[$j]);
    }
    foreach($this->body as $i => $row) {
      foreach($row as $j => $cell) {
        $sheet[$i + 1][$j] = new CELL($cell);
        if($cell === null || $cell === "") $sheet[$i + 1][$j]->Mode("null");
        else $sheet[$i + 1][$j]->Mode($mode[$j]);
      }
    }
    return $sheet;
  }
}
