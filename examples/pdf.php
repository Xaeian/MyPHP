<?php

namespace lib;

require_once(ROOT_PATH . "../composer/vendor/tecnickcom/tcpdf/tcpdf.php");
const FONT_PATH_TTF = "D:/Font/ttf/{NAME}/{NAME}-{WIDTH}{MODE}.ttf";
const TEXT_CAGE_DBG = 0;

class PDF extends \TCPDF
{
  public $pageWidth;
  public $pageHeight;

  public function __construct($orientation = "P", $unit = "mm", $format = "A4", public bool $debuge = false)
  {
    switch($format) {
      case "A4":
        $this->pageWidth = 210;
        $this->pageHeight = 297;
        break;
    }
    parent::__construct($orientation, $unit, $format);
    $this->SetDisplayMode("fullwidth", "SinglePage", "UseThumbs");
    $this->SetCreator("TCPDF");
    $this->SetAuthor("Emilian Świtalski");
    // $this->SetTitle("Invoce");
    // $this->SetSubject("Invoce");
    // $this->SetKeywords("Xaeian");
    $this->SetAutoPageBreak(true, 1);
    $this->setFontSubsetting(true);
    $this->SetFooterMargin(6);
    $this->SetMargins(0, 0, 0, true);
    $this->setCellPaddings(0, 0, 0, 0);
    $this->AddPage();
  }

  public function RandomColor($margin = 16)
  {
    $this->SetFillColor(rand($margin, 255 - $margin), rand($margin, 255 - $margin), rand($margin, 255 - $margin));
  }

  //--------------------------------------------------------------------------- Font

  static string $fontPathTff = "D:/Font/ttf/";

  public string $fontStyle = "Barlow";
  public int $fontWeight = 400;
  public float $fontSize = 11;
  public bool $fontItalic = false;
  public string $fontName;
  public string $fontTff;
  public bool $fontChange = false;

  private function FontSet()
  {
    $this->SetFont($this->fontName, '', $this->fontSize);
    $this->fontChange = false;
  }

  public function FontFamily(string $style, string|int $weight, float $size, bool $italic = false)
  {
    if(!is_number($weight)) $weight = preg_replace('/[^A-Za-z0-9]/', "", strtolower($weight));
    $this->fontStyle = preg_replace('/[^A-Za-z0-9]/', "", $style);
    $this->fontSize = $size;
    $this->fontItalic = $italic;
    $this->fontWeight = match($weight) {
      100, "extralight" => 100,
      200, "light" => 200,
      300, "thin" => 300,
      400, "normal", "regular", "" => 400,
      500, "medium" => 500,
      600, "semibold" => 600,
      700, "b", "bold" => 700,
      800, "extrabold" => 800,
      900, "black" => 900,
      default => 400
    };
    $this->fontName = $this->fontStyle . "-" . $this->fontWeight . ($italic ? "i" : "");
    $this->fontTff = $this::$fontPathTff . $this->fontStyle . "/" . $this->fontName . ".ttf";
    $this->fontChange = true;
    return $this;
  }

  public function FontStyle(string $style)
  {
    return $this->FontFamily($style, $this->fontWeight, $this->fontSize, $this->fontItalic);
  }

  public function FontWeight(string|int $weight)
  {
    return $this->FontFamily($this->fontStyle, $weight, $this->fontSize, $this->fontItalic);
  }

  public function FontSize(float $size)
  {
    return $this->FontFamily($this->fontStyle, $this->fontWeight, $size, $this->fontItalic);
  }

  public function FontItalic()
  {
    return $this->FontFamily($this->fontStyle, $this->fontWeight, $this->fontSize, true);
  }

  public function FontNotItalic()
  {
    return $this->FontFamily($this->fontStyle, $this->fontWeight, $this->fontSize, false);
  }

  // $name = preg_replace('/[^a-z0-9]/', "", strtolower($name));
  // $path = FONT_PATH_TTF;
  // $path = str_replace("{NAME}", $name, $path);
  // $path = str_replace("{WIDTH}", $width, $path);
  // if($italic) $path = str_replace("{MODE}", "i", $path);
  // else $path = str_replace("{MODE}", "", $path);
  // return $path;

  //--------------------------------------------------------------------------- Cursor

  public float $X = 0;
  public float $baseX = 0;
  public float $Y = 0;
  public string $cursorAlign = "L";
  public float $marginLR = 15;
  public float $marginTop = 15;
  public float $marginBot = 15;
  private float $lastHeight = 0;

  public function Cursor(?float $x = NULL, ?float $y = NULL, ?string $align = NULL)
  {
    $this->X = $x;
    $this->baseX = $x;
    $this->Y = $y;
    $this->cursorAlign = $align ?: $this->cursorAlign;
    return $this;
  }

  public function Enter()
  {
    $this->Y += $this->lastHeight;
    $this->X = $this->baseX;
    return $this;
  }

  private function CursorGetXY(float $width)
  {
    $x = $this->X;
    $y = $this->Y + $this->marginTop;
    switch($this->cursorAlign) {
      case "L":
        if($x < 0) $x += $this->pageWidth;
        $x += $this->marginLR;
        break;
      case "C":
        if($x < 0) $x += $this->pageWidth;
        break;
      case "R":
        if($x <= 0) $x += $this->pageWidth;
        $x -= $width;
        $x -= $this->marginLR;
        break;
    }
    return [$x, $y];
  }

  public function Color(string|array $color)
  {
    if(is_string($color)) $color = hex2rgb($color);
    $this->SetTextColor($color[0], $color[1], $color[2]);
    return $this;
  }

  public function TextBox(string $text, float $width, float $height, string $align = "L")
  {
    list($x, $y) = $this->CursorGetXY($width);
    $fill = false;
    if($this->debuge) {
      $this->RandomColor();
      $fill = true;
    }
    if($this->fontChange) $this->FontSet();
    $this->MultiCell($width, $height, $text, 0, $align, $fill, 1, $x, $y, true, 0, false, true, $height, "M");
    $this->lastHeight = $height;
    $this->X = match($this->cursorAlign) {
      "L" => $this->X + $width,
      "R" => $this->X - $width
    };
    return $this;
  }

  // public function Image($x, $y, $url, $widnt, $height)
  // {
  //   //

  //   //$pdf->ImageSVG($file='SVG/mail.svg', $x=15, $y=26, $w=10, $h=10);
  // }

  


  //--------------------------------------------------------------------------- Box

  static public $boxLink = "·";
  static public $boxNewLineInput = PHP_EOL;
  static public $boxNewLineOutput = PHP_EOL;
  private float $boxWidth, $boxHeight, $boxFactor;

  private function SetBoxSize(float $width, float $height, string $unit = "mm")
  {
    switch ($unit) {
      case "cm": $this->boxFactor = 355; break;
      case "mm": $this->boxFactor = 3.55; break;
      default: $this->boxFactor = 1; break; // $unit = "px"
    }
    $this->boxWidth = $width * $this->boxFactor;
    $this->boxHeight = $height * $this->boxFactor;
  }

  function TextWidth(string $text, ?float $fontSize = NULL, bool $print = false)
  {
    $fontSize = isset($fontSize) ? $fontSize : $this->fontSize;
    if($print) var_dump($fontSize);
    $space = imagettfbbox($fontSize, 0, $this->fontTff, $text);
    return abs($space[4] - $space[0]);
  }

  function TextHeight(string $text, ?float $fontSize = NULL)
  {
    $fontSize = isset($fontSize) ? $fontSize : $this->fontSize;
    $type_space = imagettfbbox($fontSize, 0, $this->fontTff, $text);
    return abs($type_space[5] - $type_space[1]);
  }

  /**
   * @param float $width: textbox width
   * @param float $unit: unit for size conversion
   * @param ?float $fontScale: automatic font-size reduction when the text does not fit
   * @param float $height: textbox height -          
   */
  public function BoxFitting(string $text, float $width, string $unit = "mm", ?float $fontScale = null, float $height = 0, ?float $fontSize = null): ?object
  {
    $fontSize = $fontSize ?: $this->fontSize;
    $this->SetBoxSize($width, $height, $unit);
    $input = explode($this::$boxNewLineInput, $text);
    $lines = 0;
    $spaceWidth = $this->TextWidth(" ");
    foreach($input as $phrase) {
      $phrase = trim(str_replace($this::$boxLink, "¶", $phrase));
      $phraseWidth = $this->TextWidth($phrase, $fontSize);
      if($phraseWidth > $this->boxWidth) {
        $words = explode(" ", $phrase);
        $wordWidths = [];
        foreach($words as $i => $word) {
          $wordWidths[$i] = $this->TextWidth($word, $fontSize);
          if($wordWidths[$i] > $this->boxWidth) {
            if($fontScale) return $this->BoxFitting($text, $width, $unit, true, $height, $fontSize - $fontScale);
            else return NULL; // Error
          }
        }
        $sumWidth = 0;
        $output = [""];
        foreach($words as $i => $word) {
          $sumWidth += $wordWidths[$i] + $spaceWidth;
          $output[$lines] = $output[$lines] . $word . " ";
          if(($i < (count($words) - 1)) && ($sumWidth + $wordWidths[$i + 1] > $this->boxWidth)) {
            $sumWidth = 0;
            $output[$lines] = trim($output[$lines]);
            $lines++;
            $output[$lines] = '';
          }
        }
      }
      else $output[$lines] = $phrase;
      $lines++;
      $output[$lines] = '';
    }
    $text = "";
    for($j = 0; $j < ($lines - 1); $j++) {
      $output[$j] = trim($output[$j]);
      if($output[$j] || ($output[$j] === 0) || ($output[$j] === "0")) $text = $text . $output[$j] . PHP_EOL;
    }
    if($output[$j] || ($output[$j] === 0) || ($output[$j] === "0")) $text = $text . $output[$j];
    $text = trim(str_replace('¶', ' ', $text));
    $outHeight = $this->TextHeight($text);
    if($fontScale) {
      if($outHeight > $this->boxHeight)
        return $this->BoxFitting($text, $width, $unit, true, $height, $fontSize - $fontScale);
    }
    $text = str_replace(PHP_EOL, $this::$boxNewLineOutput, $text);
    return (object) [
      "text" => $text,
      "fontSize" => $fontSize,
      "height" => $outHeight / $this->boxFactor,
      "lines" => $lines,
    ];
  }

  function BoxFittingArray(array $textArray, array|float $columnWidth = [], string $unit = "mm", ?float $fontScale = null, array|float $rowHeight = [], ?float $fontSize = null)
  {
    to_array2d($textArray);
    to_vector($columnWidth, count($textArray[0]));
    to_vector($rowHeight, count($textArray));
    $return = [];
    foreach($textArray as $i => $row) {
      $return[$i] = [];
      foreach($row as $j => $text) {
        if(!isset($rowHeight[$i])) $rowHeight[$i] = 0;
        $return[$i][$j] = $this->BoxFitting($text, $columnWidth[$j], $unit, $fontScale, $rowHeight[$i], $fontSize);
      }
    }
    return $return;
  }

  private function ArrayCrossLine($startX, $startY, $y)
  {
    $this->SetLineStyle($this->arrayLineWidth);
    $size_sum = 0;
    foreach($this->_columnSize as $size) {
      $this->Line($startX + $size_sum, $startY, $startX + $size_sum, $y - $this->arrayLineWidth);
      $size_sum += $size;
    }
    $this->Line($startX + $size_sum, $startY, $startX + $size_sum, $y - $this->arrayLineWidth);
  }

  private function ArrayPredictionHeight(int $i, int $j, int $count)
  {
    $height = 0;
    while($i < $this->_bodyRowCount && $count) {
      if($j < $this->_headRowCount) {
        $line = $this->_headLines[$j];
        $j++;
      }
      else {
        $line = $this->_bodyLines[$i];
        $count--;
        $i++;
      }
      $height += (($line - 1) * $this->_sapceing_mm) + ($this->_fontsize_mm * $line) + $this->arrayPadding;
    }
    return $height;
  }

  private function ArrayRow(array $texts, float $height, $color, $y, $x)
  {
    if($this->arrayFillEnable) {
      $this->SetAlpha(0.5);
      $this->SetFillColor($color);
      $this->Rect($x, $y - ($this->arrayLineWidth / 2), $this->_width, $height + ($this->arrayLineWidth / 2), "F");
      $this->SetAlpha(1);
    }
    foreach($texts as $i => $text) {
      if($this->fontChange) $this->FontSet();
      $this->MultiCell($this->_columnSize[$i] - (2 * $this->_sep), $height, $text, 0, $this->_columnAlign[$i], false, 0, $x + $this->_sep, $y - ($this->_sep / 4), true, 0, false, true, $height, 'M');
      $x += $this->_columnSize[$i];
    }
  }

  public $arrayRowMinPack = 2;
  public $arrayFillEnable = true;
  public $arrayCrosslineEnable = true;
  public $arrayPadding = 2;
  public $arraySapceing = 1;
  public $arrayLineWidth = 0.2;
  public $arrayLineWidthSeparator = 0.3;
  public $arrayColorBody = [248, 224];
  public $arrayColorHead = [160, 192];
  public $arrayWeightBody = [400, 400];
  public $arrayWeightHead = 700;

/**
 * @param array $body array structures - row-by-row in array
 * @param array $head 
 * @param array $columnSize
 * @param array $columnAlign
 * @param float|null $width
 * @param [handler] $headerFunction funkcja wywoływan
 * @return void
 */
  public function Array(array $body, array $head, array $columnSize, array $columnAlign, ?float $width = NULL, $headerFunction = NULL)
  {
    $this->_width = isset($width) ? $width : $this->pageWidth - (2 * $this->marginLR);
    list($x, $y) = $this->CursorGetXY($this->_width);
    to_array2d($body);
    to_array2d($head, true);
    $columnSize = vector_div($columnSize, vector_sum($columnSize) / $this->_width);
    $body = $this->BoxFittingArray($body, $columnSize);
    $bodyText = deep_property($body, "text");
    $this->_bodyLines = array_call(deep_property($body, "lines"), "max");
    $this->_bodyRowCount = count($this->_bodyLines);
    $head = $this->BoxFittingArray($head, $columnSize);
    $headText = deep_property($head, "text");
    $this->_headLines = array_call(deep_property($head, "lines"), "max");
    $this->_headRowCount = count($this->_headLines);
    $this->_columnSize = $columnSize;
    $this->_columnAlign = $columnAlign;
    $this->_fontsize_mm = $this->fontSize * 0.365;
    $this->_sapceing_mm = $this->arraySapceing * 0.365;
    $this->_sep = ($this->_fontsize_mm / 5) + ($this->arrayPadding / 5);
    if($y === NULL) $y = $this->marginTop;
    if($x === NULL) $x = $this->marginLR;
    $startX = $x;
    $startY = $y;
    $i = 0;
    pdf_array_start:
    $j = 0;
    $first = true;
    $this->Line($x, $y, $x + $this->_width, $y, ["width" => $this->arrayLineWidth]);
    while($i < $this->_bodyRowCount) {
      if($y + $this->ArrayPredictionHeight($i, $j, $this->arrayRowMinPack) > $this->pageHeight - $this->marginBot) {
        if($this->arrayCrosslineEnable) $this->ArrayCrossLine($startX, $startY, $y);
        $this->lastPage();
        if($this->headerFunction) call_user_func($this->headerFunction);
        $this->AddPage();
        $y = $this->marginTop;
        $startY = $y;
        goto pdf_array_start;
      }
      if($j < $this->_headRowCount) {
        $this->FontWeight($this->arrayWeightHead);
        $rowHeight = (($this->_headLines[$j] - 1) * $this->_sapceing_mm) + ($this->_fontsize_mm * $this->_headLines[$j]) + $this->arrayPadding;
        $this->ArrayRow($headText[$j], $rowHeight, $this->arrayColorHead[$j % 2], $y, $x);
        $j++;
        if($j >= $this->_headRowCount) $arrayLineWidth = $this->arrayLineWidthSeparator;
        else $arrayLineWidth = $this->arrayLineWidth;
      }
      else {
        $this->FontWeight($this->arrayWeightBody[$i % 2]);
        $rowHeight = (($this->_bodyLines[$i] - 1) * $this->_sapceing_mm) + ($this->_fontsize_mm * $this->_bodyLines[$i]) + $this->arrayPadding;
        $this->ArrayRow($bodyText[$i], $rowHeight, $this->arrayColorBody[$i % 2], $y, $x);
        $i++;
        $arrayLineWidth = $this->arrayLineWidth;
      }
      if($first) {
        $this->Line($x, $y, $x + $this->_width, $y, ["width" => $this->arrayLineWidth]);
        $first = false;
      }

      $x = $startX;
      $y += $rowHeight;
      $this->Line($x, $y, $x + $this->_width, $y, ["width" => $arrayLineWidth]);
      $y += $arrayLineWidth;
    }
    if($this->arrayCrosslineEnable) $this->ArrayCrossLine($startX, $startY, $y);
    $this->Y = $y - $this->marginTop;
    return $this;
  }
}
