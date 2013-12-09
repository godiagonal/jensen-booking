<?php
header('Content-type: text/html; charset=UTF-8');

// hämta filnamn
$path = pathinfo($_SERVER['PHP_SELF']);
$file = $path['filename'];

$db_username="root";
$db_password="root";
$database="wukwebbi_original";
$host="localhost";

$db = new mysqli($host, $db_username, $db_password, $database);
if ($db->connect_errno) {
  die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>JENSEN</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <script src="javascript/jquery.min.js"></script>
    <script src='javascript/fullcalendar.min.js'></script>
    <script type="text/javascript">
      $(document).ready(function(){
        // do something
      });
    </script>
  </head>
  <body>

      <div id="header">
        <div id="innerHeader">
          <h1>JENSEN</h1> 
          <div id="nav">
            <!-- sätt class "active" beroende på filnamnet -->
            <a class="<?php if($file=="booking"){echo "active";} ?>" href="booking.php">BOKA SAL</a>
          </div>
        </div>
      </div>

      <div id="wrapper">

        <div id="content">