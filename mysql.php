<?php

namespace db;
include_library("time", "log");
use LOG;
use mysqli;
use Exception;

class MYSQL
{
  public LOG $log;

  function __construct(
    public string $db = "",
    public string $host = "localhost",
    public string $user = "root",
    public string $password = "",
    ?LOG $log = NULL
  ) {
    $nameLog  = "mysql-" . $host;
    $nameLog .= $db ? "-" . $db : "";
    $this->log = $log ? $log : new LOG();
  }
  //------------------------------------------------------------------------------------------------------------------- Execute

  function Run($sql)
  {
    try {
      $conn = new mysqli($this->host, $this->user, $this->password, $this->db);
      mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
      if($conn->connect_errno) throw new Exception(mysqli_connect_errno());
      else {
        $conn->set_charset("utf8");
        $result = $conn->query(trim($sql));
        $conn->close();
        if($result) return $result;
        else throw new Exception($conn->error);
      }
    } catch (Exception $e) {
      $message = preg_replace('/\s+/', " ", $e->getMessage());
      $this->log->Error($message);
      return null;
    }
  }

  function Transaction(array $sqls)
  {
    if(!$sqls) return NULL;
    try {
      $conn = new mysqli($this->host, $this->user, $this->password, $this->db);
      mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
      if($conn->connect_errno) throw new Exception(mysqli_connect_errno());
      else {
        $conn->set_charset("utf8");
        $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
        $results = [];
        foreach($sqls as $i => $sql) {
          if($sql != NULL) {
            $sql = trim($sql);
            $sql = rtrim($sql, ';');
            if(!$results[$i] = $conn->query($sql)) {
              $conn->rollback();
              $conn->close();
              throw new Exception($conn->error);
            }
          }
          else $results[$i] = NULL;
        }
        $conn->commit();
        $conn->close();
        return $results;
      }
    } catch (Exception $e) {
      $message = preg_replace('/\s+/', " ", $e->getMessage()); // TODO get code
      $this->log->Error($message);
      return NULL;
    }
  }

  function Exec(string|array $sql)
  {
    if(is_array($sql)) {
      $results = $this->Transaction($sql);
      if(!$results) return null;
      foreach($results as $result) {
        if(is_object($result)) return $result;
      }
      return true;
    }
    return $this->Run($sql);
  }
  
  //------------------------------------------------------------------------------------------------------------------- Convert

  public static function Decode(mixed $value) // from Database
  {
    if(is_string($value)) {
      $value = str_replace("&#039;", "'", $value);
      return html_entity_decode($value);
    } else return $value;
  }

  public static function EncodeLike(mixed $value) // to Database
  {
    if(!is_number($value)) {
      if($value == null) return "null";
      return "'" . htmlentities(strtolower_utf8(trim($value)), ENT_QUOTES) . "'";
    } else return $value;
  }

  public static function EncodeInsert(mixed $value) // to Database
  {
    if(!is_number($value)) {
      if($value == null) return "null";
      return "'" . htmlentities(trim($value), ENT_QUOTES) . "'";
    } else return $value;
  }
  //------------------------------------------------------------------------------------------------------------------- GET

  function getArrayAssoc($sql): ?array
  {
    $ary = [];
    $result = $this->Run($sql);
    if(!$result || $result->num_rows < 0) return NULL;
    $n = 0;
    while($row = $result->fetch_assoc()) {
      $ary[$n] = [];
      foreach($row as $name => $value) {
        $ary[$n][$name] = $this->Decode($value);
      }
      $n++;
    }
    return $ary;
  }

  function getRowAssoc($sql): ?array
  {
    $ary = $this->getArrayAssoc($sql);
    if(isset($ary[0])) return $ary[0];
    return NULL;
  }

  function getArrayNumber($sql): ?array
  {
    $ary = [];
    $result = $this->Run($sql);
    if(!$result || $result->num_rows < 0) return NULL;
    $n = 0;
    while($row = $result->fetch_row()) {
      $ary[$n] = [];
      foreach($row as $name => $value) {
        $ary[$n][$name] = $this->Decode($value);
      }
      $n++;
    }
    return $ary;
  }

  function getRowNumber($sql): ?array
  {
    $ary = $this->getArrayNumber($sql);
    if(isset($ary[0])) return $ary[0];
    return NULL;
  }

  function getColumn($sql): ?array
  {
    $col = [];
    $ary = $this->getArrayNumber($sql);
    if($ary) {
      foreach($ary as $i => $row) $col[$i] = $row[0];
      return $col;
    } else return NULL;
  }

  function getValue($sql): mixed
  {
    $result = $this->Exec($sql);
    if(!$result || $result->num_rows <= 0) return NULL;
    else if($row = $result->fetch_row()) return $this->Decode($row[0]);
    return NULL;
  }

  function getCount($table, $where = ""): int
  {
    if($where) $where = " WHERE " . $where;
    return $this->getValue("SELECT COUNT(*) FROM " . $table . $where . ";");
  }

  function getBool($sql): ?bool
  {
    $result = $this->Run($sql);
    if(!$result || $result->num_rows <= 0) return NULL;
    else if($result->fetch_row()) return true;
    else return false;
  }
  //------------------------------------------------------------------------------------------------------------------- ???

  function AutoincrementMaxIDSQL($table)
  {
    $sql = [];
    $sql[0] = "SELECT IFNULL((MAX(id)+1),1) INTO @inc FROM " . $table . ";";
    $sql[1] = "set @sql=CONCAT(\"ALTER TABLE " . $table . " AUTO_INCREMENT=\", @inc);";
    $sql[2] = "PREPARE stmt FROM @sql;";
    $sql[3] = "EXECUTE stmt;";
    $sql[4] = "DEALLOCATE PREPARE stmt;";
    return $sql;
  }

  function AutoincrementMaxID($table)
  {
    return $this->Transaction($this->AutoincrementMaxIDSQL($table));
  }

  function ReindexSQL($table)
  {
    $sql = [];
    $sql[0] = "SET @row = 0;";
    $sql[1] = "UPDATE " . $table . " SET id = @row := @row+1;";
    return array_merge($sql, $this->AutoincrementMaxIDSQL($table));
  }

  function Reindex($table)
  {
    $this->Transaction($this->ReindexSQL($table));
  }

  function getAutoincrement($table): mixed
  {
    $sql = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$this->db' AND TABLE_NAME = '$table';";
    return $this->getValue($sql);
  }

  function setAutoincrement($table, $value)
  {
    $sql = "ALTER TABLE $table AUTO_INCREMENT = $value;";
    $this->Run($sql);
  }

  function alocAutoincrementSQL(string $table, int $size)
  {
    $sql = [];
    $sql[0] = "SET @ainc = (SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$this->db' AND TABLE_NAME = '$table');";
    $sql[1] = "SET @sql = CONCAT(\"ALTER TABLE annotations AUTO_INCREMENT=\", @ainc + $size);";
    $sql[2] = "PREPARE stmt FROM @sql;";
    $sql[3] = "EXECUTE stmt;";
    $sql[4] = "DEALLOCATE PREPARE stmt;";
    $sql[5] = "SELECT @ainc;";
    return $sql;
  }

  function alocAutoincrement(string $table, int $size): int
  {
    return $this->getValue($this->alocAutoincrementSQL($table, $size)); // get value
  }

  //------------------------------------------------------------------------------------------------------------------- Database

  function createDatabase(?string $name = NULL, bool $setActive = true)
  {
    if(!$name) {
      $name = $this->db;
      $setActive = true;
      $this->db = "";
    }
    $name = $name ?: $this->db;
    $sql = "CREATE DATABASE IF NOT EXISTS {$name} CHARACTER SET utf8 COLLATE utf8_bin;";
    $result = $this->Run($sql);
    if($setActive) $this->db = $name;
    return $result;
  }

  function dropDatabase(string|null $name = NULL)
  {
    if(!$name) {
      $name = $this->db;
      $this->db = "";
    }
    $sql = "DROP DATABASE IF EXISTS '$name';";
    $this->Run($sql);
  }

  function isSetDatabase(string $name)
  {
    return $this->getBool("SHOW DATABASES LIKE '$name'");
  }
  //------------------------------------------------------------------------------------------------------------------- Table

  function dropTable($name)
  {
    return $this->Run("DROP TABLE IF EXISTS " . $name . ";");
  }

  function createTableLike($table, $like)
  {
    return $this->Run("CREATE TABLE IF NOT EXISTS " . $table . " LIKE " . $like . ";");
  }

  function copyTable($to, $from)
  {
    return $this->Run("INSERT INTO " . $to . " SELECT * FROM " . $from . ";");
  }

  function getTableUpdateTime($database, $table)
  {
    return $this->getValue("SELECT update_time FROM information_schema.tables WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$table';");
  }

  function getTableList()
  {
    return $this->getColumn("SHOW TABLES;");
  }
  //------------------------------------------------------------------------------------------------------------------- Column

  function getColumnNames(string $table, array $without = []): array
  {
    $sql = "SHOW COLUMNS FROM $table";
    if($without) {
      $sql .= " WHERE";
      foreach($without as $column) $sql .= "Field <> '$column' AND";
      $sql = remove_prefix($sql, " AND");
    }
    $sql .= ";";
    $res = $this->getArrayAssoc($sql);
    $list = [];
    foreach($res as $key => $row) array_push($list, $row["Field"]);
    return $list;
  }
  //------------------------------------------------------------------------------------------------------------------- Insert

  function insertRowSQL(string $table, array $row, bool $ignore = true)
  {
    $sql = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO {$table} VALUES(";
    foreach($row as $value) $sql .= $this->EncodeInsert($value) . ",";
    return rtrim($sql, ",") . ");";
  }

  function insertRow(string $table, array $row, bool $ignore = true)
  {
    return $this->Run($this->insertRowSQL($table, $row, $ignore));
  }

  function insertArraySQL(string $table, array $array, bool $ignore = true): string
  {
    if(!$array) return "";
    $sql = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO {$table} VALUES(";
    foreach($array as $row) {
      if(!$row) return "";
      foreach($row as $value) $sql .= $this->EncodeInsert($value) . ",";
      $sql = rtrim($sql, ",") . "),(";
    }
    return remove_suffix($sql, ",(") . ";";
  }

  function insertArray(string $table, array $array, bool $ignore = true)
  {
    return $this->Run($this->insertArraySQL($table, $array, $ignore));
  }
  
  //------------------------------------------------------------------------------------------------------------------- Update

  function updateValueSQL(string $table, string $column, mixed $value, int $id)
  {
    return "UPDATE {$table} SET {$column} = " . $this->EncodeInsert($value) . " WHERE id = " . $id . ";";
  }

  function updateValue(string $table, string $column, mixed $value, int $id)
  {
    return $this->Run($this->updateValueSQL($table, $column, $value, $id));
  }

  function updateRowSQL(string $table, int $id, array $row)
  {
    $sql = "UPDATE {$table} SET ";
    foreach($row as $column => $value)
      $sql .= $column . "=" . $this->EncodeInsert($value) . ",";
    $sql = rtrim($sql, ",");
    $sql .= " WHERE id=" . $id . ";";
    return $sql;
  }

  function updateRow(string $table, int $id, array $row)
  {
    return $this->Run($this->updateRowSQL($table, $id, $row));
  }

  function updateArraySQL(string $table, array $array)
  {
    $sqls = [];
    foreach($array as $id => $row) $sqls[$id] = $this->updateRowSQL($table, $id, $row);
    return $sqls;
  }

  function updateArray(string $table, array $array)
  {
    return $this->Transaction($this->updateArraySQL($table, $array));
  }
  //------------------------------------------------------------------------------------------------------------------- Delete

  function deleteSQL(string $table, mixed $keys, string $column = "id")
  {
    to_vector($keys);
    $sql = "DELETE FROM $table WHERE";
    foreach($keys as $key) {
      $key = $this->EncodeInsert($key);
      $sql .= " $column=$key AND";
    }
    $sql = remove_suffix($sql, " AND") . ";";
    return $sql;
  }

  function delete(string $table, mixed $keys, string $column = "id")
  {
    return $this->Run($this->deleteSQL($table, $keys, $column));
  }

  function deleteLastRowsSQL(string $table, $count)
  {
    return "DELETE FROM $table ORDER BY id DESC LIMIT $count;";
  }

  function deleteLastRows($table, $count)
  {
    return $this->Run($this->deleteLastRowsSQL($table, $count));
  }

  function clearSQL($table)
  {
    return "TRUNCATE TABLE $table";
  }

  function clear($table)
  {
    return $this->Run($this->clearSQL($table));
  }

  //TRUNCATE

  function getLastValue($table, $column)
  {
    return $this->getValue("SELECT " . $column . " FROM " . $table . " ORDER BY id DESC LIMIT 1;");
  }

  function getLastRowAssoc($table)
  {
    return $this->getRowAssoc("SELECT * FROM " . $table . " ORDER BY id DESC LIMIT 1;");
  }

  function getLastRowNumber($table)
  {
    return $this->getRowNumber("SELECT * FROM " . $table . " ORDER BY id DESC LIMIT 1;");
  }

  function getFirstValue($table, $column)
  {
    return $this->getValue("SELECT $column FROM $table ORDER BY id ASC LIMIT 1;");
  }
  //------------------------------------------------------------------------------------------------------------------- User

  function createUser($user, $host = "%", $password = "")
  {
    $sql = [];
    $sql[0] = "CREATE USER '" . $user . "'@'" . $host . "' IDENTIFIED BY '" . $password . "';";
    $sql[1] = "GRANT ALL PRIVILEGES ON *.* TO '" . $user . "'@'" . $host . "';";
    $sql[2] = "FLUSH PRIVILEGES;";
    return $this->Transaction($sql);
  }

  function dropUser($user, $host)
  {
    $sql = [];
    $sql[0] = "DROP USER '" . $user . "'@'" . $host . "';";
    $sql[1] = "FLUSH PRIVILEGES;";
    return $this->Transaction($sql);
  }

  function setReadOnlyUser($user, $host)
  {
    $sql = [];
    $sql[0] = "REVOKE ALL ON *.* FROM '" . $user . "'@'" . $host . "';";
    $sql[1] = "GRANT SELECT, SHOW VIEW, PROCESS, REPLICATION CLIENT ON *.* TO '" . $user . "'@'" . $host . "';";
    $sql[2] = "FLUSH PRIVILEGES;";
    return $this->Transaction($sql);
  }

  function setReadWriteUser($user, $host)
  {
    $sql = [];
    $sql[0] = "GRANT ALL PRIVILEGES ON *.* TO '" . $user . "'@'" . $host . "';";
    $sql[1] = "FLUSH PRIVILEGES;";
    return $this->Transaction($sql);
  }

  //------------------------------------------------------------------------------------------------------------------- Backup

  function Backup(string $backup = "backup")
  {
    $db = $this->db;
    $tables = $this->getTableList();
    $this->createDatabase($db . "_" . $backup, false);

    foreach($tables as $table) {
      $this->dropTable($db . "_" . $backup . "." . $table);
      $this->createTableLike($db . "_" . $backup . "." . $table, $table);
      $this->copyTable($db . "_" . $backup . "." . $table, $table);
    }
    $this->log->Info("Backup '$backup' $db");
  }

  function Restore(string $backup = "backup")
  {
    $db = $this->db;
    $tables = $this->getTableList();
    if(!$this->isSetDatabase($db . "_" . $backup)) return;

    foreach($tables as $table) {
      $this->dropTable($table);
      $this->createTableLike($table, $db . "_" . $backup . "." . $table);
      $this->copyTable($table, $db . "_" . $backup . "." . $table);
    }
    $this->log->Warning("Restore '$backup' $db");
  }
}
