<?php

$usmap = 'test.svg';
$im = new Imagick();
$svg = file_get_contents($usmap);

$im->readImageBlob($svg);

$im->setImageFormat("png24");
$im->resizeImage(800, 600, imagick::FILTER_LANCZOS, 1);  /*Optional, if you need to resize*/

$im->writeImage('blank-us-map.png');

header('Content-type: image/png');
echo $im;

$im->clear();
$im->destroy();

?>