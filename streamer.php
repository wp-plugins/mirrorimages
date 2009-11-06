<?php

@include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR. "wp-config.php");
@include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR."wp-includes/wp-db.php");

$mirrim_options = get_option( "mirrim_options" );

$img = trim($_GET['img']);

$background = get_rgb($mirrim_options["mi_background"]);
$gradient   = $mirrim_options["mi_gradient"];
$shadow     = $mirrim_options["mi_shadow"];
$distance   = $mirrim_options["mi_distance"];

function get_rgb($hex) {
    $hex_array = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
        'F' => 15);
    $hex = str_replace('#', '', strtoupper($hex));
    if (($length = strlen($hex)) == 3) {
        $hex = $hex{0}.$hex{0}.$hex{1}.$hex{1}.$hex{2}.$hex{2};
        $length = 6;
    }
    if ($length != 6 or strlen(str_replace(array_keys($hex_array), '', $hex)))
        return NULL;
    $rgb[0] = $hex_array[$hex{0}] * 16 + $hex_array[$hex{1}];
    $rgb[1] = $hex_array[$hex{2}] * 16 + $hex_array[$hex{3}];
    $rgb[2] = $hex_array[$hex{4}] * 16 + $hex_array[$hex{5}];
    return $rgb;
}




/**
* imagereflection
*
* Versieht ein Bild mit einem Spiegeleffekt.
*
* @author  Jan Papenbrock, www.solvium.de
* @version 0.1 (Apr-13 2007)
* @url http://www.solvium.de/programmierung/php/imagereflection-bild-spiegeleffekt/
*
* @param   $source      Quellbild
* @param   $background  Array, das die RGB-Werte der Hintergrundfarbe enthält,
*                       wobei R = [0], G = [1], B = [2]
* @param   $gradient    Enthält die Größe des Verlaufs, anteilig zur Bildgröße
* @param   $shadow      Enthält die Größe des Schattens, anteilig zur Bildgröße
* @param    $distance   Abstand des Bildes zum Spiegelbild in Pixel
*
* @return das fertige Bild
*/

function imagereflection ( $simg, $background = array (255, 255, 255), $gradient = 0.55, $shadow = 0.1, $distance = 0 ) {
  $simgx = imagesx($simg);
  $simgy = imagesy($simg);

  // Höhen von Verlauf und Schatten in px bestimmen
  $gradientH = round($simgy * $gradient);
  $shadowH   = round($simgy * $shadow);

  // Zielbild erzeugen
  $dimg = imagecreatetruecolor($simgx, $simgy + $gradientH + $distance );

  // und mit Hintergrundfarbe füllen
  imagefill($dimg, 0, 0, imagecolorallocate($dimg, $background[0], $background[1], $background[2]));

  // Quellbild kopieren
  imagecopy($dimg, $simg, 0, 0, 0, 0, $simgx, $simgy);

  // und das gespiegelte Bild einfügen
  $simg = imageflip($simg, 1);
  imagecopy($dimg, $simg, 0, $simgy + $distance , 0, 0, $simgx, $simgy);

  // Verlauf erzeugen
  $alphaF = 60 / ($gradientH - 1);
  for ($i = 0; $i < $gradientH; $i++) {
    $col = imagecolorallocatealpha($dimg, $background[0], $background[1], $background[2], 60 - $i * $alphaF);
    imageline($dimg, 0, $simgy + $i + $distance, $simgx, $simgy + $i + $distance, $col);
  }

  // Schatten erzeugen
  $alphaF = 60 / ($shadowH - 1);
  for ($i = 0; $i < $shadowH; $i++) {
    $col = imagecolorallocatealpha($dimg, 160, 160, 160, $i*$alphaF + 67);
    imageline($dimg, 0, $simgy + $i + $distance, $simgx, $simgy + $i + $distance, $col);
  }

  // Bild zurückgeben
  return $dimg;
}

function imageflip($image, $mode) {
  $w = imagesx($image);
  $h = imagesy($image);
  $flipped = imagecreate($w, $h);
  if ($mode) {
    for ($y = 0; $y < $h; $y++) {
      imagecopy($flipped, $image, 0, $y, 0, $h - $y - 1, $w, 1);
    }
  }
  else {
    for ($x = 0; $x < $w; $x++) {
      imagecopy($flipped, $image, $x, 0, $w - $x - 1, 0, 1, $h);
    }
  }
  return $flipped;
}


if($img!="") {

  if ( ! function_exists( 'exif_imagetype' ) ) {
    function exif_imagetype ( $filename ) {
      if ( ( list($width, $height, $type, $attr) = getimagesize( $filename ) ) !== false ) {
        return $type;
      }
      return false;
    }
  }

  $dat_typ = exif_imagetype($img);

  $korr_dattyp = false;

  if($dat_typ==2) {
    $simg = imagecreatefromjpeg($img);
    $korr_dattyp = true;
  }
  else if($dat_typ==3) {
    $simg = imagecreatefrompng($img);
    $korr_dattyp = true;
  }

  if($korr_dattyp) {
    $dimg = imagereflection ($simg, $background, $gradient, $shadow, $distance);

  }
  else {
    $dimg = ImageCreate(130,50);
    $ws = Imagecolorallocate($dimg,255,255,255);
    $sw = Imagecolorallocate($dimg,0,0,0);
    ImageString($dimg,4,1,20,"WRONG IMAGE TYPE",$sw);
  }

}
else {
    $dimg = ImageCreate(100,50);
    $ws = Imagecolorallocate($dimg,255,255,255);
    $sw = Imagecolorallocate($dimg,0,0,0);
    ImageString($dimg,4,1,20,"NO IMAGE",$sw);
}


header("Content-Type: image/jpeg");
Header("Expires: Wed, 11 Nov 2001 11:11:11 GMT");
Header("Cache-Control: no-cache");
Header("Cache-Control: must-revalidate");

Imagejpeg($dimg);
ImageDestroy($dimg);



?>