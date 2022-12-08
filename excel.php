<?php

namespace excel;

require_once(ROOT_PATH . "../composer/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use SplFileInfo;




//------------------------------------------------------------------------------------------------

function SheetToArray($location, $format = "extension")
{
  $format = match (strtolower($format)) {
    "application/vnd.ms-excel", "xls" => "xls",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "xlsx" => "xlsx",
    "application/vnd.oasis.opendocument.spreadsheet", "ods" => "ods",
    "extension", "ext" => (new SplFileInfo($location))->getExtension(),
    default => null,
  };

  if(!$format) return null;
  else $format = like_names($format);

  $reader = IOFactory::createReader($format);
  $reader->setReadDataOnly(true);
  $reader->setLoadAllSheets();
  $sheets = $reader->load($location);

  $sheetNames = $sheets->getSheetNames($sheets);
  $output = [];
  $flagFirst = true;

  foreach($sheetNames as $name) {
    if($flagFirst) {
      $output[$name] = $sheets->getActiveSheet()->toArray();
      $flagFirst = false;
    } else $output[$name] = $sheets->getSheetByName($name)->toArray();
  }
  return $output;
}

class EXCEL
{
  public $creator = "PHP Xaeian Lib";
  public bool $save = true;
  public bool $download = false;

  function __construct(
    public array $body = [],
    public array $sheetNames = [],
    public string $fileName = "sheet",
    public string $location = "./",
    public string $format = "xlsx"
    )
  {
    foreach($this->sheetNames as $i => $name) $this->sheetNames[$i] = $this->SheetName($name);
    $this->fileName = $this->FileName($this->fileName);
    $this->format = strtolower($this->format);
  }

  public static function ColumnToX(string $column, int $startIndex = 0)
  {
    if($startIndex && (!$column)) return "";
    if(!preg_match("/[A-Za-z]/", $column)) {
      if($column === "") return "";
      else return intval($column);
    }
    $az_int = ["A"=>0, "B"=>1, "C"=>2, "D"=>3, "E"=>4, "F"=>5, "G"=>6, "H"=>7, "I"=>8, "J"=>9, "K"=>10, "L"=>11, "M"=>12, "N"=>13, "O"=>14, "P"=>15, "Q"=>16, "R"=>17, "S"=>18, "T"=>19, "U"=>20, "V"=>21, "W"=>22, "X"=>23, "Y"=>24, "Z"=>25];
    $column = strtoupper($column);
    $column = preg_replace("/[^A-Z]/", "", $column);
    $sum = 0;
    $j = 1;
    for($i = strlen($column) - 1; $i >= 0; $i--) {
      $x = $az_int[substr($column, $i, 1)];
      $sum += $j * ($x + 1);
      $j *= 26;
    }
    $sum--;
    return $sum + $startIndex;
  }

  static public function XtoColumn($x)
  {
    $column = "";
    $int_az = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
    do {
      $r = $x % 26;
      $x = floor($x / 26) - 1;
      $column = $int_az[$r] . $column;
    } while ($x > -1);
    return $column;
  }

  static public function XYtoCell($x, $y)
  {
    return EXCEL::XtoColumn($x) . ($y + 1);
  }

  function ExcelToDate($date, $format = "-")
  {
    if(!$date) return $date;
    if($format = ".") return date("d.m.Y", 3600 * 24 * ($date - 25569));
    if($format = "-") return date("Y-m-d", 3600 * 24 * ($date - 25569));
    return date("d/m/Y", 3600 * 24 * ($date - 25569));
  }

  static function DateToExcel($date)
  {
    $date = str_replace(".", "-", $date);
    $temp = explode("-", $date, 3);
    if(strlen($temp[0]) == 2) {
      $d = $temp[0];
      $y = $temp[2];
    }
    else {
      $d = $temp[2];
      $y = $temp[0];
    }
    $m = $temp[1];
    return (mktime(0, 0, 0, $m, $d, $y) / 3600 / 24) + 25569 + (2 / 24);
  }

  function SheetName($name)
  {
    $name = preg_replace("/\[+/", "(", $name);
    $name = preg_replace("/\]+/", ")", $name);
    $name = preg_replace("/\*+/", "×", $name);
    $name = preg_replace("/(\:|\?|\\|\/)+/", "-", $name);
    if(strlen($name) > 31) $name = substr($name, 0, 31);
    return $name;
  }

  function FileName($name)
  {
    $name = preg_replace("/\<+/", "(", $name);
    $name = preg_replace("/\>+/", ")", $name);
    $name = preg_replace("/\*+/", "×", $name);
    $name = preg_replace("/(\||\"|\:|\?|\\|\/)+/", "-", $name);
    if(strlen($name) > 256) $name = substr($name, 0, 256);
    return $name;
  }

  function Run(Spreadsheet $sheet = NULL)
  {
    if(!$sheet) $sheet = new Spreadsheet();
    $sheet->getProperties()->setCreator($this->creator)->setTitle($this->fileName);
    $n = 0;
    foreach($this->body as $tab) {
      if($n) $sheet->createSheet();
      $sheet->setActiveSheetIndex($n);
      $sheet->getActiveSheet()->setTitle($this->sheetNames[$n]);
      $countRow = count($tab);
      $countColumn = count($tab[0]);
      for ($j = 0; $j < $countRow; $j++) {
        for ($i = 0; $i < $countColumn; $i++) {
          if(isset($tab[$j][$i])) {
            $addr = $this->XtoColumn($i) . ($j + 1);
            $cell = $tab[$j][$i];
            $sheet->getActiveSheet()->setCellValueExplicit($addr, $cell->value, $cell->type);
            if($cell->link) $sheet->getActiveSheet()->getCell($addr)->getHyperlink()->setUrl($cell->link);
            if($cell->format) $sheet->getActiveSheet()->getStyle($addr)->getNumberFormat()->setFormatCode($cell->format);
            if($cell->style) $sheet->getActiveSheet()->getStyle($addr)->applyFromArray($cell->style);
            $sheet->getActiveSheet()->getStyle($addr)->getAlignment()->setHorizontal($cell->align);
          }
        }
      }
      foreach(range("A", $this->XtoColumn($countColumn - 1)) as $col)
        $sheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
      $n++;
    }
    $sheet->setActiveSheetIndex(0);
    $name = $this->fileName . "." . $this->format;
    if($this->download) {
      switch ($this->format) {
        case "xls":
          header("Content-Type: application/vnd.ms-excel");
          break;
        case "xlsx":
          header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
          break;
        case "ods":
          header("Content-Type: application/vnd.oasis.opendocument.spreadsheet");
          break;
      }
      header("Content-Disposition: attachment;filename=\"" . $name . "\"");
      header("Cache-Control: max-age=0");
      header("Cache-Control: max-age=1");
      header("Expires: Mon, 14 Jun 1993 06:00:00 GMT");
      header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
      header("Cache-Control: cache, must-revalidate");
      header("Pragma: public");
    }
    $writer = IOFactory::createWriter($sheet, like_names($this->format));
    $writer->setIncludeCharts(true);
    if($this->save) $writer->save($this->location . "/" . $name);
    if($this->download) $writer->save("php://output");
  }
}

class CELL
{
  public ?string $link = NULL;
  function __construct(
    public mixed $value = "",
    public string $type = "s",
    public string $align = "left",
    public ?array &$style = NULL,
    public string $format = ""
  ) {}

  public $styleHead = [
    "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
    "borders" => ["bottom" => ["borderStyle" => "thin"]],
    "fill" => ["fillType" => "solid", "color" => ["rgb" => '101010']],
  ];
  public $styleFalse = ["font" => ["color" => ["rgb" => "AAAAAA"]]];
  public $styleLink = ["font" => ["underline" => true, "color" => ["rgb" => "4080E0"]]];
  public $styleBold = ["font" => ["bold" => true]];

  function Mode(string $mode, bool $formule = false)
  {
    switch($mode) {
      case "str": $this->type = "s"; $this->format = ""; $this->align = "left"; break;
      case "int": $this->type = "n"; $this->format = "0"; $this->align = "right"; break;
      case "nbr": $this->type = "n"; $this->format = "0.00"; $this->align = "right"; break;
      case "bool":
        $this->value = $this->value ? True : False; $this->type = "b"; $this->format = ""; $this->align = "center";
        $this->style = $this->value ? NULL : $this->styleFalse;
        break;
      case "null": $this->value = NULL; $this->type = "null"; $this->format = ""; $this->align = "center"; break;
      case "%": $this->type = "n"; $this->format = "0.00%"; $this->align = "right"; break;
      case "zł": $this->type = "n"; $this->format = "# ##0.00\"zł\""; $this->align = "right"; break;
      case "date": $this->value = EXCEL::DateToExcel($this->value); $this->type = "n"; $this->format = "yyyy-mm-dd"; $this->align = "right"; break;
      case "date-pl": $this->value = EXCEL::DateToExcel($this->value); $this->type = "n"; $this->format = "dd.mm.yyyy"; $this->align = "right"; break;
      default: $this->type = "s"; $this->format = ""; $this->align = "right"; break;
    }
    if($formule) $this->type = "f";
    return $this;
  }

  function Head(string $mode)
  {
    $this->type = "s";
    $this->format = "";
    $this->style = $this->styleHead;
    switch($mode) {
      case "str": $this->align = "left"; break;
      case "bool": $this->align = "center"; break;
      default: $this->align = "right"; break;
    }
    return $this;
  }

  function Link(string $link)
  {
    $this->link = $link;
    $this->style = $this->styleLink;
  }
}