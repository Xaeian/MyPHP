<?php

class PROGRESS
{
  private $array = [
    ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'],
    ['⠁', '⠈', '⠐', '⠠', '⢀', '⡀', '⠄', '⠂']
  ];

  // TODO: [....................] 

  public int $step;
  public float $progress;

  function __construct(public string $title, public int $steps, public int $style = 0, public int $percentPrecision = 2)
  {
    if ($this->style >= count($this->array)) $this->style = 0;
    $this->step = 0;
  }

  function Run()
  {
    $this->step++;
    $value = $this->step . "/" . $this->steps;
    $this->progress = bcdiv($this->step, $this->steps, $this->percentPrecision + 2);
    $percent = number_format(100 * $this->progress, $this->percentPrecision) . "%";
    $char = ($this->step === $this->steps ? " " : $this->array[$this->style][$this->step % count($this->array[$this->style])]);

    echo "{$this->title}: $value [{$percent}] {$char}\r";
    if ($char === " ") echo "\n";
  }

  function getProgress()
  {
    return $this->progress;
  }
}