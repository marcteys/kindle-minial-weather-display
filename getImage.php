<?php 

try {

include("image.php");
    $fileHandle = fopen("weatherImage.png", "w");
    $im->writeImageFile( $fileHandle);

    error_log("p", 3, $log_file);

}
catch (\Exception $e) {
   // echo "lol";
}
catch (\Throwable $e) {
  //  echo "lol";
}




//$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im = $im->fxImage('intensity');




    
error_log("q", 3, $log_file);

//$im->posterizeimage(16, 'true');


// add the "Content-type" header
header('Content-type: image/png'); 
 /*
// add a "Expires" header with an offset of 10 min
$offset = 60 * 10; // (seconds * minutes)    
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($expire);
 
// add a "Cache-control" header
header("Cache-Control: max-age=600, must-revalidate");*/
 
// Set the image format to JPEG and enable compression
$im->setImageFormat("png");
//$im->setImageCompression(Imagick::COMPRESSION_JPEG);

// Set compression level (1 lowest quality, 100 highest quality)
//$im->setImageCompressionQuality(100);
 
// Strip out unneeded meta data
$im->stripImage();
 




 error_log("r ", 3, $log_file);
 error_log(strtotime('now'), 3, $log_file);


echo $im;
exit;


 ?>