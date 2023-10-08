<?php 
 include("settings.php");

$verbose = false;

if(isset($_GET['verbose'])) {
    if($_GET['verbose'] == "true")
        $verbose = true;
}


if($verbose) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
error_log("\r\n----- STARTING NEW IMAGE------\r\n", 3, $log_file);

try {
    include("image.php");
    error_log("Finishing treating image.\r\n", 3, $log_file);
}
catch (\Exception $e) {
    error_log(" error1 ".$e, 3, $log_file);
}
catch (\Throwable $e) {
  error_log(" error2 ".$e, 3, $log_file);
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



error_log("Black and white done.\r\n", 3, $log_file);
error_log("Processing complete.\r\n", 3, $log_file);

//$im->posterizeimage(16, 'true');

    if(!$verbose) {
        $fileHandle = fopen("weatherImage.png", "w");
        $im->writeImageFile( $fileHandle);
        error_log("File saved on disk.\r\n", 3, $log_file);
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
        error_log("File displayed. Time now:", 3, $log_file);
        error_log(strtotime('now')."\r\n", 3, $log_file);
        echo $im;
        exit;
    } else {
        echo '<pre>';
        echo file_get_contents($log_file);
        echo '</pre>';
    }
    error_log("Script finished at ".strtotime('now') ."\r\n", 3, $log_file);

?>