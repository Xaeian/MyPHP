<?php

class LOG
{
  private const DATETIME_STRING = [
    "s" => "Y-m-d H:i:s",
    "ms" => "Y-m-d H:i:s.v",
    "us" => "Y-m-d H:i:s.u"
  ];

  public string $buffer = "";

  function setPrecision(string $precision)
  {
    $this->precision = match(strtolower($precision)) {
      "second", "s" => "s",
      "millisecond", "ms" => "ms",
      "microsecond", "µs", "us" => "us"
    };
  }

  function __construct(
    private string $path = ROOT_PATH . "/main.log",
    private int $limit = 0,
    private string $precision = "s",
    public bool $utc = true,
    public int $headLength = 5,
    public int $msgLimit = 0,
    public bool $echo = false
    )
  {
    $this->setPrecision($precision);
    $this->ini = ini_load("log");
  }

  static function FileDeleteFirstLine(string $file, int|null $write_position = NULL)
  {
    if ($fp = fopen($file, "c+")) {
      if (flock($fp, LOCK_EX)) {
        while (($line = fgets($fp)) !== false) {
          if (!isset($write_position)) $write_position = 0;
          else {
            $read_position = ftell($fp);
            fseek($fp, $write_position);
            fputs($fp, $line);
            fseek($fp, $read_position);
            $write_position += strlen($line);
          }
        }
        fflush($fp);
        ftruncate($fp, $write_position);
        flock($fp, LOCK_UN);
      }
      fclose($fp);
    }
  }

  static function FileLinesCount(string $file)
  {
    $count = 0;
    if (file_exists($file)) {
      if ($fp = fopen($file, "r")) {
        while (!feof($fp)) {
          $line = fgets($fp);
          $count = $count + substr_count($line, PHP_EOL);
        }
        fclose($fp);
      }
    }
    return $count;
  }
  
  function Record(string $msg, string $head = "msg", bool $multiline = true)
  {
    if($this->limit) {
      $n = LOG::FileLinesCount($this->path);
      while($n > $this->limit) {
        LOG::FileDeleteFirstLine($this->path);
        $n--;
      }
    }
    $time = ($this->utc) ? gmdate(self::DATETIME_STRING[$this->precision]) : date(self::DATETIME_STRING[$this->precision]);
    $head = str_pad(strtoupper($head), $this->headLength);
    $msg = ($this->msgLimit && strlen($msg) > $this->msgLimit) ? substr($msg, 0, $this->msgLimit - 3) . "..." : $msg;
    $msg = trim($msg);
    if($multiline) {
      $parts = explode_enter($msg);
      $msg = array_shift($parts);
      foreach($parts as $i => $part) {
        $nbr = strval($i + 1);
        $msg .= PHP_EOL . $nbr . str_repeat(" ", strlen($time) + strlen($head) + 2 - strlen($nbr)) . $part;
      }
    }
    else {
      $msg = preg_replace("/[\r\n]+/", $this->ini["spaceSingleLine"], $msg);
    }
    $msg = $time . " " . $head  . " " . $msg . PHP_EOL;
    if($fp = fopen($this->path, "a")) {
      fwrite($fp, $msg);
      if($this->echo) echo($msg);
      fclose($fp);
    }
    return $this;
  }

  function RecordUnknown(mixed $msg, string $head = "msg", bool $multiline = true)
  {
    if(!is_scalar($msg)) {
      if($multiline) $msg = json_encode_pretty($msg);
      else $msg = json_encode($msg);
    }
    else if(!is_string($msg)) $msg = json_encode($msg);
    $this->Record($msg, $head, $multiline);
    return $this;
  }

  function Error(string $text)
  {
    $this->Record($text, "error");
    return $this;
  }

  function Warning(string $text)
  {
    $this->Record($text, "warn");
    return $this;
  }
  
  function Info(string $text)
  {
    $this->Record($text, "info");
    return $this;
  }

  function Debug(string $text)
  {
    $this->Record($text, "debug");
    return $this;
  }
  
  function Push(string ...$msgs)
  {
    foreach($msgs as $msg)
      if($msg != "") $this->buffer .= $msg . $this->ini["spacePush"];
    return $this;
  }
  
  function Reset()
  {
    $this->buffer = "";
    return $this;
  }
  
  function Send(string $head)
  {
    if($this->buffer) {
      $this->Record($this->buffer, $head);
      $this->Reset();
    }
    return $this;
  }
}
