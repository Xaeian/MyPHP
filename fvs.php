<?php

class FVS
{
  function __construct(
    private string $path = ROOT_PATH . "/var/"
  ) {
    $this->path = path_pretty($path, false);
  }

  function Load(string $var): mixed
  {
    $var = $this->path . $var;
    return json_load($var);
  }

  function All(): array
  {
    $all = [];
    foreach(file_loader($this->path, "json", "json") as $var => $value) {
      $all[remove_suffix($var, ".json")] = $value;
    }
    return $all;
  }

  function Save(string $var, mixed $value)
  {
    $var = $this->path . $var;
    json_save($var, $value);
  }

  function Drop(string $var)
  {
    $this->Save($var, NULL);
  }

  function Clear(): void
  {
    file_remover($this->path, "json");
  }

  function Disp(): string
  {
    return "FVS " . $this->path;
  }
}
