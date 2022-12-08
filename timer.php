<?php

class TIMER
{
  private float $value = 0;

  function __construct($unit = "ms", $precision = 2, $title = "")
  {
    $this->title = $title ?: "timer";
    $this->unit = $unit;
    $this->precision = $precision;
  }

  private function Microtime(): float
  {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }

  function Start(?string $unit = NULL, ?int $precision = NULL): ?float
  {
    if($this->value) $value = $this->Value($unit, $precision);
    else $value = NULL;
    $this->value = $this->Microtime();
    return $value;
  }

  function Value(?string $unit = NULL, ?int $precision = NULL): float
  {
    if($unit == NULL) $unit = $this->unit;
    if($precision == NULL) $precision = $this->precision;

    $x = ["s" => 1, "ms" => 1000, "us" => 1000000];
    if (!isset($x[$unit])) $x[$unit] = 1;
    return round($x[$unit] * ($this->Microtime() - $this->value), $precision);
  }

  function Interval(float $interval, ?string $unit = NULL, ?int $precision = NULL): float
  {
    if($unit == NULL) $unit = $this->unit;
    if($precision == NULL) $precision = $this->precision;

    $x = ["s" => 1000000, "ms" => 1000, "us" => 1];
    if($this->value) {
      $value = $this->Value($unit, $precision);
      $wait = $interval - $value;
      if($wait > 0) {
        usleep($x[$unit] * $wait);
      }
    }
    else $value = 0;
    $this->Start();
    return $value;
  }
}