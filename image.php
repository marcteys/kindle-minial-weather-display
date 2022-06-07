<?php




/* /////////////////////////////////

    LOAD BACKGROUND IMAGE 

*/ /////////////////////////////////


$im = new imagick(realpath("Photos/cloud/ali-abdul-rahman-l0PVhG5Af5E-unsplash.jpg"));
$imageprops = $im->getImageGeometry();
$im->setImageCompressionQuality(100);
$width = $imageprops['width'];
$height = $imageprops['height'];
if($width > $height){
    $newHeight = 800;
    $newWidth = ( 600 / $height) * $width;
}else{
    $newWidth = 600;
    $newHeight = (800 / $width) * $height;
}
    echo $newHeight;

$im->resizeImage($newWidth,$newHeight, imagick::FILTER_LANCZOS, 0.9, true);
$im->cropImage (600,800,0,0);




function image_cover(Imagick $image, $width, $height) {
  $ratio = $width / $height;

  // Original image dimensions.
  $old_width = $image->getImageWidth();
  $old_height = $image->getImageHeight();
  $old_ratio = $old_width / $old_height;

  // Determine new image dimensions to scale to.
  // Also determine cropping coordinates.
  if ($ratio > $old_ratio) {
    $new_width = $width;
    $new_height = $width / $old_width * $old_height;
    $crop_x = 0;
    $crop_y = intval(($new_height - $height) / 2);
  }
  else {
    $new_width = $height / $old_height * $old_width;
    $new_height = $height;
    $crop_x = intval(($new_width - $width) / 2);
    $crop_y = 0;
  }

  // Scale image to fit minimal of provided dimensions.
  $image->resizeImage($new_width, $new_height, imagick::FILTER_LANCZOS, 0.9, true);

  // Now crop image to exactly fit provided dimensions.
  $image->cropImage($new_width, $new_height, $crop_x, $crop_y);
}

















/* /////////////////////////////////

    WRITE TEXT

*/ /////////////////////////////////



$white = "rgba(255, 255, 255,1)";
$whiteTransp = "rgba(255, 255, 255,0.5)";
$fontDINNNext = "fonts/DINNextLTPro-Medium.ttf";
$fontDINNExp = "fonts/D-DINExp.ttf";
$fontWeatherIcon = "fonts/weathericons-regular-webfont.ttf";




// Main temperature
$im = WriteText($im, "15°", $white, 100, $fontDINNExp, 370, 215,\Imagick::ALIGN_CENTER);
// minTemp
$im = WriteText($im, "15°", $whiteTransp, 32, $fontDINNExp, 450, 170,\Imagick::ALIGN_LEFT);
// maxTemp
$im = WriteText($im, "15°", $white, 32, $fontDINNExp, 450, 215,\Imagick::ALIGN_LEFT);

// Main Weather
$im = WriteText($im, "", $white, 80, $fontWeatherIcon, 230, 215,\Imagick::ALIGN_CENTER  );









/* /////////////////////////////////

    SAVE IMAGE

*/ /////////////////////////////////



$fileHandle = fopen("test.jpg", "w");
$im->writeImageFile( $fileHandle);

 




function WriteText($image, $text, $fillColor, $fontSize, $font,$x, $y, $align ) {

    $draw = new \ImagickDraw();
    $draw->setFillColor($fillColor);
    $draw->setStrokeWidth(0);
    $draw->setFontSize($fontSize);
    $draw->setFont($font);
    $draw->setTextAlignment($align);
    $image->annotateimage($draw, $x, $y, 0, $text);

    $draw->setFillColor("rgb(200, 32, 32)");
    $draw->circle($x, $y, $x+2, $y+2);

    $image->drawImage($draw);

    return $image;
}







/* /////////////////////////////////

    ICONS

*/ /////////////////////////////////







ini_set('display_errors', 0);

$xmlfile = file_get_contents("weathericons.xml");
$xml = simplexml_load_string($xmlfile,"SimpleXMLElement");
$iconsList = array();
foreach($xml->children() as $child) {
    $att = $child->attributes();
    $iconsList += array($att->name->__toString() => $child[0]->__toString());
}

echo GetIcon($iconsList,"wi-day-showers");

function GetIcon($list, $name) {
    $name = str_replace("-", "_", $name);
    if(array_key_exists($name, $list)) {
        return $list[$name];
    } else {
       return "";
    }
}







