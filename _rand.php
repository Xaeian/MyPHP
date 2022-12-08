<?php

/**
 * Randomizes string of characters of the given `$length` form charset `$chars`
 */
function rand_from(string $chars, int $length): string
{
  $n = strlen($chars);
  $rand = "";
  for ($i = 0; $i < $length; $i++) $rand .= $chars[rand(0, $n - 1)];
  return $rand;
}

/**
 * Randomizes the string of the given `$length`
 */
function rand_string(int $length): string
{
  return rand_from("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", $length);
}

/**
 * Randomizes the password of the given `$length`
 */
function rand_password(int $length): ?string
{
  $lowerLetters = "abcdefghijklmnopqrstuvwxyz";
  $upperLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $numbers = "0123456789";
  $specialChars = "!@#$%^&*?";
  if($length < 8) return "";
  $pass = "";
  $pass .= rand_from($lowerLetters, 1);
  $pass .= rand_from($upperLetters, 1);
  $pass .= rand_from($numbers, 1);
  $pass .= rand_from($specialChars, 1);
  $pass .= rand_from($lowerLetters . $upperLetters . $numbers . $specialChars, $length - 4);
  return str_shuffle($pass);
}