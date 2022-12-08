<?php

function curl_conn(string $url = "localhost",
                   string $method = "GET",
                   null|object|array $object = NULL,
                   int $timeout = 1000): object|string
{
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout);
  if($object) {
    $json = json_encode((object)$object);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: text/plain","Content-Length: " . strlen($json)]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
  }
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($curl);
  curl_close($curl);
  if($object = json_decode($response)) return $object;
  return $response;
}

class CURL
{
  public array $postArray = [];
  public int $fileCount = 0;

  function Reset()
  {
    $this->postArray = [];
    $this->fileCount = 0;
  }

  function __construct(public $url = "localhost", public $timeout = 1000)
  {
  }

  function PushValue($key, $value)
  {
    $this->Post[$key] = $value;
  }

  function PushFile($key, $path)
  {
    if(!file_exists($path)) return;
    switch(pathinfo($path, PATHINFO_EXTENSION)) {
      case "zip": $ext = "application/zip"; break;
      default: $ext = "";
    }
    $this->postArray[$key] = curl_file_create(realpath($path), $ext, basename($path));
    $this->fileCount++;
  }
  
  function Run($reset = true)
  {
    $curl = curl_init($this->url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->timeout);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type" => "multipart/form-data"]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->Post);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    if($reset) $this->Reset(); 
    return $response;
  }
}
