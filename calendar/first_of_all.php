<?php
header('Content-type: text/html; charset=UTF-8');

// hämta filnamn
$path = pathinfo($_SERVER['PHP_SELF']);
$file = $path['filename'];

require "db_con.php";
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>JENSEN</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <link rel="stylesheet" type="text/css" href="css/ui/jquery-ui-1.10.3.custom.css" />
    <link rel="stylesheet" type="text/css" href="css/fullcalendar.css" />
    <script src="javascript/jquery.min.js"></script>
    <script src="javascript/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="javascript/fullcalendar.min.js"></script>
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
            <a class="<?php if($file=="index"){echo "active";} ?>" href="index.php">STARTSIDA</a>
            <a class="<?php if($file=="booking"){echo "active";} ?>" href="booking.php?course=1">BOKA KLASSRUM</a>
          </div>
        </div>
      </div>

      <div id="wrapper">

        <div id="content">