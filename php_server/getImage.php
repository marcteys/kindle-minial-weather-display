<?php 

try {
    include("image.php");

    error_log("p", 3, $log_file);
}
catch (\Exception $e) {
   // echo "lol";
        error_log(" errooooor ", 3, $log_file);

}
catch (\Throwable $e) {
  //  echo "lol";
  error_log(" erraaaaaaar ", 3, $log_file);
}

//$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im = $im->fxImage('intensity');



$colorSpace   = 30;
$treeDepth    = 10;
$dither       = 20;
$measureError = 0;
$im->quantizeImage(16, $colorSpace,$treeDepth,$dither,$measureError);

// Now we have reduced the image to NUM_COLORS - what are those colors ?
$nColors = $im->getImageColors();


for ($i=0;$i<$nColors;$i++) {
       
$oImPixel = $im->getImageColormapColor($i);
$aColor = $oImPixel->getColor();

// Convert to RGB hex values
// --------------------------
$r = str_pad(dechex($aColor['r']),2,0,STR_PAD_LEFT);
$g = str_pad(dechex($aColor['g']),2,0,STR_PAD_LEFT);
$b = str_pad(dechex($aColor['b']),2,0,STR_PAD_LEFT);

} // end for each color






error_log("q", 3, $log_file);

//$im->posterizeimage(16, 'true');

$fileHandle = fopen("weatherImage.png", "w");
$im->writeImageFile( $fileHandle);
// add the "Content-type" header
header('Content-type: image/png'); 
 /*
// add a "Expires" header with an offset of 10 min
$offset = 60 * 10; // (seconds * minutes)    
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($expire);
header("Cache-Control: max-age=600, must-revalidate");*/
 
$im->setImageFormat("png");
$im->stripImage();
error_log("r ", 3, $log_file);
error_log(strtotime('now'), 3, $log_file);
echo $im;
exit;
?>