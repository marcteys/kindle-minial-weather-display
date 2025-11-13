<?php


// path of the log file where errors need to be logged
$log_file = dirname(__FILE__)."/my-errors.log";


date_default_timezone_set('Europe/Paris'); 
setlocale(LC_TIME, "fr_FR", "French");

$today = new DateTime('now', new DateTimeZone('Europe/Paris'));



$lat="47.216523";
$lon="-1.574932";
$token="__Wj7dVSTjV9YGu1guveLyDq0g7S7TfTjaHBTPTpO0kj8__";


$timeInMinutesBetweenUpdates = 10;

// http://api.openweathermap.org/data/2.5/forecast?q=Paris&appid=<yourkey>&units=metric
?>