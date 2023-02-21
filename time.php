<?php

class TIME extends DateTime
{
  function __construct(
    int|float|string $time = "now",
    public string $stringFormat = "Y-m-d H:i:s",
    public string $stampFormat = "U.v",
    public DateTimeZone|null $timezone = null)
  {
    if($time === null) $time = "now";
    $timezone = $timezone ?: new DateTimeZone("UTC");
    if(is_number($time)) {
      parent::__construct("now", $timezone);
      $this->setStamp($time);
    }
    else if($this->isInterval($time)) {
      parent::__construct("now", $timezone);
      $this->setInterval($time);
    }
    else if($this->isIntervals($time)) {
      parent::__construct("now", $timezone);
      $this->setIntervals($time);
    }
    else parent::__construct($time, $timezone);
  }

  function setStamp(int|float $timestamp)
  {
    $us = fmod($timestamp, 1);
    $this->setTimestamp($timestamp);
    $this->setMicrosecond($us);
  }

  function getStamp(): float
  {
    return (float)$this->format($this->stampFormat);
  }

  function modifyStamp(float $modyfier)
  {
    $this->setStamp($this->getStamp() + $modyfier);
  }

  function getString(?string $format = NULL): string
  {
    $format = $format ?: $this->stringFormat;
    return $this->format($format);
  }

  function __toString()
  {
    return $this->getString();
  }

  static function isInterval(string $interval) : bool
  {
    if(preg_match("/^(\-|\+)?[0-9]*\.?[0-9]+(y|mo|w|d|h|m|s|ms|µs|us)$/", trim($interval))) return true;
    return false;
  }

  static function isIntervals(string $interval) : bool
  {
    if(preg_match("/^((\-|\+)?[0-9]*\.?[0-9]+(y|mo|w|d|h|m|s|ms|µs|us) ?)*$/", trim($interval))) return true;
    return false;
  }

  function setInterval(string $interval)
  {
    preg_match("/\-?[0-9]*\.?[0-9]+/", $interval, $value);
    if(isset($value[0])) $value = $value[0];
    else $value = 0;
    $interval = strtolower($interval);
    $factor = preg_replace("/[^a-z]/", "", $interval);
    if($factor == "y" || $factor == "mo") {
      $str = match($factor) {
        "y" => $value . " year",
        "mo" => $value . " month"
      };
      $this->modify($str);
      return;
    }
    $this->modifyStamp(match($factor) {
      "w" => 7 * 24 * 3600 * $value,
      "d" => 24 * 3600 * $value,
      "h" => 3600 * $value,
      "m" => 60 * $value,
      "s" => $value,
      "ms" => $value / (10 ^ 3),
      "us", "µs" => $value / (10 ^ 6)
    });
  }

  function setIntervals(string $intervals)
  {
    $intervals = explode_spaces($intervals);
    foreach($intervals as $interval) {
      $this->setInterval($interval);
    }
  }

  function createByInterval(string $interval): TIME
  {
    $new = clone $this;
    $new->setInterval($interval);
    return $new;
  }

  function createByIntervals(string $intervals)
  {
    $intervals = explode_spaces($intervals);
    foreach($intervals as $interval) {
      if(!isset($new)) $new = clone $this;
      $new->setInterval($interval);
    }
  }

  function setYear(int $year) {
    $this->setDate($year, 1, 1);
    $this->setTime(0, 0);
  }
  
  function setMonth(int $month) {
    $this->setDate($this->format("Y"), $month, 1);
    $this->setTime(0, 0);
  }

  function setDay(int $day) {
    $this->setDate($this->format("Y"), $this->format("m"), $day);
    $this->setTime(0, 0);
  }

  function setHour(int $hour) {
    $this->setTime($hour, 0);
  }

  function setMinute(int $minute) {
    $this->setTime($this->format("H"), $minute);
  }

  function setSecond(int $second) {
    $this->setTime($this->format("H"), $this->format("i"), $second);
  }

  function setMicrosecond(int|float $us) {
    if($us < 1) $us *= 10 ^ 6;
    $this->setTime($this->format("H"), $this->format("i"), $this->format("s"), $us);
  }

  function Round(string $factor)
  {
    $factor = strtolower($factor);
    $factor = preg_replace("/[^a-z]/", "", $factor);
    switch($factor) {
      case "year": case "y": $this->setMonth(1); break;
      case "month": case "mo": $this->setDay(1); break;
      case "week": case "day": case "w": case "d": $this->setHour(0); break;
      case "hour": case "h": $this->setMinute(0); break;
      case "minute": case "m": $this->setSecond(0); break;
      case "second": case "s":
      case "millisecond": case "ms":
      case "microsecond":case "us": case "µs": $this->setMicrosecond(0); break;
    }
  }

  function createByRound(string $factor): TIME
  {
    $new = clone $this;
    $new->Round($factor);
    return $new;
  }

  function createByCopy(): TIME
  {
    return clone $this;
  }

  function IsGreaterThen(TIME $time): bool
  {
    if($this->getStamp() > $time->getStamp()) return true;
    else return false;
  }

  function IsGreaterEqualThen(TIME $time): bool
  {
    if($this->getStamp() >= $time->getStamp()) return true;
    else return false;
  }

  function IsSmallerThen(TIME $time): bool
  {
    if($this->getStamp() < $time->getStamp()) return true;
    else return false;
  }

  function IsSmallerEqualThen(TIME $time): bool
  {
    if($this->getStamp() <= $time->getStamp()) return true;
    else return false;
  }

  // function createByLoc(FVS &$fvs, string $name): TIME|null
  // {
  //   $time = $fvs->Load($name);
  //   if($time) $time = new TIME($time, $this->stringFormat, $this->stampFormat, $this->timezone);
  //   return $time;
  // }

  // function Tick(FVS &$fvs, string $name, string $interval)
  // {
  //   if($last = $this->createByLoc($fvs, $name)) {
  //     $limit = $last->createByInterval($interval);
  //     if($this->IsGreaterThen($limit)) {
  //       $fvs->Save($name, $this->getString());
  //       return true;
  //     }
  //   }
  //   $fvs->Save($name, $this->getString());
  //   return false;
  // }

  // function WatchdogRise(FVS &$fvs, string $time, string $state): bool
  // {
  //   $fvs->Save($time, $this->getString());
  //   $rise = false;
  //   if(!$fvs->Load($state)) {
  //     $fvs->Save($state, True);
  //     $rise = true;
  //   }
  //   return $rise;
  // }

  // function WatchdogFall(FVS &$fvs, string $time, string $state, string $interval): ?TIME
  // {
  //   $last = $this->createByLoc($fvs, $time);
  //   if($last) {
  //     $limit = $last->createByInterval($interval);
  //     if($this->IsGreaterThen($limit)) {
  //       if($fvs->Load($state)) {
  //         $fvs->Save($state, False);
  //         return $last;
  //       }
  //     }
  //   }
  //   return NULL;
  // }
}