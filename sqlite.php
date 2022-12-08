<?php

namespace db;
include_library("time", "log");
use SQLite3;
use SQLite3Result;

class SQLITE
{
  public SQLite3 $db;

  function newSQLite($path, $name): object
  {
    if (!$path) $path = "./";
    $this->path = rtrim($path, "\\/") . "/";
    $this->name = certain_suffix($name, ".sqlite");
    return new SQLite3($this->path . $this->name);
  }

  function __construct(
    public string $path = "./",
    public string $name = "db",
  ) {
    $this->db = $this->newSQLite($path, $name);
  }

  //------------------------------------------------------------------------------------------------------------------- <--- Execute

  function Run($sql): SQLite3Result
  {
    return $this->db->query($sql);
  }

  function Transaction($sqls): array
  {
    $results = [];
    $i = 0;
    $this->db->exec('BEGIN');
    foreach ($sqls as $sql) {
      $results[$i] = $this->db->query($sql);
      $i++;
    }
    $this->db->exec('COMMIT');
    return $results;
  }

  //------------------------------------------------------------------------------------------------------------------- <--- Convert

  public static function Decode(mixed $value) // from Database
  {
    return MYSQL::Decode($value);
  }

  public static function EncodeLike(mixed $value) // to Database
  {
    return MYSQL::EncodeLike($value);
  }

  public static function EncodeInsert(mixed $value) // to Database
  {
    return MYSQL::EncodeInsert($value);
  }

  //------------------------------------------------------------------------------------------------------------------- <--- GET

  function getArrayAssoc($sql)
  {
    $ary = [];
    $result = $this->Run($sql);
    $n = 0;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $ary[$n] = [];
      foreach ($row as $name => $cell) {
        $ary[$n][$name] = $this->Decode($cell);
      }
      $n++;
    }
    return $ary;
  }

  function getRowAssoc($sql)
  {
    $ary = $this->getArrayAssoc($sql);
    if (isset($ary[0])) return $ary[0];
    return null;
  }

  function getArrayNumber($sql)
  {
    $ary = [];
    $result = $this->Run($sql);
    $n = 0;
    while ($row = $result->fetchArray(SQLITE3_NUM)) {
      $ary[$n] = [];
      foreach ($row as $name => $cell) {
        $ary[$n][$name] = $this->Decode($cell);
      }
      $n++;
    }
    return $ary;
  }

  function getRowNumber($sql)
  {
    $ary = $this->getArrayNumber($sql);
    if (isset($ary[0])) return $ary[0];
    return null;
  }

  function getColumn($sql)
  {
    $col = [];
    $ary = $this->getArrayNumber($sql);
    if ($ary) {
      foreach ($ary as $i => $row) $col[$i] = $row[0];
      return $col;
    } else return null;
  }

  function getCell($sql)
  {
    $result = $this->Run($sql);
    if (!$result || $result->num_rows <= 0) return null;
    else if ($row = $result->fetchArray(SQLITE3_NUM)) return $this->Decode($row[0]);
    return null;
  }

  function getCount($table, $where = "")
  {
    if ($where) $where = " WHERE " . $where;
    return $this->getCell("SELECT COUNT(*) FROM " . $table . $where . ";");
  }

  function getBool($sql)
  {
    $result = $this->Run($sql);
    if (!$result || $result->num_rows <= 0) return null;
    else if ($result->fetchArray(SQLITE3_NUM)) return true;
    else return false;
  }

  function getColumnNames(string $table, array $without = []): array
  {
    $sql = "PRAGMA table_info('$table');";
    $res = $this->getArrayAssoc($sql);
    $list = [];
    foreach ($res as $key => $row) {
      $set = true;
      foreach ($without as $column) {
        if ($column == $row["name"]) {
          $set = false;
          break;
        }
      }
      if ($set) array_push($list, $row["name"]);
    }
    return $list;
  }

  //------------------------------------------------------------------------------------------------------------------- <--- Database

  function createDatabase(string $name = "db", bool $setActive = true)
  {
    if ($setActive) $this->db = $this->newSQLite($this->path, $name);
    else {
      $name = certain_suffix($name, ".sqlite");
      new SQLite3($this->path . $name);
    }
  }

  //------------------------------------------------------------------------------------------------------------------- <--- Table

  function dropTable(string $name)
  {
    return $this->Run("DROP TABLE IF EXISTS {$name};");
  }

  function getTableList()
  {
    return $this->getColumn("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY 1;");
  }

  //------------------------------------------------------------------------------------------------------------------- <--- Last/First

  function getLastCell($table, $column)
  {
    return $this->getCell("SELECT $column FROM $table ORDER BY id DESC LIMIT 1;");
  }

  function getLastRowAssoc($table)
  {
    return $this->getRowAssoc("SELECT * FROM $table ORDER BY id DESC LIMIT 1;");
  }

  function getLastRowNumber($table)
  {
    return $this->getRowNumber("SELECT * FROM $table ORDER BY id DESC LIMIT 1;");
  }

  function getFirstCell($table, $column)
  {
    return $this->getCell("SELECT $column FROM $table ORDER BY id ASC LIMIT 1;");
  }
}