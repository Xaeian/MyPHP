<?php

class CRC
{
  public array $array = [];
  private int $topbit;
  const CRC_MASK = [8=>0xFF, 16=>0xFFFF, 32=>0xFFFFFFFF];

  public function IntToHex(int $int, int $width): string
  {
    $int &= self::CRC_MASK[$width];
    $str = strtoupper(dechex($int));
    switch($width) {
      case 8: switch(strlen($str)) {
        case 1: $str = "0x0".$str; break;
        case 2: $str = "0x".$str; break; 
      } break;
      case 16: switch(strlen($str)) {
        case 1: $str = "0x000".$str; break;
        case 2: $str = "0x00".$str; break; 
        case 3: $str = "0x0".$str; break;
        case 4: $str = "0x".$str; break; 
      } break;
      case 32: switch(strlen($str))
      {
        case 1: $str = "0x0000000".$str; break;
        case 2: $str = "0x000000".$str; break; 
        case 3: $str = "0x00000".$str; break;
        case 4: $str = "0x0000".$str; break;
        case 5: $str = "0x000".$str; break;
        case 6: $str = "0x00".$str; break; 
        case 7: $str = "0x0".$str; break;
        case 8: $str = "0x".$str; break;
      } break;
    }
    return $str;
  }
  
  function ReflectBit(int $data, int $width): int
  {
    $reflection = 0;
    for($bit = 0; $bit < $width; ++$bit) {
      if($data & 0x01) $reflection |= (1 << (($width - 1) - $bit));
      $data = ($data >> 1);
    }
    return $reflection;
  }

  function __construct(
    public int $width = 8,
    public int $polynomial = 0x31,
    public int $initial = 0xFF,
    public bool $reflectIn = false, # reflect_data_in
    public bool $reflectOut = false, # reflect_data_out
    public int $xor = 0x00,
    public bool $invertOut = false
    )
  {
    $this->topbit = (1 << ($width - 1));
    $this->Init();
  }

  function Init()
  {
    for($i = 0; $i < 256; ++$i) {
      $remainder = $i << ($this->width - 8);
      for($bit = 8; $bit > 0; --$bit) {
        if($remainder & $this->topbit) $remainder = ($remainder << 1) ^ $this->polynomial;
        else $remainder = ($remainder << 1);
      }
      $remainder &= self::CRC_MASK[$this->width];
      $this->array[$i] = $remainder;
    }
  }

  function CLangArray($enter = "\r\n") : string
  {
    $str = "const uint" . $this->width . "_t CRC_ARRAY = {";
    foreach($this->array as $i => $cell) {
      switch($this->width) {
        case 8: if(!($i % 16)) $str .= $enter; break;
        case 16: if(!($i % 12)) $str .= $enter; break;
        case 32: if(!($i % 8)) $str .= $enter; break;
      }
      $str .= $this->IntToHex($cell, $this->width) . ", ";
    }
    $str = preg_replace('/\.\ $/', " ", $str) . $enter . "};";
    return $str;
  }

  function Run(array $msg): int
  {
    $n = count($msg);
    $remainder = $this->initial;
    for($byte = 0; $byte < $n; ++$byte) {
      if($this->reflectIn) $msg[$byte] = $this->ReflectBit($msg[$byte], 8);
      $data = $msg[$byte] ^ ($remainder >> ($this->width - 8));
      $tmp = $data & self::CRC_MASK[8];
      $remainder = $this->array[$tmp] ^ ($remainder << 8);
    }
    $remainder &= self::CRC_MASK[$this->width];
    if($this->reflectOut) $remainder = $this->ReflectBit($remainder, $this->width);
    $remainder = $remainder ^ $this->xor;
    if($this->invertOut) {
      $this->toInt(array_reverse($this->toArray($remainder)));
    }
    return $remainder;
  }

  function toArray(int $crc): array
  {
    $array = [];
    switch($this->width) {
      case 32:
        array_push($array, ($crc >> 24) & 0xFF);
        array_push($array, ($crc >> 16) & 0xFF);
      case 16:
        array_push($array, ($crc >> 8) & 0xFF);
      case 8:
        array_push($array, $crc & 0xFF);
    }
    return $array;
  }

  function toInt(array $crc): int
  {
    return match($this->width) {
      32 => ($crc[0] << 24) + ($crc[1] << 16) + ($crc[2] << 8) + $crc[3],
      16 => ($crc[0] << 8) + $crc[1],
      8  => ($crc[0])
    };
  }

  function Decode(string|array $frame): string|array|NULL
  {
    if(is_string($frame)) {
      $frame = string_to_data($frame);
      $toString = true;
    } else $toString = false;

    $msg = array_slice($frame, 0, -$this->width / 8);
    $crc = array_slice($frame, -$this->width / 8);
    if($this->toInt($crc) == $this->Run($msg)) return $toString ? data_to_string($msg) : $msg;
    return NULL;
  }

  function Encode(string|array $msg)
  {
    if(is_string($msg)) {
      $msg = string_to_data($msg);
      $toString = true;
    } else $toString = false;
    $crc = $this->Run($msg);
    $frame = array_merge($msg, $this->toArray($crc));
    return $toString ? data_to_string($frame) : $frame;
  }
}