<?php

const ROOT_PATH = __DIR__."/../../";

require_once(ROOT_PATH."lib/stdlib.php");
include_library("crc");

//------------------------------------------------------------------------------------------------

$crc = new CRC(8, 0x31, 0xFF, false, false, 0x00); // SHTC3
$crc = new CRC(8, 0x31, 0x00, false, false, 0x00); // SFM4100

$test = [0x00, 0x13];

// echo $crc->ArrayLangC();
// echo PHP_EOL;
// echo "CRC output: ".int_to_hex($crc->Run($test), 8);

// for ($i = 0; $i < 256; $i++) {
//   echo $i." ";
// }

for ($bit = 8; $bit > 0; --$bit) {
  echo $bit." ";
}

//------------------------------------------------------------------------------------------------

?>