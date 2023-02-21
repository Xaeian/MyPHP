<?php

include_library("mysql", "sqlite");

use db\MYSQL;
use db\SQLITE;

//------------------------------------------------------------------------------------------------- FVAR

class FVAR
{
  function __construct(
    private string $path = ROOT_PATH . "/var/"
  ) {
    $this->path = path_pretty($path, false);
  }

  function Get(string $name): mixed
  {
    return json_load($this->path . $name);
  }

  function Set(string $name, mixed $value)
  {
    json_save($this->path . $name, $value);
  }

  function Dump(): array
  {
    $values = [];
    foreach(file_loader($this->path, "json", "json") as $var => $value) {
      $values[remove_suffix($var, ".json")] = $value;
    }
    return $values;
  }

  function Load(?array $names = NULL): array
  {
    if(!$names) return $this->Dump();
    $values = [];
    foreach($names as $name) {
      $values[$name] = $this->Get($name);
    }
    return $values;
  }

  function Save(array|object $values)
  {
    foreach($values as $name => $value) {
      $this->Set($name, $value);
    }
  }

  function Drop(string|array $names)
  {
    to_vector($names);
    foreach($names as $name) $this->Set($name, NULL);
  }

  function Clear(): void
  {
    file_remover($this->path, "json");
  }

  function __toString(): string
  {
    return "FVAR:" . $this->path;
  }
}

//------------------------------------------------------------------------------------------------- DBVAR

class DBVAR
{
  private const MYSQL =
  <<<SQL
  CREATE TABLE IF NOT EXISTS {table} (
    name VARCHAR(64) NOT NULL UNIQUE,
    value VARCHAR({size}),
    PRIMARY KEY (name)
  );
  SQL;

  private const SQLITE =
  <<<SQL
  CREATE TABLE IF NOT EXISTS {table} (
    name TEXT NOT NULL PRIMARY KEY,
    value TEXT
  );
  SQL;

  private string $update;

  function __construct(
    private MYSQL|SQLITE &$conn,
    public string $table = "var",
    public int $size = 255, // for MySQL
    // TODO: function encode
    // TODO: function decode
  ) {
    $this->update = match($this->conn instanceof MYSQL) {
      true => "ON DUPLICATE KEY UPDATE",
      false => "ON CONFLICT(name) DO UPDATE SET"
    };
  }

  function Build(): bool
  {
    $this->conn->createDatabase();
    if($this->conn instanceof MYSQL) $sql = self::MYSQL;
    else $sql = self::SQLITE;
    $sql = str_replace_assoc(["table" => $this->table,  "size" => $this->size], $sql, "{", "}");
    return (bool)$this->conn->Exec($sql);
  }

  function Get(string $name): mixed
  {
    $sql = "SELECT value FROM $this->table WHERE name = '$name';";
    $value = $this->conn->getValue($sql);
    if($value === NULL) return NULL;
    return json_decode($value);
  }

  function Set(string $name, mixed $value): bool
  {
    $name = $this->conn->EncodeInsert($name);
    $value = $this->conn->EncodeInsert(json_encode($value));
    if(strlen($value) > $this->size) $value = "null"; // TODO: LOG Error
    $sql = "INSERT INTO $this->table VALUES ($name, $value) $this->update value = $value;";;
    return (bool)$this->conn->Exec($sql);
  }

  function Dump(): array
  {
    $sql = "SELECT name, value FROM $this->table";
    $array = $this->conn->getArrayAssoc($sql);
    $values = [];
    foreach($array as $row) {
      $values[$row["name"]] = json_decode($row["value"]);
    }
    return $values;
  }

  /**
   * Reading a group of variables
   * @param ?array $names List of variable names.
   *   Returns all variables if `NULL`
   * @return array values array where `$name => $value`
   */
  function Load(?array $names = NULL): array
  {
    if(!$names) return $this->Dump();
    $sql = "SELECT name, value FROM $this->table WHERE";
    foreach($names as $name) {
      $name = $this->conn->EncodeInsert($name);
      $sql .= " name = $name OR";
    }
    $sql = remove_suffix($sql, " OR") . ";";
    $array = $this->conn->getArrayAssoc($sql);
    $values = [];
    foreach($names as $name) {
      $values[$name] = NULL;
      foreach($array as $row) {
        if($row["name"] == $name) {
          $values[$name] = json_decode($row["value"]);
          break;
        }
      }
    }
    return $values;
  }

  function Save(array|object $values): bool
  {
    $sqls = [];
    foreach($values as $name => $value) {
      $name = $this->conn->EncodeInsert($name);
      $value = $this->conn->EncodeInsert(json_encode($value));
      if(strlen($value) > $this->size) $value = "null"; // TODO: LOG Error
      $sql = "INSERT INTO $this->table VALUES ($name, $value) $this->update value = $value;";
      array_push($sqls, $sql);
    }
    return (bool)$this->conn->Exec($sqls);
  }

  function Drop(string|array $names): bool
  {
    return (bool)$this->conn->delete($this->table, $names, "name"); // TODO
  }

  function Clear(): bool
  {
    return (bool)$this->conn->clear($this->table);
  }

  function __toString(): string
  {
    return "DBVAR:" . $this->conn->db . "/" . $this->table;
  }
}

//-------------------------------------------------------------------------------------------------
