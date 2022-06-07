<?php



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

$fileHandle = fopen("test.jpg", "w");
$im->writeImageFile( $fileHandle);

 


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



/*


  $backgroundImagick = new \Imagick(realpath("Photos/cloud/billy-huynh-v9bnfMCyKbg-unsplash.jpg"));
    $imagick = new \Imagick();
    $imagick->setCompressionQuality($quality);
    $imagick->newPseudoImage(
        $backgroundImagick->getImageWidth(),
        $backgroundImagick->getImageHeight(),
        'canvas:white'
    );

    $imagick->compositeImage(
        $backgroundImagick,
        \Imagick::COMPOSITE_ATOP,
        0,
        0
    );
    
    $imagick->setFormat("jpg");    
    header("Content-Type: image/jpg");
    echo $imagick->getImageBlob();

*/
/*


    $image = new \Imagick(600, 800);
            $image->setCompressionQuality(1);



    $image->newPseudoImage(
        $backGroundImage->getImageWidth(),
        $backGroundImage->getImageHeight(),
        'canvas:white'
    );

    $image->compositeImage(
        $backGroundImage,
        \Imagick::COMPOSITE_ATOP,
        0,
        0
    );

*/
   // $image->newImage(600, 800, new \ImagickPixel('pink'));
  //  $image->setImageFormat("jpg");
  /* $texture = new \Imagick(realpath("Photos/cloud/ali-abdul-rahman-l0PVhG5Af5E-unsplash.jpg"));
    $texture->scaleimage($image->getimagewidth() / 4, $image->getimageheight() / 4);
    $image = $image->textureImage($texture);*/
//    $image->drawImage($backGroundImage);
    # Combine multiple images into one, stackde vertically.
//$image = $backGroundImage->appendImages(true);
  //  $image->drawImage($draw);



  //  $imagick = new \Imagick(realpath("Photos/cloud/ali-abdul-rahman-l0PVhG5Af5E-unsplash.jpg"));
/*
   $imagick = new \Imagick(realpath("Photos/cloud/ali-abdul-rahman-l0PVhG5Af5E-unsplash.jpg"));

    640, 480, 
    $imagick->setImageFormat("jpg");

    header("Content-Type: image/jpg");
    echo $image->getImageBlob();


*/
