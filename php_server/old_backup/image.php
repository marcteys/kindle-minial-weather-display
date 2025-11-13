<?php


/* ///////////////////

*   CHECK THE FILES TO SEE THE LAST UPDATE. 

*/ ///////////////////

$todayString = $today->format('Y-m-d H:i:s');

error_log("\r\n", 3, $log_file);
error_log("Starting time: " . $todayString . " ", 3, $log_file);
error_log("( " . strtotime('now')  . " )", 3, $log_file);
error_log("\r\n", 3, $log_file);
$debug = isset($_GET["debug"]);
$export = isset($_GET["export"]);
$batterypercent = "";
if(isset($_GET["battery"])) {
  $batterypercent=$_GET["battery"];
}
error_log("Found GET parameters in url. debug:".$debug." , export:".$export." , batterypercent:".$batterypercent." \r\n", 3, $log_file);

/* ///////////////////

*   GET THE WEATHER ONLINE OR FROM THE FILES

*/ ///////////////////


error_log("Loading forecast and raincast from previous files. ", 3, $log_file);

$JSONDATA = json_decode("{}");
$forecast = "";
$raincast = "";

$forecast = file_get_contents("forecast.json");
$raincast = file_get_contents("raincast.json");

$merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );
$JSONDATA = $merged;

$lastUpdate = json_decode(file_get_contents("lastUpdate.json"))->update;

error_log("Complete.\r\n", 3, $log_file);


$differenceFromLastUpdate = (strtotime("now") - $lastUpdate);

$forceAPIUpdate = false;
var_dump($timeInMinutesBetweenUpdates * 60);
var_dump($differenceFromLastUpdate * 60);

if($differenceFromLastUpdate > ($timeInMinutesBetweenUpdates * 60)) {
  $forceAPIUpdate = true;
}
else if(isset($_GET["force"])) $forceAPIUpdate = true;
else if(isset($_GET["test"])) $forceAPIUpdate = true;


if($forceAPIUpdate) { // Get The readl data;

  error_log("Forcing forecast and raincast from meteofrance. ", 3, $log_file);

	$forecast = "";
	$raincast = "";
  $forecastTargetUrl = "https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=".$lat."&lon=".$lon."&id=&instants=morning,afternoon,evening,night&token=".$token;
  $raincastTargetUrl = "https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=".$lat."&lon=".$lon."&token=".$token;
  error_log("\r\nForecastTargetUrl: " .$forecastTargetUrl , 3, $log_file);
  error_log("\r\nRaincastTargetUrl: " . $raincastTargetUrl."\r\n", 3, $log_file);
      // here, the @ hides the warning if the file_get_contents fails
      
      
  $context = stream_context_create([
    'http' => [
        'timeout' => 3,
    ],
    'socket' => [
        'connect_timeout' => 3,
    ]
  ]);
  $forecast = file_get_contents($forecastTargetUrl, false,$context);
  if($forecast != "")    error_log("Error loading Forecast, timout.\r\n", 3, $log_file);
  var_dump($forecast);

  $raincast = file_get_contents($raincastTargetUrl, false,$context);
  if($raincast != "")    error_log("Error loading Raincast, timout.\r\n", 3, $log_file);
  var_dump($raincast);

  if($forecast != "" && $raincast != "") {

  file_put_contents("lastUpdate.json", json_encode(array("update"=> strtotime("now"))));
  $lastUpdate = strtotime("now");

  file_put_contents("forecast.json", $forecast);
  file_put_contents("raincast.json", $raincast);
  $merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );
  $JSONDATA = $merged;

  if($debug) $debug = "Updated !";
  error_log("Loaded.\r\n", 3, $log_file);
  } else {
    error_log("Error, cannot load forecast or raincast URL !! Using the privous one.\r\n", 3, $log_file);
  }

}  else {
  error_log("Error loading.\r\n", 3, $log_file);
  if($debug) $debug = "Retrieved !";
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

error_log("Sorting icons file to array.\r\n", 3, $log_file);

//echo GetIcon($iconsList,"day-showers");










/* ///////////////////

*   PRECIPITATIONS 

*/ ///////////////////

error_log("Building precipitations array.", 3, $log_file);

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

error_log(" Done.\r\n", 3, $log_file);


error_log("Building prevision array.", 3, $log_file);

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

error_log(" Done.\r\n", 3, $log_file);



$dt = new DateTime;

$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
$formatter->setPattern('EEEE d MMM');

// From top to bottom
$WeatherData = array(
  "lastUpdateDate" =>  $formatter->format($dt),
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

error_log("File stored in 'all.json' at time " . date("l jS \of F Y h:i:s A") ."\r\n", 3, $log_file);







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



error_log("Background image. ", 3, $log_file);


$im = new imagick(realpath($randomImageUrl));

error_log("Loading at " . realpath($randomImageUrl) . ". ", 3, $log_file);

$imageprops = $im->getImageGeometry();

$im->setImageCompressionQuality(100);
error_log("\r\nCompress. ", 3, $log_file);

$im->cropThumbnailImage( 600, 800 );

error_log("Crop. ", 3, $log_file);






// dégradé haut 
$imagick2 = new Imagick();
//$imagick2->newPseudoImage(600, 300, 'gradient:white-black');
$imagick2->newPseudoImage(600, 100, 'gradient:#bbbbbb-#ffffff');
// Composite images by BLEND model.
$im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY, 0, 0);

error_log("Adding gradient top. ", 3, $log_file);



// dégradé Bas 
$imagick2 = new Imagick();
//$imagick2->newPseudoImage(600, 300, 'gradient:white-black');
$imagick2->newPseudoImage(600, 280, 'gradient:#ffffff-#555555');
// Composite images by BLEND model.
$im->compositeImage($imagick2, Imagick::COMPOSITE_MULTIPLY, 0, 520);
error_log("Adding gradient bottom. ", 3, $log_file);



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

error_log("Adding gradient center. ", 3, $log_file);





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

error_log("Writing text. ", 3, $log_file);

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
$dateWithoutWhiteSpace = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $WeatherData['lastUpdateDate']);
$im = WriteText($im, $dateWithoutWhiteSpace , $white, 27, $fontDINNNext, 35, 50,\Imagick::ALIGN_LEFT);
$im = WriteText($im, $WeatherData['lastUpdateTime'], $white, 27, $fontDINNNext, 600-35, 50,\Imagick::ALIGN_RIGHT);


$im = WriteText($im, date('H\hi', strtotime('now')), $white, 14, $fontDINNNext, 600-35, 67,\Imagick::ALIGN_RIGHT);

if($batterypercent != "") {

  error_log("Display battery. ", 3, $log_file);

  // Draw big icon
  if($batterypercent <= 8) {

    $posX = 225;
    $posY =355;

    // body
    $draw = new \ImagickDraw();
    $draw->setFillColor($transp);
    $draw->setStrokeColor($white);
    $draw->setStrokeOpacity(1);
    $draw->setStrokeWidth(6);
    $draw->roundRectangle($posX + 0, $posY + 0, $posX + 150, $posY + 70, 5, 5);
    $im->drawImage($draw);
    $im->drawImage($draw);
    //tip
    $draw = new \ImagickDraw();
    $draw->setFillColor($transp);
    $draw->setStrokeColor($white);
          $draw->setStrokeOpacity(1);
    $draw->setStrokeWidth(6);
    $draw->roundRectangle($posX + 150,  $posY + 20, $posX +170, $posY + 50,3,3);
    $im->drawImage($draw);
    $im->drawImage($draw);

    // inside
    $draw = new \ImagickDraw();
    $draw->setFillColor($white);
    $draw->setStrokeOpacity(0);
    $draw->setStrokeWidth(0);
    $draw->rectangle($posX + 8, $posY + 8, $posX + 20, $posY + 62);
    $im->drawImage($draw);

    // text
    $im = WriteText($im, $batterypercent, $white, 50, $fontDINNNext, $posX + 68, $posY + 52,\Imagick::ALIGN_LEFT);
    error_log("Big icon. ", 3, $log_file);
    // draw small icon
  } else {

    $posX = 420;
    $posY =30;
    
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
    $draw->rectangle($posX + 5,  $posY + 5, $posX +5 + $batterypercent +1, $posY + 25);
    $im->drawImage($draw);
    
    $im = WriteText($im, $batterypercent, $white, 24, $fontDINNNext, $posX + 26, $posY + 24,\Imagick::ALIGN_LEFT);
    error_log("Small icon. ", 3, $log_file);

  }

}




error_log("Writing previsions. ", 3, $log_file);


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


error_log("Done.\r\n", 3, $log_file);



/* /////////////////////////////////

    Rain Cast

*/ /////////////////////////////////

if( $WeatherData['precipitations'] != null ) {
  error_log("Writing precipitations. ", 3, $log_file);

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

  error_log("Done.\r\n", 3, $log_file);


} // If there are no precipitationss
else {
  error_log("No precipitations.\r\n", 3, $log_file);
}


/* /////////////////////////////////

    SAVE IMAGE

*/ /////////////////////////////////


error_log("End of image.php \r\n", 3, $log_file);

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