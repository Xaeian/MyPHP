<?php

class SERIE
{
  public array $vect = [];

  function __construct(array $array = [])
  {
    foreach($array as $value) {
      array_push($this->vect, floatval($value));  
    }
  }

  static function Ones(int $count): SERIE
  {
    $array = [];
    for($i = 0; $i < $count; $i++) {
      array_push($array, 1);
    }
    return new SERIE($array);
  }

  static function Zeros(int $count): SERIE
  {
    $array = [];
    for($i = 0; $i < $count; $i++) {
      array_push($array, 0);
    }
    return new SERIE($array);
  }

  static function Values(int $count, float $value): SERIE
  {
    $array = [];
    for($i = 0; $i < $count; $i++) {
      array_push($array, $value);
    }
    return new SERIE($array);
  }

  static function Range(float ...$arg): SERIE
  {
    $array = [];
    $start = 0;
    $step = 1;
    switch(count($arg)) {
      case 1: $stop = $arg[0]; break;
      case 2: $start = $arg[0]; $stop = $arg[1]; break;
      case 3: $start = $arg[0]; $stop = $arg[1]; $step = $arg[2]; break;
      default: return new SERIE();
    }
    $step = abs($step);

    if($start < $stop) {
      while($start < $stop) {
        array_push($array, $start);
        $start += $step;
      }
    }
    else {
      while($stop < $start) {
        array_push($array, $start);
        $start -= $step;
      }
    }
    return new SERIE($array);
  }

  function Count(): int
  {
    return count($this->vect);
  }

  function CountMin(SERIE $serie): int
  {
    return min([$this->Count(), $serie->Count()]);
  }

  function Index(float $value, int $skip = 0): ?int
  {
    $count = $this->Count();
    for($i = 0; $i < $count; $i++) {
      if($this->vect[$i] == $value) {
        if($skip--) continue;
        return $i;
      }
    }
    return NULL;
  }

  function VaulePoint(float $value, int $skip = 0): ?int
  {
    if(!$count = $this->Count()) return NULL;
    if($this->vect[0] == $value) {
      if($skip) $skip--;
      else return 0;
    }
    for($i = 1; $i < $count; $i++) {
      $a = $this->vect[$i - 1] - $value;
      $b = $this->vect[$i] - $value;
      if($b == 0) {
        if($skip) { $skip--; continue; }
        return $i;
      }
      else if($a * $b < 0) {
        if($skip) { $skip--; continue; }
        if(abs($a) < abs($b)) return $i - 1;
        else return $i;
      }
    }
    return NULL;
  }

  function Value(int $index): ?float
  {
    if($index > $this->Count()) return NULL;
    return $this->vect[$index];
  }

  private function Begin(SERIE|float $serie)
  {
    if(is_float($serie)) {
      $count = $this->Count();
      $serie = SERIE::Values($count, $serie);
    } else $count = $this->CountMin($serie);
    return [$serie, $count, []];
  }

  function add(SERIE|float $serie): SERIE
  {
    [$serie, $count, $array] = $this->Begin($serie);
    for($i = 0; $i < $count; $i++) {
      array_push($array, $this->vect[$i] + $serie->vect[$i]);
    }
    return new SERIE($array);
  }

  function sub(SERIE|float $serie): SERIE
  {
    [$serie, $count, $array] = $this->Begin($serie);
    for($i = 0; $i < $count; $i++) {
      array_push($array, $this->vect[$i] - $serie->vect[$i]);
    }
    return new SERIE($array);
  }

  function x(SERIE|float $serie): SERIE
  {
    [$serie, $count, $array] = $this->Begin($serie);
    for($i = 0; $i < $count; $i++) {
      array_push($array, $this->vect[$i] * $serie->vect[$i]);
    }
    return new SERIE($array);
  }

  function div(SERIE|float $serie): SERIE
  {
    [$serie, $count, $array] = $this->Begin($serie);
    for($i = 0; $i < $count; $i++) {
      array_push($array, $this->vect[$i] / $serie->vect[$i]);
    }
    return new SERIE($array);
  }

  function NormMinMax(): SERIE
  {
    $max = max($this->vect);
    $min = min($this->vect);
    return $this->add($min)->div($max - $min);
  }

  static function DistGaussFnc(float $x, float $sigma = 1, float $mi = 0): float
  {
    return (1 / ($sigma * sqrt(2 * pi()))) * exp((-pow($x - $mi, 2))/(2 * pow($sigma, 2)));
  }

  function DistGauss(float $sigma, float $mi): SERIE
  {
    $array = [];
    $count = $this->Count();
    for($i = 0; $i < $count; $i++) {
      array_push($array, SERIE::DistGaussFnc($this->vect[$i], $sigma, $mi));
    }
    return new SERIE($array);
  }
}
