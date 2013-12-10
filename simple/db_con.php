<?php
$db_username="root";
$db_password="root";
$database="wukwebbi_original";
$host="localhost";

$db = new mysqli($host, $db_username, $db_password, $database);
if ($db->connect_errno) {
  die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}
?>