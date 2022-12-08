<?php

namespace lib\c;

class FIELD
{
  static int $id = 0;
  public int $sizeof;
  public ?bool $sign = NULL;
  public $encodeFnc = NULL;
  public $decodeFnc = NULL;

  function __construct(public string $type = "uint8", public string $name = "")
  {
    $this->sizeof = match ($type) {
      "int8", "uint8" => 1,
      "int16", "uint16" => 2,
      "int32", "uint32", "float" => 4,
      "int64", "uint64", "double" => 8
    };
    $this->sign = match ($type) {
      "uint8", "uint16", "uint32", "uint64" => false,
      "int8", "int16", "int32", "int64" => true,
      default => NULL
    };
    if (!$name) $name = "_field" . FIELD::$id;
    FIELD::$id++;
  }

  function setFunction($encode, $decode)
  {
    $this->encodeFnc = $encode;
    $this->decodeFnc = $decode;
  }
}

class STRFIELD
{
  const SIZEOF_HEAD = 0xFFFF;
  static int $id = 0;
  public string $type = "str";
  public $encodeFnc = NULL;
  public $decodeFnc = NULL;
  // $sizeof: '0' string, 'n' static string/bytes, 'SIZEOF_HEAD', any string/bytes
  function __construct(public string $name = "", public int $sizeof = STRFIELD::SIZEOF_HEAD)
  {
    if (!$name) $name = "_strfield" . FIELD::$id;
    FIELD::$id++;
  }

  function setFunction($encode, $decode)
  {
    $this->encodeFnc = $encode;
    $this->decodeFnc = $decode;
  }
}

class SERIES
{
  public $int16 = [1, 0];
  public $int32 = [3, 2, 1, 0];
  public $int64 = [7, 6, 5, 4, 3, 2, 1, 0];

  function __construct(public array $fields, $endianness = "default")
  {
    $this->Endianness($endianness);
  }

  function Endianness($mode)
  {
    switch (strtolower($mode)) {
      case "revers":
        $this->int16 = [0, 1];
        $this->int32 = [0, 1, 2, 3];
        $this->int64 = [0, 1, 2, 3, 4, 5, 6, 7];
        break;
      case "default":
        $this->int16 = [1, 0];
        $this->int32 = [3, 2, 1, 0];
        $this->int64 = [7, 6, 5, 4, 3, 2, 1, 0];
        break;
    }
  }

  private function EncodeField(FIELD $field, int|float $value, array &$data, int &$j): void
  {
    $str = match ($field->type) {
      "int8" => pack("c", $value),
      "uint8" => pack("C", $value),
      "int16" => pack("s", $value),
      "uint16" => pack("S", $value),
      "int32" => pack("l", $value),
      "uint32" => pack("L", $value),
      "int64" => pack("q", $value),
      "uint64" => pack("Q", $value),
      "float" => pack("f", $value),
      "double" => pack("d", $value),
    };
    $str = str_split($str);
    switch ($field->sizeof) {
      case 1:
        $data[$j] = ord($str[0]) & 0xFF;
        break;
      case 2:
        foreach ($this->int16 as $pos) $data[$j + $pos] = ord($str[$pos]) & 0xFF;
        break;
      case 4:
        foreach ($this->int32 as $pos) $data[$j + $pos] = ord($str[$pos]) & 0xFF;
        break;
      case 8:
        foreach ($this->int64 as $pos) $data[$j + $pos] = ord($str[$pos]) & 0xFF;
        break;
    }
    $j += $field->sizeof;
  }

  private function EncodeStrField(STRFIELD $field, string $value, array &$data, int &$j): void
  {
    if ($field->sizeof == STRFIELD::SIZEOF_HEAD) {
      $size = str_split(pack("L", strlen($value)));
      foreach ($this->int32 as $pos) $data[$j + $pos] = ord($size[$pos]) & 0xFF;
      $j += 4;
    } else if ($field->sizeof) {
      $value = substr($value, 0, $field->sizeof);
      $value = str_pad($value, $field->sizeof, "\0");
    } else {
      if (substr($value, -1) != "\0") $value .= "\0";
    }
    $str = str_split($value);
    foreach ($str as $char) {
      $data[$j++] = ord($char);
    }
  }

  function Encode(array|object $struct, bool $returnString = true): array|string
  {
    $data = [];
    $j = 0;
    foreach ($struct as $obj) {
      foreach ($this->fields as $field) {
        $obj = (array)$obj;
        $value = $obj[$field->name];
        if ($fnc = $field->encodeFnc) $value = $fnc($value);
        if ($field->type == "str") $this->EncodeStrField($field, $value, $data, $j);
        else $this->EncodeField($field, $value, $data, $j);
      }
    }
    ksort($data);
    if ($returnString) return data_to_string($data);
    return $data;
  }

  private function DecodeField(FIELD $field, array &$data, int &$j): int|float
  {
    $str = "";
    switch ($field->sizeof) {
      case 1:
        $str .= chr($data[$j]);
        break;
      case 2:
        foreach (array_reverse($this->int16) as $pos) $str .= chr($data[$j + $pos]);
        break;
      case 4:
        foreach (array_reverse($this->int32) as $pos) $str .= chr($data[$j + $pos]);
        break;
      case 8:
        foreach (array_reverse($this->int64) as $pos) $str .= chr($data[$j + $pos]);
        break;
    }
    $j += $field->sizeof;
    return match ($field->type) {
      "int8" => unpack("c", $str)[1],
      "uint8" => unpack("C", $str)[1],
      "int16" => unpack("s", $str)[1],
      "uint16" => unpack("S", $str)[1],
      "int32" => unpack("l", $str)[1],
      "uint32" => unpack("L", $str)[1],
      "int64" => unpack("q", $str)[1],
      "uint64" => unpack("Q", $str)[1],
      "float" => unpack("f", $str)[1],
      "double" => unpack("d", $str)[1],
    };
  }

  private function DecodeStrField(STRFIELD $field, array &$data, int &$j): string
  {
    $str = "";
    if ($field->sizeof == STRFIELD::SIZEOF_HEAD) {
      $size = "";
      foreach (array_reverse($this->int32) as $pos) $size .= chr($data[$j + $pos]);
      $size = unpack("L", $size)[1];
      $j += 4;
      for ($i = 0; $i < $size; $i++) {
        if (!isset($data[$j + $i])) break;
        $str .= chr($data[$j + $i]);
      }
    } else if ($field->sizeof) {
      for ($i = 0; $i < $field->sizeof; $i++)
        if (!isset($data[$j + $i])) break;
      $str .= chr($data[$j + $i]);
    } else {
      $i = 0;
      while (isset($data[$j + $i])) {
        $i++;
        if ($data[$j + $i - 1] == "\0") break;
        $str .= chr($data[$j + $i - 1]);
      }
    }
    $j += $i;
    return $str;
  }

  function Decode(array|string $data): array
  {
    if (is_string($data)) $data = string_to_data($data);
    $struct = [];
    $j = 0;
    $i = 0;
    while (1) {
      $struct[$i] = [];
      foreach ($this->fields as $field) {
        if ($field->type == "str") $struct[$i][$field->name] = $this->DecodeStrField($field, $data, $j);
        else $struct[$i][$field->name] = $this->DecodeField($field, $data, $j);
        if ($fnc = $field->decodeFnc) $struct[$i][$field->name] = $fnc($struct[$i][$field->name]);
        if (!isset($data[$j])) return $struct;
      }
      $i++;
    }
  }

  function SaveCSV(string $path, array|string $data, string $sep = ",")
  {
    $head = [];
    foreach ($this->fields as $field) array_push($head, $field->name);
    $data = $this->Decode($data);
    array_unshift($data, $head);
    csv_save($path, $data, $sep);
  }
}
