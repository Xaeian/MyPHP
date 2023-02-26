<?php

class LOG_FILE {
  private  $text;
  function __construct(mixed $subject) {
    if(is_scalar($subject) || (is_object($subject) && method_exists($subject, "__toString")))
      $this->text = (string)$subject;
    else $this->text = json_encode_pretty($subject);
  }
  public function __toString() {
    return $this->text;
  }
}

class LOG
{
  private const DATETIME_STRING = [
    "s" => "Y-m-d H:i:s",
    "ms" => "Y-m-d H:i:s.v",
    "us" => "Y-m-d H:i:s.u"
  ];

  public mixed $buffer = [];

  function setPrecision(string $precision)
  {
    $this->precision = match(strtolower($precision)) {
      "second", "s" => "s",
      "millisecond", "ms" => "ms",
      "microsecond", "µs", "us" => "us"
    };
  }

  public int $headLimit = 5;
  public int $dropCount = 1000;
  public int $randCount = 8;
  public string $space = " ";
  public bool $echo = false;

  function __construct(
    private string $path = ROOT_PATH . "/myphp.log",
    private string $container = ROOT_PATH . "/log/", # global
    private int $limit = 0,
    public bool $utc = true,
    public string $precision = "s"
    )
  {
    $this->container = path_pretty($container, false);
    dir_make($this->container);
    $this->setPrecision($precision);
  }

  static function File(mixed $subject): LOG_FILE
  {
    return new LOG_FILE($subject);
  }
  
  static function Var(mixed $subject): mixed
  {
    if($subject === null) return "null";
    else if(is_string($subject)) return '"' . $subject . '"';
    else return $subject;
  }

  static function FileDeleteFirstLines(string $file, int $count = 1)
  {
    $lines = file($file);
    array_splice($lines, 0, $count);
    file_put_contents($file, $lines);
  }

  static function FileLinesCount(string $file)
  {
    $count = 0;
    if(file_exists($file)) {
      if($fp = fopen($file, "r")) {
        while(!feof($fp)) {
          $line = fgets($fp);
          $count = $count + substr_count($line, PHP_EOL);
        }
        fclose($fp);
      }
    }
    return $count;
  }
  
  function Record(string $message)
  {
    $message = trim($message) . PHP_EOL;
    if($fp = fopen($this->path, "a")) {
      fwrite($fp, $message);
      if($this->echo) echo($message);
      fclose($fp);
    }
    return $this;
  }

  function Filename($ext)
  {
    do {
      $file = $this->container . rand_string($this->randCount) . "." . $ext;
    } while(file_exists($file));
    return $file;
  }

  function Note(string $head = "note", mixed ...$subjects)
  {
    $text = "";
    foreach($subjects as $subject) {
      if(is_scalar($subject)) {
        $last = mb_substr($subject, -1);
        $text .= trim($subject, " \t\x00") . ($last == ":" || $last == "=" ? "" : " ");
      }
      else {
        if(is_object($subject) && method_exists($subject, "__toString")) {
          $file = $this->Filename("txt");
          file_save($file, (string)$subject);
        }
        else {
          $file = $this->Filename("json");
          json_save_pretty($file, $subject);
        }
        $text .= "file://$file ";
      }
    }
    $text = trim($text);
    if($this->limit && LOG::FileLinesCount($this->path) > $this->limit)
      LOG::FileDeleteFirstLines($this->path, $this->dropCount);
    $time = ($this->utc) ? gmdate(self::DATETIME_STRING[$this->precision]) : date(self::DATETIME_STRING[$this->precision]);
    $head = str_pad(strtoupper($head), $this->headLimit);
    $lines = explode_enter($text);
    $text = array_shift($lines);
    foreach($lines as $i => $line)
      $text .= PHP_EOL . str_repeat(" ", strlen($time) + strlen($head) + 2) . trim($line);
    return $this->Record("$time $head $text");
  }

  function Error(mixed ...$subjects)
  {
    return $this->Note("error", ...$subjects);
  }

  function Warning(mixed ...$subjects)
  {
    return $this->Note("warn", ...$subjects);
  }
  
  function Info(mixed ...$subjects)
  {
    return $this->Note("info", ...$subjects);
  }

  function Debug(mixed ...$subjects)
  {
    return $this->Note("debug", ...$subjects);
  }
  
  function Push(mixed ...$subjects)
  {
    array_push($this->buffer, ...$subjects);
    return $this;
  }
  
  function Reset()
  {
    $this->buffer = [];
    return $this;
  }
  
  function Send(string $head = "note")
  {
    if($this->buffer) {
      $this->Note($head, ...$this->buffer);
      $this->Reset();
    }
    return $this;
  }
}
