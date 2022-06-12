<?php 

include("image.php");

//$im->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
$im->setImageType(\Imagick::IMGTYPE_GRAYSCALEMATTE);


// add the "Content-type" header
header('Content-type: image/png'); 
 
// add a "Expires" header with an offset of 10 min
$offset = 60 * 10; // (seconds * minutes)    
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($expire);
 
// add a "Cache-control" header
header("Cache-Control: max-age=600, must-revalidate");
 
// Set the image format to JPEG and enable compression
$im->setImageFormat("png");
$im->setImageCompression(Imagick::COMPRESSION_JPEG);
 
// Set compression level (1 lowest quality, 100 highest quality)
$im->setImageCompressionQuality(100);
 
// Strip out unneeded meta data
$im->stripImage();
 
echo $im;
exit;


 ?>