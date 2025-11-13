<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Image loading</title>
</head>
<body>

<?php ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$_GET['verbose']='true';

?>
<h1>Image</h1>
  <?php include("getImage.php"); ?>
  
<img src="weatherImage.png">


</body>
</html>