<?php

// https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=48.847904&lon=2.379711&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__
// https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=48.847904&lon=2.379711&id=&instants=morning,afternoon,evening,night&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__

// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric
// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric


//$url = 'https://www.dicocitations.com/reference_citation/91210/Du_mode_d_existence_des_objets_techniques_1958_/0.php';
//$content = file_get_contents($url);


// path of the log file where errors need to be logged
$log_file = "./my-errors.log";


/* ///////////////////

*   CHECK THE FILES TO SEE THE LAST UPDATE. 

*/ ///////////////////

date_default_timezone_set('Europe/Paris'); 
setlocale(LC_TIME, "fr_FR", "French");

$today = new DateTime('now', new DateTimeZone('Europe/Paris'));
$todayString = $today->format('Y-m-d H:i:s');

error_log("\r\n", 3, $log_file);
error_log("Time: " . strtotime('now') . " ", 3, $log_file);
error_log("(" . $todayString . ") =>", 3, $log_file);
error_log("a", 3, $log_file);

$debug = isset($_GET["debug"]);
$export = isset($_GET["export"]);


error_log("b", 3, $log_file);


/* ///////////////////

*   GET THE WEATHER ONLINE OR FROM THE FILES

*/ ///////////////////



$JSONDATA = json_decode("{}");
$forecast = "";
$raincast = "";

$forecast = file_get_contents("forecast.json");
$raincast = file_get_contents("raincast.json");

$merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );
$JSONDATA = $merged;

$lastUpdate = json_decode(file_get_contents("lastUpdate.json"))->update;


error_log("c", 3, $log_file);

$differenceFromLastUpdate = (strtotime("now") - $lastUpdate);

$forceAPIUpdate = false;
if($differenceFromLastUpdate > (10 * 60)) {
  $forceAPIUpdate = true;
}
else if(isset($_GET["force"])) $forceAPIUpdate = true;
else if(isset($_GET["test"])) $forceAPIUpdate = true;


if($forceAPIUpdate) { // Get The readl data;
	$forecast = "";
	$raincast = "";
  $forecast = file_get_contents("https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=48.847904&lon=2.379711&id=&instants=morning,afternoon,evening,night&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__");
  $raincast = file_get_contents("https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=48.847904&lon=2.379711&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__");

  file_put_contents("lastUpdate.json", json_encode(array("update"=> strtotime("now"))));
  $lastUpdate = strtotime("now");

  file_put_contents("forecast.json", $forecast);
  file_put_contents("raincast.json", $raincast);
  $merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );
  $JSONDATA = $merged;

  if($debug) $debug = "Updated !";
//http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric
}  else {
  if($debug) $debug = "Retrieved !";
}

error_log("d", 3, $log_file);


$batterypercent = "";

if(isset($_GET["battery"])) {
  $batterypercent=$_GET["battery"];
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






error_log("e", 3, $log_file);







// Get Precipitations


$PrecipitationsData = array();
$PrecipitationSum = 0;
for($i = 0; $i < 9; $i++) {
  $tmpTime = strtotime($JSONDATA["raincast"]->properties->forecast[$i]->time);
  $time = date('H\hi', $tmpTime);
  $prec = array(
    "time" => $time,
    "value" => $JSONDATA["raincast"]->properties->forecast[$i]->rain_intensity,
  );
  $PrecipitationSum += $JSONDATA["raincast"]->properties->forecast[$i]->rain_intensity;
  array_push($PrecipitationsData, $prec);
}



error_log("f", 3, $log_file);


$PrevisionsData = array();
for($i = 0; $i < 6; $i++) {
  $momentText = ucwords($JSONDATA["forecast"]->properties->forecast[$i]->moment_day,'-');
  if($momentText == "Après-Midi") $momentText = "Aprèm'";

    $DayData = array(
    "temperature" => round($JSONDATA["forecast"]->properties->forecast[$i]->T),
    "minTemperature" => round($JSONDATA["forecast"]->properties->daily_forecast[0]->T_min),
    "maxTemperature" => round($JSONDATA["forecast"]->properties->daily_forecast[0]->T_max),
    "iconText" => getIcones($JSONDATA["forecast"]->properties->forecast[$i]->weather_description),
    "iconChar" => GetIconDrawing($iconsList,
    							   getIcones($JSONDATA["forecast"]->properties->forecast[$i]->weather_description),
    							   $JSONDATA["forecast"]->properties->forecast[$i]->moment_day == "Nuit"
    							),
    "weatherText" => $JSONDATA["forecast"]->properties->forecast[$i]->weather_description,
    "moment" => $momentText
  );
  array_push($PrevisionsData, $DayData);
}



// From top to bottom
$WeatherData = array(
  "lastUpdateDate" => ucwords(strftime('%A %e %B')),
  "lastUpdateTime" => date('H\hi', $lastUpdate),
  "precipitations" => $PrecipitationSum != 9 ? $PrecipitationsData : null, // false if none
  "previsions" => $PrevisionsData, // false if none
);


file_put_contents("all.json",  print_r($WeatherData,true));


if (empty($WeatherData)) {
  file_put_contents("all.json", "empty");
}
if (!isset($WeatherData)) {
  file_put_contents("all.json", "isset");
}
if (strlen(print_r($WeatherData,true)) < 30)  {
  file_put_contents("all.json", "strlen");
}

file_put_contents("last.json",  date("l jS \of F Y h:i:s A"));

error_log("g", 3, $log_file);



























/* /////////////////////////////////

    LOAD BACKGROUND IMAGE 

*/ /////////////////////////////////





$folderName = $WeatherData['previsions'][0]['iconText'];
$imagesDir = 'Photos/'.$folderName.'/';
if(!is_dir($imagesDir)) $imagesDir = 'Photos/cloud/';
$images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
$randomImageUrl = $images[array_rand($images)]; // See comments
//echo $randomImageUrl;
//$randomImageUrl = "Photos/cloud/ryan-kwok--JykOQ7R2Ls-unsplash.jpg";
//$randomImageUrl = "Photos/cloudy/tony-wallstrom-_nkcMamrvhU-unsplash.jpg";






error_log("h", 3, $log_file);


$im = new imagick(realpath($randomImageUrl));

error_log(realpath($randomImageUrl), 3, $log_file);

$imageprops = $im->getImageGeometry();
error_log("h2", 3, $log_file);

$im->setImageCompressionQuality(100);
error_log("h3", 3, $log_file);

$im->cropThumbnailImage( 600, 800 );

error_log("i", 3, $log_file);






// dégradé haut 
$imagick2 = new Imagick();
//$imagick2->newPseudoImage(600, 300, 'gradient:white-black');
$imagick2->newPseudoImage(600, 100, 'gradient:#bbbbbb-#ffffff');
// Composite images by BLEND model.
$im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY, 0, 0);



error_log("j", 3, $log_file);


// dégradé Bas 
$imagick2 = new Imagick();
//$imagick2->newPseudoImage(600, 300, 'gradient:white-black');
$imagick2->newPseudoImage(600, 280, 'gradient:#ffffff-#555555');
// Composite images by BLEND model.
$im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY, 0, 520);




// dégradé Milieu 

  /*  $draw = new \ImagickDraw();
    $draw->rectangle(110, 110, 510, 250);
    $im->drawImage($draw);*/

  $imagickCrop = new Imagick();
  $imagickCrop = clone $im;
  $imagickCrop->cropImage(400, 140, 110,220);

 // $imagickCrop->resizeImage(1,1,Imagick::FILTER_LANCZOS, 1, true);
  $pixel = $imagickCrop->getImagePixelColor(1,1);
  $colors = $pixel->getHSL();
//print_r($colors); // produces Array([r]=>255,[g]=>255,[b]=>255,[a]=>1); 
//print_r($colors["luminosity"]);
//$a = fopen("a.png", "w");
//$imagickCrop->writeImageFile( $a);

if($colors["luminosity"] > 0.54) 
{ 

   $imagick2 = new Imagick();
  $imagick2->newPseudoImage(600, 100, 'gradient:#ffffff-#aaaaaa');
  $im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY, 0, 100);
  $imagick2->newPseudoImage(600, 200, 'gradient:#aaaaaa-#ffffff');
  $im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY , 0, 200);
}
// dégradé Milieu 






/* /////////////////////////////////

    WRITE TEXT

*/ /////////////////////////////////



$white = "rgba(255, 255, 255,1)";
$whiteTransp = "rgba(255, 255, 255,0.5)";
$transp = "rgba(255, 255, 255,0)";
$blackStroke = "rgba(0, 0, 0, 0.5)";
$blackTransp = "rgba(0, 0, 0, 0.2)";
$noColor = "rgba(255, 255, 255,0)";
$fontDINNNext = "fonts/D-DIN.ttf";
$fontDINNNextBold = "fonts/D-DIN-Bold.ttf";
$fontDINNExp = "fonts/D-DINExp.ttf";
$fontWeatherIcon = "fonts/weathericons-regular-webfont.ttf";



if($debug != "" ||  $debug != null) {
  $im = WriteText($im, $debug, $white, 100, $fontDINNNextBold, 370, 415,\Imagick::ALIGN_CENTER);
}


error_log("k", 3, $log_file);

$topBasePosition = 225;


// Main temperature
/* // SHADOWS
$im = WriteText($im, $WeatherData['previsions'][0]['temperature']."°", $blackTransp, 110, $fontDINNExp, 365, $topBasePosition,\Imagick::ALIGN_CENTER);
$im = WriteText($im, $WeatherData['previsions'][0]['temperature']."°", $blackTransp, 110, $fontDINNExp, 375, $topBasePosition,\Imagick::ALIGN_CENTER);
$im = WriteText($im, $WeatherData['previsions'][0]['temperature']."°", $blackTransp, 110, $fontDINNExp, 375, $topBasePosition-5,\Imagick::ALIGN_CENTER);
$im = WriteText($im, $WeatherData['previsions'][0]['temperature']."°", $blackTransp, 110, $fontDINNExp, 375, $topBasePosition+5,\Imagick::ALIGN_CENTER);
*/


$im = WriteText($im, $WeatherData['previsions'][0]['temperature']."°", $white, 110, $fontDINNExp, 370, $topBasePosition,\Imagick::ALIGN_CENTER);


// Main temperature text
$im = WriteText($im, $WeatherData['previsions'][0]['weatherText'], $white, 40, $fontDINNNext, 300, $topBasePosition + 75,\Imagick::ALIGN_CENTER);


// minTemp
$im = WriteText($im, $WeatherData['previsions'][0]['minTemperature']."°", $whiteTransp, 40, $fontDINNExp, 460, $topBasePosition -50,\Imagick::ALIGN_LEFT);
// maxTemp
$im = WriteText($im, $WeatherData['previsions'][0]['maxTemperature']."°", $white, 40, $fontDINNExp, 460, $topBasePosition,\Imagick::ALIGN_LEFT);

// Main Weather Icon
$im = WriteText($im, $WeatherData['previsions'][0]['iconChar'], $white, 95, $fontWeatherIcon, 200, $topBasePosition,\Imagick::ALIGN_CENTER  );



// Date
$im = WriteText($im, $WeatherData['lastUpdateDate'], $white, 27, $fontDINNNext, 35, 50,\Imagick::ALIGN_LEFT);
$im = WriteText($im, $WeatherData['lastUpdateTime'], $white, 27, $fontDINNNext, 600-35, 50,\Imagick::ALIGN_RIGHT);


$im = WriteText($im, date('H\hi', strtotime('now')), $white, 14, $fontDINNNext, 600-35, 67,\Imagick::ALIGN_RIGHT);

if($batterypercent != "") {
  $posX = 270;
  $posY =28;
  // Drawing battery icon
  $draw = new \ImagickDraw();
  $draw->setFillColor($transp);
  $draw->setStrokeColor($white);
  $draw->setStrokeOpacity(1);
  $draw->setStrokeWidth(2);
  $draw->roundRectangle($posX + 0, $posY + 0, $posX + 60, $posY + 30, 5, 5);
  $im->drawImage($draw);
  $draw->roundRectangle($posX + 0, $posY + 0, $posX + 60, $posY + 30, 5, 5);
  $im->drawImage($draw);

 /* $draw = new \ImagickDraw();
  $draw->setFillColor($white);
  $draw->setStrokeOpacity(1);
  $draw->setStrokeWidth(2);
  $draw->roundRectangle($posX + 60, $posY + 15, $posX + 5, $posY + 5, 5, 5);
  $im->drawImage($draw);*/

  $draw = new \ImagickDraw();
  $draw->setFillColor($transp);
  $draw->setStrokeColor($white);
  $draw->setStrokeOpacity(1);
  $draw->setStrokeWidth(2);
  $draw->rectangle($posX + 60,  $posY + 10, $posX +66, $posY + 20);
  $im->drawImage($draw);
  $im->drawImage($draw);


  $draw = new \ImagickDraw();
  $draw->setFillColor($white);
    $draw->setStrokeWidth(0);
  $draw->rectangle($posX + 5,  $posY + 5, $posX +15, $posY + 25);
  $im->drawImage($draw);

  $im = WriteText($im, $batterypercent, $white, 24, $fontDINNNext, $posX + 26, $posY + 24,\Imagick::ALIGN_LEFT);
}




error_log("l", 3, $log_file);


$position = 85; // 60 + 25
$width = 110;
$basePosition = 630;
// Weathers du bas 
for( $i = 1; $i < 6 ;$i++) {

    // draw line
    if($i != 1 && $WeatherData['previsions'][$i]['moment'] == "Matin") {
      $draw = new \ImagickDraw();
      $draw->setStrokeColor($whiteTransp);
      $draw->setFillColor($noColor);
      $draw->setStrokeWidth(2.5);
      $draw->line($position- ($width / 2 ), $basePosition - 25 , $position- ($width / 2 ), $basePosition + 115);
      $im->drawImage($draw);
    }

    //titre
    $im = WriteText($im, $WeatherData['previsions'][$i]['moment'], $white, 27, $fontDINNNext, $position, $basePosition,\Imagick::ALIGN_CENTER);
    // icone
    $im = WriteText($im, $WeatherData['previsions'][$i]['iconChar'], $white, 45, $fontWeatherIcon, $position,  $basePosition + 60,\Imagick::ALIGN_CENTER);
    //temp
     $im = WriteText($im, $WeatherData['previsions'][$i]['temperature']."°", $white, 27, $fontDINNNextBold, $position,  $basePosition + 110,\Imagick::ALIGN_CENTER);
    $position += $width; // width = 120
}


error_log("m", 3, $log_file);



/* /////////////////////////////////

    Rain Cast

*/ /////////////////////////////////

    // icone


if( $WeatherData['precipitations'] != null ) {


  $leftMargin = 70;
  $topPosition = 335;
  $width = 36;
  $height = 7;
  $margin = 3;

   $im = WriteText($im, "", $white, 36, $fontWeatherIcon, 55, $topPosition + 15,\Imagick::ALIGN_CENTER);

  // First six rain values 
  for( $i = 0; $i < 6 ;$i++) {
      $draw = new \ImagickDraw();
      $draw->setFillColor($white);
      $position = $leftMargin + $i * $width + ($i * $margin);


      for($x = 0; $x <$WeatherData['precipitations'][$i]['value']; $x++) {
          $newTopPosition =  $topPosition - ($x * $height ) - ( $x * $margin);
          $draw->rectangle($position, $newTopPosition, $position + $width , $newTopPosition+$height);
      }
      $im->drawImage($draw);
  }

error_log("n", 3, $log_file);

  // last three 

  $width = 72;
  $leftMargin = 304;
  for( $i = 0; $i < 3 ;$i++) {
      $draw = new \ImagickDraw();
      $draw->setFillColor($white);
      $position = $leftMargin + $i * $width + ($i * $margin);
      for($x = 0; $x < $WeatherData['precipitations'][$i+6]['value']; $x++) {
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
      $im = WriteText($im, $WeatherData['precipitations'][0]['time'], $white, 14, $fontDINNNextBold, 70, $topPosition + 24,\Imagick::ALIGN_LEFT);
      // time end
      $im = WriteText($im, $WeatherData['precipitations'][8]['time'], $white, 14, $fontDINNNextBold, 526, $topPosition + 24,\Imagick::ALIGN_RIGHT);



} // If there are no precipitationss


/* /////////////////////////////////

    SAVE IMAGE

*/ /////////////////////////////////


error_log("o", 3, $log_file);

 


function WriteText($image, $text, $fillColor, $fontSize, $font,$x, $y, $align ) {

    $draw = new \ImagickDraw();
    $draw->setFillColor($fillColor);
    $draw->setStrokeWidth(0);
    //$draw->setStrokeColor("rgba(0, 0, 0, 1)");
    //    $draw->setStrokeOpacity(.1);
    $draw->setFontSize($fontSize);
    $draw->setFont($font);
    $draw->setTextAlignment($align);
    $image->annotateimage($draw, $x, $y, 0, $text);


    //$draw->setFillColor("rgb(200, 32, 32)");
    //$draw->circle($x, $y, $x+2, $y+2);

    $image->drawImage($draw);

    return $image;
}






/*






*/
























/* ///////////////////

*   TOOLS

*/ ///////////////////






   function getIcones($_var) {
    $icon = lowerAccent($_var);
    $icon = str_replace('-',' ', $icon);
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
    else if($icon == 'ciel voile nuit') return 'night-alt-cloudy';
    else if($icon == 'eclaircies') return 'day-cloudy';
    else if($icon == 'peu nuageux') return 'day-sunny-overcast';
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


  function GetIconDrawing($list, $name, $night) {
    if ( ! (strpos($name, 'wi') === 0) ) {
    	if($night === true)
    	    $name = "wi_night_".$name;
		else
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
/*

if($debug ) {
echo '<pre>';
print_r($JSONDATA);
echo '</pre>';}

if($debug ) {

  echo '<pre>';
print_r($WeatherData */