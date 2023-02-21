<?php

include_library("time", "var");

class CRON
{
  function __construct(public FVAR|DBVAR &$var, public TIME &$time)
  {
    $this->time = new TIME();
  }

  private function Next(TIME $time, string $interval): TIME
  {
    while($time->IsSmallerThen($this->time)) {
      $time->setInterval($interval);
    }
    return $time;
  }

  /**
    * Whether the given `$name` process is to be executed.
    * The process is executed from `$init` with` $interval`.
    * The next execution time is saved in the `$this->fvs` variable system.
    * They will be scheduled at `$interval` thereafter.
    * The prefix `cron-` will be appended to the variable `$name`.
    */
  function Task(string $name, string $interval, string $init = "2023-01-01 00:00:00"): bool
  {
    $strtime = $this->var->Get($name);    
    if($strtime) {
      $time = new TIME($strtime);
      if($time->IsSmallerThen($this->time)) {
        $time = $this->Next($time, $interval);
        $this->var->Set($name, (string)$time);
        return true;
      }
      return false;
    }
    else {
      $time = new TIME($init);
      $time = $this->Next($time, $interval);
      $this->var->Set($name, (string)$time);
      return false;
    }
    return false;
  }

  function Rise(string $nameTime, string $nameState): bool
  {
    $this->var->Set($nameTime, (string)$this->time);
    $rise = false;
    if(!$this->var->Get($nameState)) {
      $this->var->Set($nameState, True);
      $rise = true;
    }
    return $rise;
  }

  function Fall(string $nameTime, string $nameState, string $interval): ?TIME
  {
    $strlast = $this->var->Get($nameTime);
    if($strlast) {
      $last = new TIME($strlast);
      $next = $last->createByInterval($interval);
      if($this->time->IsGreaterThen($next)) {
        if($this->var->Get($nameState)) {
          $this->var->Set($nameState, False);
          return $last;
        }
      }
    }
    return NULL;
  }
}