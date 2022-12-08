<?php

include_library("time");

class CRON
{
  private TIME $time;

  function __construct(public FVS &$fvs)
  {
    $this->time = new TIME();
  }

  function Refresh(?TIME &$time = NULL)
  {
    $this->time = new TIME();
  }

  private function Next(TIME $run, string $interval): TIME
  {
    while($run->IsSmallerThen($this->time)) {
      $run->setInterval($interval);
    }
    return $run;
  }

  /**
    * Whether the given `$name` process is to be executed.
    * The process is executed from `$init` with` $interval`.
    * The next execution time is saved in the `$this->fvs` variable system.
    * They will be scheduled at `$interval` thereafter.
    * The prefix `cron-` will be appended to the variable `$name`.
    */
  public function Task(string $name, string $interval, string $init = "2022-01-01 00:00:00"): bool
  {
    if($run = $this->time->createByLoc($this->fvs, $name)) {
      if($run->IsSmallerThen($this->time)) {
        $run = $this->Next($run, $interval);
        $this->fvs->Save($name, $run->getString());
        return true;
      }
      return false;
    } else {
      $run = new TIME($init);
      $run = $this->Next($run, $interval);
      $this->fvs->Save($name, $run->getString());
      return false;
    }
    return false;
  }
}