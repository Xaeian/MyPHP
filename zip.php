<?php

class ZIP
{
  static public function Extract(string $zip_location = "./archive.zip", string $extract_location = "./")
  {
    $zip = new \ZipArchive;
    if ($zip->open($zip_location)) {
      if (file_exists($extract_location)) folder_delete($extract_location);
      mkdir($extract_location, 0777, true);
      $zip->extractTo($extract_location);
    }
    $zip->close();
  }

  static public function Archive(string $zip_location = "./archive.zip", string $dir_location = "./")
  {
    $dir_location = realpath($dir_location);
    $zip_location = str_replace_right(".zip", "", $zip_location) . ".zip";
    $zip = new \ZipArchive();
    $zip->open($zip_location, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir_location), \RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
      if (!$file->isDir()) {
        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($dir_location) + 1);
        $zip->addFile($file_path, $relative_path);
      }
    }
    $zip->close();
  }
}
