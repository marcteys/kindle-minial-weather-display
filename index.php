<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test</title>
</head>
<body>
<?php
include('image.php');

?>
<img src="test.jpg">
    <?php



echo '<br>';

// https://rpcache-aa.meteofrance.com/internet2018client/2.0/nowcast/rain?lat=48.847904&lon=2.379711&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__
// https://rpcache-aa.meteofrance.com/internet2018client/2.0/forecast?lat=48.847904&lon=2.379711&id=&instants=morning,afternoon,evening,night&token=__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__

// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric
// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=6522a661efd99b0d7e3c9095e8bb0b0b&units=metric


//$url = 'https://www.dicocitations.com/reference_citation/91210/Du_mode_d_existence_des_objets_techniques_1958_/0.php';
//$content = file_get_contents($url);



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
  echo "THis should never happend";
}



$JSONDATA = json_decode("{}");
$forecast = "";
$raincast = "";
if(!isset($_GET["test"])) {

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
var_dump(json_decode($forecast));

$merged = array("forecast" => json_decode($forecast), "raincast" => json_decode($raincast) );


$JSONDATA = $merged;




// Display Rain

//var_dump($JSONDATA["raincast"]->properties->forecast);
foreach ($JSONDATA["raincast"]->properties->forecast as $key => $value) {
 echo $value->rain_intensity;
}


foreach ($JSONDATA["forecast"]->properties->forecast as $key => $value) {
 echo $value->weather_icon;
 echo "<br>";
 echo getIcones($value->weather_description);
}
//var_dump($JSONDATA["forecast"]->properties->forecast);




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





</body>
</html>
