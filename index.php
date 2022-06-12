<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test</title>
</head>
<body>
<?php



// https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=48.847904&lon=2.379711&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__
// https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=48.847904&lon=2.379711&id=&instants=morning,afternoon,evening,night&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__

// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric
// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric


//$url = 'https://www.dicocitations.com/reference_citation/91210/Du_mode_d_existence_des_objets_techniques_1958_/0.php';
//$content = file_get_contents($url);



/* ///////////////////

*   CHECK THE FILES TO SEE THE LAST UPDATE. 

*/ ///////////////////

date_default_timezone_set('Europe/Paris'); 
$today = new DateTime('now', new DateTimeZone('Europe/Paris'));
$todayString = $today->format('Y-m-d H:i:s');
$file = 'weather.json';
$content =  json_decode(file_get_contents($file));
$lastUpdate =  $content->lastUpdate;
$fileDate = new DateTime($lastUpdate);
$secondsBetweenTwoEvents = (strtotime($todayString) - strtotime($fileDate->format("Y-m-d H:i:s")));
if($secondsBetweenTwoEvents < (20 * 60) && !isset($_GET["force"])) { // 10 minutes
  echo file_get_contents($file);
  return;
  echo "This should never happend";
}




/* ///////////////////

*   GET THE WEATHER ONLINE OR FROM THE FILES

*/ ///////////////////



$JSONDATA = json_decode("{}");
$forecast = "";
$raincast = "";

if(!isset($_GET["test"])) { // Get The readl data;

$forecast = file_get_contents("https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=48.847904&lon=2.379711&id=&instants=morning,afternoon,evening,night&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__");
$raincast = file_get_contents("https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=48.847904&lon=2.379711&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__");
   file_put_contents("forecast.json", $forecast);
  file_put_contents("raincast.json", $raincast);

//http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric
} else {
    $forecast = file_get_contents("forecast.json");
    $raincast = file_get_contents("raincast.json");
}

//var_dump(json_decode($forecast));

$merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );
$JSONDATA = $merged;




/* ///////////////////

*   Make the data pretty

*/ ///////////////////


// Get Precipitations

$PrecipitationsData = array();

for($i = 0; $i < 9; $i++) {
  $tmpTime = strtotime($JSONDATA["raincast"]->properties->forecast[$i]->time);
  $time = date('H\hi', $tmpTime);
  $prec = array(
    "time" => $time,
    "value" => $JSONDATA["raincast"]->properties->forecast[$i]->rain_intensity,
  );
array_push($PrecipitationsData, $prec);
}






$PrevisionsData = array();
for($i = 0; $i < 6; $i++) {
  $DayData = array(
  "temperature" => round($JSONDATA["forecast"]->properties->forecast[$i]->T),
  "minTemperature" => round($JSONDATA["forecast"]->properties->daily_forecast[0]->T_min),
  "maxTemperature" => round($JSONDATA["forecast"]->properties->daily_forecast[0]->T_max),
  "icon" => getIcones($JSONDATA["forecast"]->properties->forecast[$i]->weather_description),
  "weatherText" => $JSONDATA["forecast"]->properties->forecast[$i]->weather_description,
  "moment" => $JSONDATA["forecast"]->properties->forecast[$i]->moment_day
);
  array_push($PrevisionsData, $DayData);
}


// From top to bottom
$WeatherData = array(
  "lastUpdateDate" => ucwords(strftime('%A %e %B')),
  "lastUpdateTime" => $today->format('H\hi'),
  "precipitations" => $PrecipitationsData, // false if none
  "previsions" => $PrevisionsData, // false if none
);





























/* /////////////////////////////////

    LOAD BACKGROUND IMAGE 

*/ /////////////////////////////////




setlocale(LC_TIME, "fr_FR", "French");

$folderName = $WeatherData['previsions'][0]['icon'];
$imagesDir = 'Photos/'.$folderName.'/';
if(!is_dir($imagesDir)) $imagesDir = 'Photos/cloud/';
$images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
$randomImageUrl = $images[array_rand($images)]; // See comments
//echo $randomImageUrl;
//$randomImageUrl = "Photos/day-sunny/sindy-sussengut-4V3X-GQLwYA-unsplash.jpg";



$im = new imagick(realpath($randomImageUrl));
$imageprops = $im->getImageGeometry();
$im->setImageCompressionQuality(100);
$im->cropThumbnailImage( 600, 800 );



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
$fontDINNNext = "fonts/D-DIN.ttf";
$fontDINNNextBold = "fonts/D-DIN-Bold.ttf";
$fontDINNExp = "fonts/D-DINExp.ttf";
$fontWeatherIcon = "fonts/weathericons-regular-webfont.ttf";


// Main temperature
$im = WriteText($im, "15°", $white, 100, $fontDINNExp, 370, 215,\Imagick::ALIGN_CENTER);
// Main temperature text
$im = WriteText($im, "Très nuageux", $white, 30, $fontDINNNext, 300, 275,\Imagick::ALIGN_CENTER);


// minTemp
$im = WriteText($im, "15°", $whiteTransp, 32, $fontDINNExp, 450, 170,\Imagick::ALIGN_LEFT);
// maxTemp
$im = WriteText($im, "15°", $white, 32, $fontDINNExp, 450, 215,\Imagick::ALIGN_LEFT);

// Main Weather
$im = WriteText($im, "", $white, 80, $fontWeatherIcon, 230, 215,\Imagick::ALIGN_CENTER  );

// Date
$im = WriteText($im, "Mardi 6 Juin", $white, 20, $fontDINNNext, 45, 45,\Imagick::ALIGN_LEFT);
$im = WriteText($im, "20h30", $white, 20, $fontDINNNext, 600-45, 45,\Imagick::ALIGN_RIGHT);




$position = 100;
$width = 100;
// Weathers du bas 
for( $i = 0; $i < 5 ;$i++) {

    //titre
    $im = WriteText($im, "Soirée", $white, 20, $fontDINNNext, $position, 600,\Imagick::ALIGN_CENTER);
    // icone
    $im = WriteText($im, "", $white, 37, $fontWeatherIcon, $position, 660,\Imagick::ALIGN_CENTER);
    //temp
     $im = WriteText($im, "13°", $white, 20, $fontDINNNextBold, $position, 710,\Imagick::ALIGN_CENTER);
    $position += $width; // width = 120
}





/* /////////////////////////////////

    Rain Cast

*/ /////////////////////////////////

    // icone

$leftMargin = 70;
$topPosition = 335;
$width = 36;
$height = 7;
$margin = 3;

    $im = WriteText($im, "", $white, 36, $fontWeatherIcon, 55, $topPosition + 15,\Imagick::ALIGN_CENTER);


for( $i = 0; $i < 6 ;$i++) {
    $draw = new \ImagickDraw();
    $draw->setFillColor($white);
    $position = $leftMargin + $i * $width + ($i * $margin);
    for($x = 0; $x <4; $x++) {
        $newTopPosition =  $topPosition - ($x * $height ) - ( $x * $margin);
        $draw->rectangle($position, $newTopPosition, $position + $width , $newTopPosition+$height);
    }
    $im->drawImage($draw);
}



$width = 72;
$leftMargin = 304;
for( $i = 0; $i < 3 ;$i++) {
    $draw = new \ImagickDraw();
    $draw->setFillColor($white);
    $position = $leftMargin + $i * $width + ($i * $margin);
    for($x = 0; $x <4; $x++) {
        $newTopPosition =  $topPosition - ($x * $height ) - ( $x * $margin);
        $draw->rectangle($position, $newTopPosition, $position + $width , $newTopPosition+$height);
    }
    $im->drawImage($draw);

}




// Text 
for( $i = 0; $i < 5 ;$i++) {
    $text  = "";
    $text .= 1+$i."";
    $text .= "0min";
    $textPos = 150 + ($margin + $width ) * $i;
    $im = WriteText($im, $text, $white, 12, $fontDINNNext, $textPos, $topPosition + 22,\Imagick::ALIGN_CENTER);
}
    // time start
    $im = WriteText($im, "22h30", $white, 14, $fontDINNNextBold, 70, $topPosition + 24,\Imagick::ALIGN_LEFT);
    // time end
    $im = WriteText($im, "23h30", $white, 14, $fontDINNNextBold, 526, $topPosition + 24,\Imagick::ALIGN_RIGHT);




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


    //$draw->setFillColor("rgb(200, 32, 32)");
    //$draw->circle($x, $y, $x+2, $y+2);

    $image->drawImage($draw);

    return $image;
}







/* /////////////////////////////////

    ICONS

*/ /////////////////////////////////






$xmlfile = file_get_contents("weathericons.xml");
$xml = simplexml_load_string($xmlfile,"SimpleXMLElement");
$iconsList = array();
foreach($xml->children() as $child) {
    $att = $child->attributes();
    $iconsList += array($att->name->__toString() => $child[0]->__toString());
}

//echo GetIcon($iconsList,"day-showers");




/*



// add the "Content-type" header
header('Content-type: image/jpeg'); 
 
// add a "Expires" header with an offset of 24 hours
$offset = 3600 * 24;    
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
header($expire);
 
// add a "Cache-control" header
header("Cache-Control: max-age=3600, must-revalidate");
 
// Set the image format to JPEG and enable compression
$image->setImageFormat("jpeg");
$image->setImageCompression(Imagick::COMPRESSION_JPEG);
 
// Set compression level (1 lowest quality, 100 highest quality)
$image->setImageCompressionQuality(90);
 
// Strip out unneeded meta data
$image->stripImage();
 
echo $image;
exit;



*/
























/* ///////////////////

*   TOOLS

*/ ///////////////////






   function getIcones($_var) {
    $icon = lowerAccent($_var);
    if($icon == '' ) return 'day-sunny';
    else if($icon == 'nuit claire') return 'night-clear';
    else if($icon == 'tres nuageux') return 'cloudy';
    else if($icon == 'couvert') return 'cloudy';
    else if($icon == 'brume') return 'fog';
    else if($icon == 'brume ou bancs de brouillard') return 'fog';
    else if($icon == 'brouillard') return 'fog';
    else if($icon == 'brouillard givrant') return 'fog';
    else if($icon == 'risque de grele') return 'hail';
    else if($icon == 'orages') return 'lightning';
    else if($icon == 'risque d\'orages') return 'lightning';
    else if($icon == 'pluie orageuses') return 'thunderstorm';
    else if($icon == 'pluies orageuses') return 'thunderstorm';
    else if($icon == 'averses orageuses') return 'thunderstorm';
    else if($icon == 'ciel voile') return 'cloud';
    else if($icon == 'ciel voile nuit') return 'cloud';
    else if($icon == 'eclaircies') return 'cloud';
    else if($icon == 'peu nuageux') return 'cloud';
    else if($icon == 'pluie forte') return 'rain';
    else if($icon == 'bruine / pluie faible') return 'showers';
    else if($icon == 'bruine') return 'showers';
    else if($icon == 'pluie faible') return 'showers';
    else if($icon == 'pluies eparses / rares averses') return 'showers';
    else if($icon == 'pluies eparses') return 'showers';
    else if($icon == 'rares averses') return 'showers';
    else if($icon == 'pluie moderee') return 'rain';
    else if($icon == 'pluie / averses') return 'rain';
    else if($icon == 'pluie faible') return 'showers';
    else if($icon == 'averses') return 'rain';
    else if($icon == 'pluie') return 'rain';
    else if($icon == 'neige') return 'snow';
    else if($icon == 'neige forte') return 'snow';
    else if($icon == 'quelques flocons') return 'snow';
    else if($icon == 'averses de neige') return 'snow';
    else if($icon == 'neige / averses de neige') return 'snow';
    else if($icon == 'pluie et neige') return 'snow';
    else if($icon == 'pluie verglacante') return 'sleet';
    else if($icon == 'ensoleille') return 'day-sunny';
    else return 'day-sunny';
  }

  function lowerAccent($_var) {
    $return = str_replace(' ','_',strtolower($_var));
    $return = preg_replace('#Ç#', 'C', $return);
    $return = preg_replace('#ç#', 'c', $return);
    $return = preg_replace('#è|é|ê|ë#', 'e', $return);
    $return = preg_replace('#à|á|â|ã|ä|å#', 'a', $return);
    $return = preg_replace('#ì|í|î|ï#', 'i', $return);
    $return = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $return);
    $return = preg_replace('#ù|ú|û|ü#', 'u', $return);
    $return = preg_replace('#ý|ÿ#', 'y', $return);
    $return = preg_replace('#Ý#', 'Y', $return);
    $return = str_replace('_', '-', $return);
    $return = str_replace('\'', '', $return);
    return $return;
  }


  function GetIconDrawing($list, $name) {
    if ( ! (strpos($name, 'wi') === 0) ) {
        $name = "wi_".$name;
    }

    $name = str_replace("-", "_", $name);
    if(array_key_exists($name, $list)) {
        return $list[$name];
    } else {
       return "";
    }
}


/*





$time = date("H");
$timezone = date("e");


// starting at this limit, switch
if(isset($_GET["limit"]))
  $limit = (int)$_GET["limit"];
else
  $limit = 20;

if($time < $limit) {
  $datetime = new DateTime('today', new DateTimeZone('Europe/Paris'));
  $resultDate  = $datetime->format('Y-m-d 15:00:00');
} else {
  $datetime = new DateTime('tomorrow', new DateTimeZone('Europe/Paris'));
  $resultDate  = $datetime->format('Y-m-d 15:00:00');
}

$temp = 0;
$icon =null;

$dateFound = false;

 foreach ($JSONDATA->list as $key => $value) {
   $t = new DateTime($value->dt_txt);

if($dateFound) continue;
  if($resultDate <= $t->format("Y-m-d H:i:s")) { // take values superor than today
          $temp = (int) $value->main->temp;
      //    $txt .= (int) $value->main->temp .'|';
          //echo '|';
          //echo $value->weather[0]->main;
          $icon = strtolower($value->weather[0]->icon);
          //$txt .= $value->weather[0]->main .'|';
          $dateFound = true;
        }
 }






$rainChart = array();
$weatherChart = array();


$count  = 0;

//echo strtotime($resultDate);
$listWeather = (Array) $JSONDATA->list;
for($i = 0; $i < count($JSONDATA->list); $i++) 
{
$value = $JSONDATA->list[$i];

   $t = new DateTime($value->dt_txt);

    //  var_dump((Array)($value->rain)[0]);

   // echo  $resultDate; echo " - ";  echo $t->format("Y-m-d H:i:s");
  //  > strtotime($resultDate). ' <br>';
 //  echo "-"; echo $datetime->format('Y-m-d 06:00:00');    echo "<br>";

  if($datetime->format('Y-m-d H:i:s') <= $t->format("Y-m-d H:i:s")) { // take values superor than today
 //echo($i);


    if($count < 10) {
     // echo $t->format("Y-m-d H:i:s"); echo "<br>";

      if(property_exists($value, "rain")) {
       $a =  (float)$value->rain->{"3h"};
          $rainVal = round($a * 100 / 8);
          $rainVal = round($a * 2);
          $rainChart[$count] = $rainVal;
        } else {
          $rainChart[$count] = 0;
        }

        $weatherValue = (int)$value->main->temp;
        $weatherChart[$count] =    round($weatherValue * 7 / 25);
        if( $weatherChart[$count] < 0)  $weatherChart[$count] = 0;

    } else {
      continue;
    }

    $count++;
  }


  //    echo "<br>";

 }

//var_dump($rainChart);
//var_dump($weatherChart);







$finalValue = array("temp" => $temp, "icon"=> $icon, "rainChart"=>$rainChart, "weatherChart"=>$weatherChart,"lastUpdate" => $todayString);
$finalValueJson = json_encode($finalValue);

echo $finalValueJson;
 file_put_contents($file, $finalValueJson);

*/



?>
<img src="test.jpg">





</body>
</html>
